<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->unsignedBigInteger('cart_id')->nullable();
            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('set null');
            $table->string('payment_method')->nullable();
            $table->date('date_debut_credit')->nullable();
            $table->date('date_fin_credit')->nullable();
            $table->string('reference_credit')->nullable();
            $table->string('payment_status')->nullable();//, ['pending', 'paid', 'failed']
            $table->string('order_status')->nullable();//['processing', 'shipped', 'delivered']
            $table->boolean('is_credit')->nullable();
            $table->date('delivery_date')->nullable();
            $table->decimal('total_price', 10, 2);
            $table->decimal('paid_price', 10, 2);
            $table->decimal('remain_price', 10, 2);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};