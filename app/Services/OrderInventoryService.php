<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;

class OrderInventoryService
{
    public function restoreForCancellation(Order $order, User $user): void
    {
        $order->loadMissing('items.product.ingredients');

        foreach ($order->items as $item) {
            foreach ($item->product->ingredients as $ingredient) {
                $lockedIngredient = Ingredient::query()->lockForUpdate()->findOrFail($ingredient->id);
                $restoredQty = (float) $ingredient->pivot->qty_needed * $item->qty;
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
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);
    }
}
