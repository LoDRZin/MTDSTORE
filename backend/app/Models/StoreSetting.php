<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name',
        'description',
        'cnpj',
        'maintenance_mode',
        'require_login',
        'contact_email',
        'social_links',
        'business_hours',
        'show_business_hours',
        'logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'stripe_active', 'stripe_secret', 'stripe_webhook_secret',
        'mercadopago_active', 'mercadopago_access_token', 'mercadopago_webhook_secret',
        'efi_active', 'efi_client_id', 'efi_client_secret',
        'oxapay_active', 'oxapay_merchant_key',
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'require_login' => 'boolean',
        'show_business_hours' => 'boolean',
        'social_links' => 'array',
        'business_hours' => 'array',
        'stripe_active' => 'boolean',
        'mercadopago_active' => 'boolean',
        'efi_active' => 'boolean',
        'oxapay_active' => 'boolean',
        'stripe_secret' => 'encrypted',
        'stripe_webhook_secret' => 'encrypted',
        'mercadopago_access_token' => 'encrypted',
        'mercadopago_webhook_secret' => 'encrypted',
        'efi_client_id' => 'encrypted',
        'efi_client_secret' => 'encrypted',
        'oxapay_merchant_key' => 'encrypted',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'store_name' => 'MTD STORE',
        ]);
    }
}
