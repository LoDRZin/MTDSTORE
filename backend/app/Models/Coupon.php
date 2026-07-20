<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_purchase_amount',
        'max_discount_value',
        'max_uses',
        'uses_count',
        'expires_at',
        'active',
        'allowed_payment_methods',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_discount_value' => 'decimal:2',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'expires_at' => 'datetime',
        'active' => 'boolean',
        'allowed_payment_methods' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $coupon): void {
            $coupon->code = strtoupper(trim((string) $coupon->code));

            if (! in_array($coupon->type, ['percent', 'fixed'], true)) {
                throw new \InvalidArgumentException('Tipo de desconto de cupom invÃ¡lido.');
            }

            if ((float) $coupon->value < 0) {
                throw new \InvalidArgumentException('O valor do cupom nÃ£o pode ser negativo.');
            }

            if ($coupon->type === 'percent' && (float) $coupon->value > 100) {
                throw new \InvalidArgumentException('O desconto percentual nÃ£o pode ser maior que 100%.');
            }
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'coupon_category');
    }

    public function allowedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_allowed_user');
    }

    public function isValid(): bool
    {
        return $this->active
            && ! ($this->expires_at?->isPast())
            && ! ($this->max_uses !== null && $this->uses_count >= $this->max_uses);
    }

    public function isApplicableToProducts(array $cartProductIds): bool
    {
        $restrictedIds = $this->products->modelKeys();

        return $restrictedIds === []
            || collect($cartProductIds)->every(fn (int $id): bool => in_array($id, $restrictedIds, true));
    }

    public function isApplicableToCategories(array $cartProductIds): bool
    {
        $restrictedIds = $this->categories->modelKeys();

        if ($restrictedIds === []) {
            return true;
        }

        $categoriesByProduct = DB::table('category_product')
            ->whereIn('product_id', $cartProductIds)
            ->get(['product_id', 'category_id'])
            ->groupBy('product_id');

        return collect($cartProductIds)->every(function (int $productId) use ($categoriesByProduct, $restrictedIds): bool {
            return $categoriesByProduct->get($productId, collect())
                ->contains(fn (object $category): bool => in_array($category->category_id, $restrictedIds, true));
        });
    }
}
