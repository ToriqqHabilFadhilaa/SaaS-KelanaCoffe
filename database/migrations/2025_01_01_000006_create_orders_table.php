<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // e.g. ORD-20260917-0001
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // kasir yang handle
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('paid_amount', 15, 2);
            $table->decimal('change_amount', 15, 2);
            $table->enum('payment_method', ['cash', 'qris', 'debit', 'credit'])->default('cash');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
