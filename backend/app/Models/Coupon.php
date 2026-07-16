<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_value',
        'max_discount_value',
        'max_uses',
        'uses_count',
        'expires_at',
        'active',
    ];

    protected $casts = [
        'value'              => 'decimal:2',
        'min_order_value'    => 'decimal:2',
        'max_discount_value' => 'decimal:2',
        'max_uses'           => 'integer',
        'uses_count'         => 'integer',
        'expires_at'         => 'datetime',
        'active'             => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Products this coupon is restricted to.
     * If this collection is empty, the coupon applies to ALL products.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    // ─── Business Logic ──────────────────────────────────────────

    /**
     * Checks if the coupon passes all time/usage rules.
     */
    public function isValid(): bool
    {
        if (!$this->active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) return false;

        return true;
    }

    /**
     * Checks if the coupon can be applied to a given set of product IDs.
     * A coupon with NO product restrictions applies universally.
     *
     * @param  array<int>  $cartProductIds
     */
    public function isApplicableToProducts(array $cartProductIds): bool
    {
        // Load restricted products (uses eager loading cache if already loaded)
        $restrictedProductIds = $this->products->pluck('id')->toArray();

        // No restrictions = applies to everything
        if (empty($restrictedProductIds)) {
            return true;
        }

        // Every product in cart must be in the allowed list
        foreach ($cartProductIds as $productId) {
            if (!in_array($productId, $restrictedProductIds, strict: true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether this coupon has product-level restrictions.
     */
    public function hasProductRestrictions(): bool
    {
        return $this->products()->exists();
    }
}
