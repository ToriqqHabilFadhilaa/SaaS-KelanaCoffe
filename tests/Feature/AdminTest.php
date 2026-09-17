<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_operational_dashboard(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Dashboard')
            ->assertSee('Omzet hari ini');
    }

    public function test_non_admin_cannot_view_admin_dashboard(): void
    {
        /** @var User $cashier */
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_upload_product_image_for_cashier_catalog(): void
    {
        Storage::fake('public');

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
        $category = Category::create([
            'name' => 'Coffee',
            'slug' => 'coffee',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Admin Coffee',
            'sku' => 'ADMIN-IMG-001',
            'price' => 20000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.image', $product), [
                'image' => UploadedFile::fake()->image('coffee.png', 800, 800),
            ])
            ->assertRedirect(route('admin.products'));

        $product = $product->fresh();

        $this->assertNotNull($product->image);
        $this->assertTrue(Storage::disk('public')->exists($product->image));
    }
}