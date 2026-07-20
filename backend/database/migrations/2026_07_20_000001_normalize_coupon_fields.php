<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('coupons', 'min_order_value')) {
            DB::table('coupons')
                ->whereNull('min_purchase_amount')
                ->whereNotNull('min_order_value')
                ->update(['min_purchase_amount' => DB::raw('min_order_value')]);
        }
    }

    public function down(): void
    {
        // The canonical field is min_purchase_amount; copied values are retained.
    }
};
