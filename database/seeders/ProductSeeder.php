<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $coffeeCategory = Category::where('slug', 'coffee')->first();
        $nonCoffeeCategory = Category::where('slug', 'non-coffee')->first();
        $pastryCategory = Category::where('slug', 'pastry')->first();

        // Map ingredient sku -> id, biar mudah dipakai untuk resep
        $ing = Ingredient::pluck('id', 'sku');

        $products = [
            [
                'category_id' => $coffeeCategory->id,
                'name' => 'Espresso',
                'sku' => 'PRD-001',
                'description' => 'Shot espresso murni dari kopi arabica.',
                'price' => 18000,
                'recipe' => [
                    ['sku' => 'ING-001', 'qty' => 18], // 18 gram kopi arabica
                    ['sku' => 'ING-006', 'qty' => 30], // sedikit air
                ],
            ],
            [
                'category_id' => $coffeeCategory->id,
                'name' => 'Cappuccino',
                'sku' => 'PRD-002',
                'description' => 'Espresso dengan foam susu lembut.',
                'price' => 25000,
                'recipe' => [
                    ['sku' => 'ING-001', 'qty' => 18],
                    ['sku' => 'ING-003', 'qty' => 150],
                ],
            ],
            [
                'category_id' => $coffeeCategory->id,
                'name' => 'Kopi Susu Gula Aren',
                'sku' => 'PRD-003',
                'description' => 'Signature kopi susu dengan gula aren.',
                'price' => 22000,
                'recipe' => [
                    ['sku' => 'ING-002', 'qty' => 20],
                    ['sku' => 'ING-003', 'qty' => 120],
                    ['sku' => 'ING-004', 'qty' => 30],
                    ['sku' => 'ING-007', 'qty' => 100],
                ],
            ],
            [
                'category_id' => $nonCoffeeCategory->id,
                'name' => 'Chocolate Milk',
                'sku' => 'PRD-004',
                'description' => 'Susu coklat dingin untuk non-coffee lover.',
                'price' => 20000,
                'recipe' => [
                    ['sku' => 'ING-009', 'qty' => 25],
                    ['sku' => 'ING-003', 'qty' => 150],
                    ['sku' => 'ING-007', 'qty' => 100],
                ],
            ],
            [
                'category_id' => $nonCoffeeCategory->id,
                'name' => 'Vanilla Latte (Non-Coffee)',
                'sku' => 'PRD-005',
                'description' => 'Susu dengan sirup vanilla, tanpa kopi.',
                'price' => 21000,
                'recipe' => [
                    ['sku' => 'ING-003', 'qty' => 180],
                    ['sku' => 'ING-005', 'qty' => 20],
                ],
            ],
            [
                'category_id' => $pastryCategory->id,
                'name' => 'Croissant Butter',
                'sku' => 'PRD-006',
                'description' => 'Croissant butter dipanggang segar.',
                'price' => 17000,
                'recipe' => [
                    ['sku' => 'ING-008', 'qty' => 1],
                ],
            ],
        ];

        foreach ($products as $data) {
            $recipe = $data['recipe'];
            unset($data['recipe']);

            $product = Product::create($data);

            foreach ($recipe as $item) {
                $product->ingredients()->attach($ing[$item['sku']], [
                    'qty_needed' => $item['qty'],
                ]);
            }
        }
    }
}
