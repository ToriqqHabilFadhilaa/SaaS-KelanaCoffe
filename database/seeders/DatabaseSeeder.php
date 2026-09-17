<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan seeder mengikuti dependency FK:
     * Users & Suppliers (independent) -> Categories (independent)
     * -> Ingredients (depends on Suppliers) -> Products (depends on Categories)
     * -> ProductIngredients / resep (depends on Products & Ingredients)
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SupplierSeeder::class,
            CategorySeeder::class,
            IngredientSeeder::class,
            ProductSeeder::class, // sudah include attach resep di dalamnya
        ]);
    }
}
