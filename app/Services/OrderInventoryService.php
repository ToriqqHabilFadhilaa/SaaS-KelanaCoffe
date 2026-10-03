<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class OrderInventoryService
{
    public function restoreForCancellation(Order $order, User $user): bool
    {
        $order = Order::query()->lockForUpdate()->findOrFail($order->id);

        if ($order->status !== Order::STATUS_PENDING) {
            return false;
        }

        $saleMovements = StockMovement::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', StockMovement::TYPE_OUT)
            ->where('reason', StockMovement::REASON_SALE)
            ->get();

        if ($saleMovements->isEmpty()) {
            throw ValidationException::withMessages([
                'order' => 'Pergerakan stok penjualan tidak ditemukan; pembatalan otomatis dihentikan untuk mencegah stok tidak akurat.',
            ]);
        }

        foreach ($saleMovements as $saleMovement) {
            $lockedIngredient = Ingredient::query()->lockForUpdate()->findOrFail($saleMovement->ingredient_id);
            $restoredQty = (float) $saleMovement->qty;
            $stockBefore = (float) $lockedIngredient->stock;
            $stockAfter = $stockBefore + $restoredQty;

            $lockedIngredient->update(['stock' => $stockAfter]);

            StockMovement::query()->create([
                'ingredient_id' => $lockedIngredient->id,
                'user_id' => $user->id,
                'type' => StockMovement::TYPE_IN,
                'reason' => StockMovement::REASON_ADJUSTMENT,
                'qty' => $restoredQty,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'note' => 'Pembatalan '.$order->order_number,
            ]);
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);

        return true;
    }
}
