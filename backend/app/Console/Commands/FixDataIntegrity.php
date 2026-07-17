<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDataIntegrity extends Command
{
    protected $signature = 'data:fix-integrity';
    protected $description = 'Corrige dados inválidos no banco de dados para evitar violacão de constraints';

    public function handle()
    {
        $this->info('Corrigindo integridade dos dados...');
        
        // 1. Fix products status
        $fixedProducts = DB::table('products')
            ->whereNotIn('status', ['draft', 'active', 'archived'])
            ->update(['status' => 'draft']);
        
        $this->info("✓ Products: {$fixedProducts} registros corrigidos");
        
        // 2. Fix orders status
        $fixedOrders = DB::table('orders')
            ->whereNotIn('status', ['pending', 'awaiting_payment', 'paid', 'failed', 'refunded', 'partially_refunded', 'chargeback'])
            ->update(['status' => 'pending']);
        
        $this->info("✓ Orders: {$fixedOrders} registros corrigidos");
        
        // 3. Fix stock_items status
        $fixedStock = DB::table('product_stock_items')
            ->whereNotIn('status', ['available', 'sold', 'revoked'])
            ->update(['status' => 'available']);
        
        $this->info("✓ Stock Items: {$fixedStock} registros corrigidos");
        
        // 4. Fix null prices or negative
        $fixedPrices = DB::table('products')
            ->where('price', '<=', 0)
            ->update(['price' => 0.01]);
        
        $this->info("✓ Prices: {$fixedPrices} registros corrigidos");
        
        // 5. Fix nullable fields
        DB::table('products')
            ->where('description', '')
            ->update(['description' => null]);
            
        DB::table('products')
            ->where('post_purchase_instructions', '')
            ->update(['post_purchase_instructions' => null]);
        
        $this->info("✓ Empty text fields converted to null");
        
        $this->newLine();
        $this->info('✅ Integridade dos dados restaurada!');
        
        return Command::SUCCESS;
    }
}
