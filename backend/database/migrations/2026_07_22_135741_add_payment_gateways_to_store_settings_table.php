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
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('stripe_active')->default(false);
            $table->text('stripe_secret')->nullable();
            $table->text('stripe_webhook_secret')->nullable();
            
            $table->boolean('mercadopago_active')->default(false);
            $table->text('mercadopago_access_token')->nullable();
            $table->text('mercadopago_webhook_secret')->nullable();

            $table->boolean('efi_active')->default(false);
            $table->text('efi_client_id')->nullable();
            $table->text('efi_client_secret')->nullable();
            
            $table->boolean('oxapay_active')->default(false);
            $table->text('oxapay_merchant_key')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_active', 'stripe_secret', 'stripe_webhook_secret',
                'mercadopago_active', 'mercadopago_access_token', 'mercadopago_webhook_secret',
                'efi_active', 'efi_client_id', 'efi_client_secret',
                'oxapay_active', 'oxapay_merchant_key'
            ]);
        });
    }
};
