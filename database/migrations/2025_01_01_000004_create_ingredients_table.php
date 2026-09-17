<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            // Satuan: gram, ml, pcs, kg, liter — disimpan sebagai string unit dasar per bahan
            $table->string('unit', 20);
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->decimal('cost_per_unit', 15, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
