<?php

namespace App\Services;

use App\Models\Order;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use Illuminate\Support\Facades\DB;

class MidtransPaymentService
{
    public function isConfigured(): bool
    {
        return filled(config('services.midtrans.server_key'))
            && filled(config('services.midtrans.client_key'));
    }

    public function createSnapToken(Order $order): string
    {
        if (! $this->isConfigured()) {
            throw new InvalidArgumentException('Midtrans Sandbox belum dikonfigurasi.');
        }

        $this->configure();
        $order->loadMissing('items');

        $itemDetails = $order->items->map(fn ($item) => [
            'id' => (string) $item->product_id,
            'price' => (int) round((float) $item->price),
            'quantity' => (int) $item->qty,
            'name' => $item->product_name,
        ])->values()->all();

        if ((float) $order->tax > 0) {
            $itemDetails[] = [
                'id' => 'tax',
                'price' => (int) round((float) $order->tax),
                'quantity' => 1,
                'name' => 'Pajak 10%',
            ];
        }

        return Snap::getSnapToken([
            'transaction_details' => [
                'order_id' => $order->midtrans_order_id ?: $order->order_number,
                'gross_amount' => (int) round((float) $order->total),
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => $order->user?->name ?? 'Kelana Coffee Customer',
                'email' => $order->user?->email,
            ],
        ]);
    }

    public function hasValidSignature(array $payload): bool
    {
        $signature = $payload['signature_key'] ?? null;
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $serverKey = (string) config('services.midtrans.server_key');

        if (! $signature || $serverKey === '') {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, (string) $signature);
    }

    public function synchronizeOrder(Order $order, OrderInventoryService $inventoryService): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $this->configure();
        $response = Transaction::status($order->midtrans_order_id ?: $order->order_number);
        $this->applyStatus($order, (array) $response, $inventoryService);
    }

    public function applyStatus(Order $order, array $payload, OrderInventoryService $inventoryService): void
    {
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;
        $isPaid = in_array($transactionStatus, ['settlement', 'capture'], true)
            && ($fraudStatus === null || $fraudStatus === 'accept');
        $isFailed = in_array($transactionStatus, ['cancel', 'deny', 'expire', 'failure'], true);

        DB::transaction(function () use ($order, $payload, $isPaid, $isFailed, $inventoryService) {
            $order->refresh();

            if ($order->status !== Order::STATUS_PENDING) {
                return;
            }

            if ($isPaid) {
                $order->update([
                    'status' => Order::STATUS_COMPLETED,
                    'paid_amount' => (float) ($payload['gross_amount'] ?? $order->total),
                    'paid_at' => now(),
                ]);

                return;
            }

            if ($isFailed) {
                $inventoryService->restoreForCancellation($order, $order->user()->firstOrFail());
            }
        });
    }

    public function configure(): void
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }
}
