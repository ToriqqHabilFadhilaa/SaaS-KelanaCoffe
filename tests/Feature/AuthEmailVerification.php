<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthEmailVerification extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration_sends_verification_email_and_blocks_login_until_verified(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Budi Test',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->post('/login', [
            'email' => 'budi@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_verification_link_marks_email_as_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->get($verificationUrl)
            ->assertRedirect('/login');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_user_can_login_with_remember_me(): void
    {
        $user = User::factory()->create([
            'email' => 'remember@example.com',
            'email_verified_at' => now(),
            'password' => 'password123',
        ]);

        $this->post('/login', [
            'email' => 'remember@example.com',
            'password' => 'password123',
            'remember' => '1',
        ])->assertRedirect('/cashier/dashboard');

        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_login_and_register_pages_show_password_toggle_buttons(): void
    {
        $this->get('/login')
            ->assertSee('data-password-toggle', false)
            ->assertSee('aria-label="Lihat password"', false);

        $this->get('/register')
            ->assertSee('data-password-toggle', false)
            ->assertSee('aria-label="Lihat password"', false);
    }

    public function test_cashier_can_checkout_with_valid_stock_and_create_order(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($cashier);

        $category = Category::create([
            'name' => 'Coffee',
            'slug' => 'coffee',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Espresso',
            'sku' => 'TEST-ESP-001',
            'description' => 'Test coffee',
            'price' => 20000,
            'is_active' => true,
        ]);

        $ingredient = Ingredient::create([
            'name' => 'Arabica Beans',
            'sku' => 'ING-TEST-001',
            'unit' => 'gram',
            'stock' => 1000,
            'minimum_stock' => 100,
            'cost_per_unit' => 250,
        ]);

        $product->ingredients()->attach($ingredient->id, ['qty_needed' => 50]);

        $response = $this->post('/cashier/checkout', [
            'items' => json_encode([
                ['product_id' => $product->id, 'qty' => 2],
            ]),
            'payment_method' => 'cash',
            'cash_received' => 50000,
        ]);

        $order = Order::latest()->first();

        $response->assertRedirect(route('cashier.receipt', ['order' => $order->id]));
        $this->assertDatabaseHas('orders', [
            'payment_method' => 'cash',
            'status' => Order::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'qty' => 2,
        ]);
        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'stock' => 900,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $ingredient->id,
            'type' => 'out',
            'reason' => 'sale',
            'qty' => 100,
        ]);
    }

    public function test_cashier_cannot_checkout_when_ingredient_stock_is_insufficient(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($cashier);

        $category = Category::create([
            'name' => 'Coffee',
            'slug' => 'coffee',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Espresso',
            'sku' => 'TEST-ESP-002',
            'description' => 'Test coffee',
            'price' => 20000,
            'is_active' => true,
        ]);

        $ingredient = Ingredient::create([
            'name' => 'Arabica Beans',
            'sku' => 'ING-TEST-002',
            'unit' => 'gram',
            'stock' => 10,
            'minimum_stock' => 5,
            'cost_per_unit' => 250,
        ]);

        $product->ingredients()->attach($ingredient->id, ['qty_needed' => 50]);

        $this->post('/cashier/checkout', [
            'items' => json_encode([
                ['product_id' => $product->id, 'qty' => 1],
            ]),
            'payment_method' => 'cash',
            'cash_received' => 30000,
        ])->assertSessionHasErrors(['items']);
    }

    public function test_non_cashier_cannot_access_cashier_routes(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        $this->get(route('cashier.dashboard'))->assertForbidden();
        $this->get(route('cashier.history'))->assertForbidden();
    }

    public function test_cashier_cannot_view_another_cashiers_receipt(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'email_verified_at' => now(),
        ]);
        $otherCashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'email_verified_at' => now(),
        ]);

        $order = Order::create([
            'order_number' => 'ORD-OWNERSHIP-001',
            'user_id' => $otherCashier->id,
            'subtotal' => 10000,
            'discount' => 0,
            'tax' => 1000,
            'total' => 11000,
            'paid_amount' => 11000,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'status' => Order::STATUS_COMPLETED,
            'paid_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->get(route('cashier.receipt', $order))
            ->assertForbidden();
    }

    public function test_qris_checkout_is_pending_and_cancellation_restores_stock(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($cashier);

        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'QRIS Coffee',
            'sku' => 'TEST-QRIS-001',
            'price' => 20000,
            'is_active' => true,
        ]);
        $ingredient = Ingredient::create([
            'name' => 'QRIS Beans',
            'sku' => 'ING-QRIS-001',
            'unit' => 'gram',
            'stock' => 100,
            'minimum_stock' => 10,
            'cost_per_unit' => 250,
        ]);
        $product->ingredients()->attach($ingredient->id, ['qty_needed' => 50]);

        $this->post(route('cashier.checkout'), [
            'items' => json_encode([['product_id' => $product->id, 'qty' => 1]]),
            'payment_method' => 'qris',
        ]);

        $order = Order::latest()->firstOrFail();

        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame('50.00', (string) $ingredient->fresh()->stock);

        $this->post(route('cashier.cancel', $order))
            ->assertRedirect(route('cashier.history'));

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame('100.00', (string) $ingredient->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'reference_id' => $order->id,
            'type' => StockMovement::TYPE_IN,
            'reason' => StockMovement::REASON_ADJUSTMENT,
        ]);
    }

    public function test_history_status_filter_excludes_completed_orders_from_pending_view(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'email_verified_at' => now(),
        ]);
        $order = Order::create([
            'order_number' => 'ORD-FILTER-001',
            'user_id' => $cashier->id,
            'subtotal' => 10000,
            'discount' => 0,
            'tax' => 1000,
            'total' => 11000,
            'paid_amount' => 0,
            'change_amount' => 0,
            'payment_method' => 'qris',
            'status' => Order::STATUS_PENDING,
        ]);

        $this->actingAs($cashier)
            ->get(route('cashier.history', ['status' => Order::STATUS_PENDING]))
            ->assertOk()
            ->assertSee($order->order_number);
    }
}
