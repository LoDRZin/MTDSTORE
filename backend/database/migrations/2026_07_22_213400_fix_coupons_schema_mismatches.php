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
        Schema::table('coupons', function (Blueprint $table) {
            // Rename min_order_value to min_purchase_amount if it exists
            if (Schema::hasColumn('coupons', 'min_order_value') && !Schema::hasColumn('coupons', 'min_purchase_amount')) {
                $table->renameColumn('min_order_value', 'min_purchase_amount');
            } elseif (!Schema::hasColumn('coupons', 'min_purchase_amount')) {
                $table->decimal('min_purchase_amount', 10, 2)->nullable()->after('value');
            }

            if (!Schema::hasColumn('coupons', 'allowed_payment_methods')) {
                $table->json('allowed_payment_methods')->nullable()->after('max_discount_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (Schema::hasColumn('coupons', 'min_purchase_amount')) {
                $table->renameColumn('min_purchase_amount', 'min_order_value');
            }
            if (Schema::hasColumn('coupons', 'allowed_payment_methods')) {
                $table->dropColumn('allowed_payment_methods');
            }
        });
    }
};
