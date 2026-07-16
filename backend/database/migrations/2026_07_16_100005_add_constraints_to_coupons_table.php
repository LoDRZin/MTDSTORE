<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Valor mínimo do pedido para o cupom ser aplicável
            $table->decimal('min_order_value', 10, 2)->nullable()->after('value');
            // Cap máximo de desconto (útil para cupons de % com teto)
            $table->decimal('max_discount_value', 10, 2)->nullable()->after('min_order_value');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['min_order_value', 'max_discount_value']);
        });
    }
};
