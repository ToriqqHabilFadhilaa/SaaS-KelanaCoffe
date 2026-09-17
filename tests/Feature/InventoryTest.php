<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_inventory_role_can_open_inventory_dashboard(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->get(route('inventory.dashboard'))
            ->assertForbidden();
    }

    public function test_inventory_can_receive_stock_and_log_movement(): void
    {
        /** @var User $inventoryUser */
        $inventoryUser = User::factory()->create([
            'role' => User::ROLE_INVENTORY,
            'email_verified_at' => now(),
        ]);
        $ingredient = Ingredient::create([
            'name' => 'Test Milk',
            'sku' => 'ING-STOCK-IN-001',
            'unit' => 'ml',
            'stock' => 100,
            'minimum_stock' => 20,
            'cost_per_unit' => 10,
        ]);

        $this->actingAs($inventoryUser)
            ->post(route('inventory.stock-in'), [
                'ingredient_id' => $ingredient->id,
                'qty' => 50,
                'note' => 'Restock supplier',
            ])
            ->assertRedirect(route('inventory.dashboard'));

        $this->assertSame('150.00', (string) $ingredient->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $ingredient->id,
            'user_id' => $inventoryUser->id,
            'type' => 'in',
            'reason' => 'purchase',
            'qty' => 50,
        ]);
    }

    public function test_inventory_cannot_remove_more_stock_than_available(): void
    {
        /** @var User $inventoryUser */
        $inventoryUser = User::factory()->create([
            'role' => User::ROLE_INVENTORY,
            'email_verified_at' => now(),
        ]);
        $ingredient = Ingredient::create([
            'name' => 'Test Beans',
            'sku' => 'ING-STOCK-OUT-001',
            'unit' => 'gram',
            'stock' => 25,
            'minimum_stock' => 10,
            'cost_per_unit' => 10,
        ]);

        $this->actingAs($inventoryUser)
            ->post(route('inventory.stock-out'), [
                'ingredient_id' => $ingredient->id,
                'qty' => 30,
                'reason' => 'damage',
            ])
            ->assertSessionHasErrors('qty');

        $this->assertSame('25.00', (string) $ingredient->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
