<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Product extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_url',
        'price',
        'status',
        'post_purchase_instructions',
        'delivery_type',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['price', 'status'])
            ->logOnlyDirty();
    }

    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'string',
        'description' => 'string',
        'image_url' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($product) {
            $validStatuses = ['draft', 'active', 'archived'];
            if (!in_array($product->status, $validStatuses)) {
                throw new \InvalidArgumentException("Status inválido: {$product->status}. Válidos: " . implode(', ', $validStatuses));
            }
            
            if ($product->price < 0) {
                throw new \InvalidArgumentException("Preço não pode ser negativo");
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────

    public function stockItems(): HasMany
    {
        return $this->hasMany(ProductStockItem::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_product');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    /** Only published products */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Filter by category slug (looks up the category tree).
     * Includes products in child categories too.
     */
    public function scopeByCategory($query, string $slug)
    {
        return $query->whereHas('categories', function ($q) use ($slug) {
            $q->where('slug', $slug)
              ->orWhereHas('parent', fn ($p) => $p->where('slug', $slug));
        });
    }

    /** Filter to products that have at least one available stock item */
    public function scopeInStock($query)
    {
        return $query->whereHas('stockItems', function ($q) {
            $q->where('status', 'available');
        });
    }

    /** Filter by minimum price */
    public function scopeMinPrice($query, float $min)
    {
        return $query->where('price', '>=', $min);
    }

    /** Filter by maximum price */
    public function scopeMaxPrice($query, float $max)
    {
        return $query->where('price', '<=', $max);
    }
}
