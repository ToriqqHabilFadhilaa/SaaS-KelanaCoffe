<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $kopiSupplier = Supplier::where('name', 'PT Kopi Nusantara')->first();
        $susuSupplier = Supplier::where('name', 'CV Susu Segar Jaya')->first();
        $gulaSupplier = Supplier::where('name', 'Toko Gula & Sirup Manis')->first();

        $ingredients = [
            ['supplier_id' => $kopiSupplier->id, 'name' => 'Kopi Arabica Bean', 'sku' => 'ING-001', 'unit' => 'gram', 'stock' => 5000, 'minimum_stock' => 1000, 'cost_per_unit' => 250],
            ['supplier_id' => $kopiSupplier->id, 'name' => 'Kopi Robusta Bean', 'sku' => 'ING-002', 'unit' => 'gram', 'stock' => 5000, 'minimum_stock' => 1000, 'cost_per_unit' => 180],
            ['supplier_id' => $susuSupplier->id, 'name' => 'Susu Fresh Milk', 'sku' => 'ING-003', 'unit' => 'ml', 'stock' => 10000, 'minimum_stock' => 2000, 'cost_per_unit' => 15],
            ['supplier_id' => $gulaSupplier->id, 'name' => 'Gula Cair', 'sku' => 'ING-004', 'unit' => 'ml', 'stock' => 3000, 'minimum_stock' => 500, 'cost_per_unit' => 10],
            ['supplier_id' => $gulaSupplier->id, 'name' => 'Sirup Vanilla', 'sku' => 'ING-005', 'unit' => 'ml', 'stock' => 2000, 'minimum_stock' => 300, 'cost_per_unit' => 40],
            ['supplier_id' => null, 'name' => 'Air Mineral', 'sku' => 'ING-006', 'unit' => 'ml', 'stock' => 20000, 'minimum_stock' => 5000, 'cost_per_unit' => 2],
            ['supplier_id' => null, 'name' => 'Es Batu', 'sku' => 'ING-007', 'unit' => 'gram', 'stock' => 15000, 'minimum_stock' => 3000, 'cost_per_unit' => 1],
            ['supplier_id' => null, 'name' => 'Roti Croissant (Frozen)', 'sku' => 'ING-008', 'unit' => 'pcs', 'stock' => 50, 'minimum_stock' => 10, 'cost_per_unit' => 8000],
            ['supplier_id' => null, 'name' => 'Coklat Bubuk', 'sku' => 'ING-009', 'unit' => 'gram', 'stock' => 100, 'minimum_stock' => 500, 'cost_per_unit' => 120], // sengaja di bawah minimum -> contoh low stock
        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::create($ingredient);
        }
    }
}
