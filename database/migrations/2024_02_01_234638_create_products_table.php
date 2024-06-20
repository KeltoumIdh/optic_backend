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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('reference'); // Unique reference code for the product.
            $table->string('name'); // Name of the product.
            $table->decimal('price', 8, 2); // Price of the product (decimal to handle cents).
            $table->integer('quantity_available'); // Current quantity available for sale.
            $table->integer('initial_quantity'); // Initial quantity of the product (when added to inventory).
            $table->integer('quantity_sold'); // Quantity of the product already sold.
            $table->string('image'); // File path or URL to the product image.
            $table->string('status'); // Status of the product.
            $table->text('message')->nullable(); // Additional message or notes about the product.
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};