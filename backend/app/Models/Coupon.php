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
        'min_purchase_amount',
        'allowed_payment_methods',
    ];

    protected $casts = [
        'value'                   => 'decimal:2',
        'min_order_value'         => 'decimal:2',
        'max_discount_value'      => 'decimal:2',
        'max_uses'                => 'integer',
        'uses_count'              => 'integer',
        'expires_at'              => 'datetime',
        'active'                  => 'boolean',
        'min_purchase_amount'     => 'decimal:2',
        'allowed_payment_methods' => 'array',
        'type'                    => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($coupon) {
            $validTypes = ['percentage', 'fixed'];
            if (!in_array($coupon->type, $validTypes)) {
                throw new \InvalidArgumentException("Tipo de desconto de cupom inválido: {$coupon->type}");
            }
            
            if ($coupon->value < 0) {
                throw new \InvalidArgumentException("O valor do cupom não pode ser negativo");
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────

    /**
     * Products this coupon is restricted to.
     * If this collection is empty, the coupon applies to ALL products.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function allowedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_allowed_user');
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

    public function hasProductRestrictions(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Checks if the coupon can be applied to the categories of the given product IDs.
     */
    public function isApplicableToCategories(array $cartProductIds): bool
    {
        $restrictedCategoryIds = $this->categories->pluck('id')->toArray();

        if (empty($restrictedCategoryIds)) {
            return true;
        }

        // We need to ensure that every product in the cart has AT LEAST ONE category 
        // that is within the restricted category IDs.
        $productCategories = \Illuminate\Support\Facades\DB::table('category_product')
            ->whereIn('product_id', $cartProductIds)
            ->get()
            ->groupBy('product_id');

        foreach ($cartProductIds as $productId) {
            $categoriesForProduct = $productCategories->get($productId);

            // Se o produto não tiver nenhuma categoria, e o cupom exige categoria, falha.
            if (!$categoriesForProduct) {
                return false;
            }

            // Verifica se alguma das categorias deste produto está na lista de permitidas
            $hasAllowedCategory = false;
            foreach ($categoriesForProduct as $cat) {
                if (in_array($cat->category_id, $restrictedCategoryIds, strict: true)) {
                    $hasAllowedCategory = true;
                    break;
                }
            }

            if (!$hasAllowedCategory) {
                return false;
            }
        }

        return true;
    }
}
