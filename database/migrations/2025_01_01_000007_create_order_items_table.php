<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            // Snapshot nama & harga saat transaksi — produk/harga bisa berubah di masa depan,
            // tapi histori transaksi harus tetap akurat sesuai kondisi saat itu.
            $table->string('product_name');
            $table->decimal('price', 15, 2);
            $table->unsignedInteger('qty');
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
