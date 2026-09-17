<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('midtrans_order_id')->nullable()->unique()->after('order_number');
            $table->text('midtrans_token')->nullable()->after('midtrans_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['midtrans_order_id']);
            $table->dropColumn(['midtrans_order_id', 'midtrans_token']);
        });
    }
};
