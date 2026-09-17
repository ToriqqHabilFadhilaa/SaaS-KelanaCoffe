<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::insert([
            ['name' => 'PT Kopi Nusantara', 'phone' => '081234567890', 'address' => 'Malang, Jawa Timur', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'CV Susu Segar Jaya', 'phone' => '081234567891', 'address' => 'Surabaya, Jawa Timur', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Toko Gula & Sirup Manis', 'phone' => '081234567892', 'address' => 'Blitar, Jawa Timur', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
