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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_code')->unique();
            $table->string('customer_name');
            $table->string('phone', 30);
            $table->text('address');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('payment_status')->default('pending');
            $table->string('order_status')->default('menunggu_pembayaran');
            $table->string('courier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['payment_status', 'order_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
