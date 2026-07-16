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
        Schema::create('product_stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('value');
            $table->enum('status', ['available', 'sold', 'revoked'])->default('available');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users');
            $table->uuid('batch_id')->index();
            $table->timestamps();
            
            $table->index(['product_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stock_items');
    }
};
