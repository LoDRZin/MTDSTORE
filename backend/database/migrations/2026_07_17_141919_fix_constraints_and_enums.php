<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Atualizar valores inválidos existentes para products
        DB::table('products')->where('status', 'published')->update(['status' => 'active']);
        DB::table('products')->where('status', 'unpublished')->update(['status' => 'draft']);
        DB::table('products')->whereNotIn('status', ['draft', 'active', 'archived'])->update(['status' => 'draft']);
        
        // 2. Drop e recria constraint para products
        try {
            DB::statement("ALTER TABLE products DROP CONSTRAINT IF EXISTS products_status_check");
        } catch (\Exception $e) {}
        
        DB::statement("
            ALTER TABLE products 
            ADD CONSTRAINT products_status_check 
            CHECK (status::text = ANY (ARRAY['draft'::character varying, 'active'::character varying, 'archived'::character varying]::text[]))
        ");

        // 3. Atualizar valores inválidos existentes para orders
        DB::table('orders')->whereNotIn('status', ['pending', 'awaiting_payment', 'paid', 'failed', 'refunded', 'partially_refunded', 'chargeback'])->update(['status' => 'pending']);

        try {
            DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        } catch (\Exception $e) {}
        
        DB::statement("
            ALTER TABLE orders 
            ADD CONSTRAINT orders_status_check 
            CHECK (status::text = ANY (ARRAY['pending'::character varying, 'awaiting_payment'::character varying, 'paid'::character varying, 'failed'::character varying, 'refunded'::character varying, 'partially_refunded'::character varying, 'chargeback'::character varying]::text[]))
        ");

        // 4. Atualizar valores inválidos para product_stock_items
        DB::table('product_stock_items')->whereNotIn('status', ['available', 'sold', 'revoked'])->update(['status' => 'available']);

        try {
            DB::statement("ALTER TABLE product_stock_items DROP CONSTRAINT IF EXISTS product_stock_items_status_check");
        } catch (\Exception $e) {}
        
        DB::statement("
            ALTER TABLE product_stock_items 
            ADD CONSTRAINT product_stock_items_status_check 
            CHECK (status::text = ANY (ARRAY['available'::character varying, 'sold'::character varying, 'revoked'::character varying]::text[]))
        ");
    }

    public function down(): void
    {
        // Reversão não é necessária para dados limpos, apenas removemos se necessário
    }
};
