<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'require_login' => 'boolean',
        'show_business_hours' => 'boolean',
        'social_links' => 'array',
        'business_hours' => 'array',
    ];
}
