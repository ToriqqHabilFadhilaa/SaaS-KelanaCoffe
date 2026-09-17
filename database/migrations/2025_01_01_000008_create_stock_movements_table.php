<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // siapa yang input
            $table->enum('type', ['in', 'out']);
            // Sumber pergerakan stok — penting untuk laporan & audit:
            // in: purchase (dari supplier), adjustment (koreksi manual)
            // out: sale (auto dari transaksi), damage (rusak), adjustment (koreksi manual)
            $table->enum('reason', ['purchase', 'sale', 'damage', 'adjustment']);
            $table->decimal('qty', 12, 2); // selalu positif, arah ditentukan oleh `type`
            $table->decimal('stock_before', 12, 2);
            $table->decimal('stock_after', 12, 2);
            // Polymorphic reference: bisa merujuk ke order_item (sale) atau null (manual)
            $table->nullableMorphs('reference');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['ingredient_id', 'created_at']);
            $table->index(['type', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
