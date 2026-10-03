<?php

use App\Models\Order;
use App\Services\MidtransPaymentService;
use App\Services\OrderInventoryService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::useCache('array');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Order::query()
        ->where('status', Order::STATUS_PENDING)
        ->where('payment_method', 'qris')
        ->whereNotNull('midtrans_token')
        ->orderBy('id')
        ->chunkById(50, function ($orders): void {
            $midtransService = app(MidtransPaymentService::class);
            $inventoryService = app(OrderInventoryService::class);

            foreach ($orders as $order) {
                try {
                    $midtransService->synchronizeOrder($order, $inventoryService);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        });
})->name('midtrans-pending-reconciliation')->everyFiveMinutes();
