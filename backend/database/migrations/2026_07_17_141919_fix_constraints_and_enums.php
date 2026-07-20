<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite already represents Laravel enums as strings. PostgreSQL needs the
        // explicit check constraints below to accept the expanded status sets.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::table('products')->where('status', 'published')->update(['status' => 'active']);
        DB::table('products')->where('status', 'unpublished')->update(['status' => 'draft']);
        DB::table('products')->whereNotIn('status', ['draft', 'active', 'archived'])->update(['status' => 'draft']);
        DB::table('orders')->whereNotIn('status', [
            'pending', 'awaiting_payment', 'paid', 'failed', 'refunded', 'partially_refunded', 'chargeback',
        ])->update(['status' => 'pending']);
        DB::table('product_stock_items')->whereNotIn('status', ['available', 'sold', 'revoked'])->update(['status' => 'available']);

        DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_status_check');
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_status_check CHECK (status::text = ANY (ARRAY['draft'::character varying, 'active'::character varying, 'archived'::character varying]::text[]))");

        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'awaiting_payment'::character varying, 'paid'::character varying, 'failed'::character varying, 'refunded'::character varying, 'partially_refunded'::character varying, 'chargeback'::character varying]::text[]))");

        DB::statement('ALTER TABLE product_stock_items DROP CONSTRAINT IF EXISTS product_stock_items_status_check');
        DB::statement("ALTER TABLE product_stock_items ADD CONSTRAINT product_stock_items_status_check CHECK (status::text = ANY (ARRAY['available'::character varying, 'sold'::character varying, 'revoked'::character varying]::text[]))");
    }

    public function down(): void
    {
        // Existing data is preserved; status constraints are forward-only.
    }
};
