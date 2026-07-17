<?php

namespace App\Services;

use App\Models\Coupon;
use App\DTOs\CouponDTO;
use Illuminate\Support\Facades\DB;
use Exception;

class CouponService
{
    /**
     * Validate a coupon and calculate discount.
     *
     * @param  string       $code
     * @param  float        $orderTotal
     * @param  array<int>   $cartProductIds  IDs of products in the cart (for restriction check)
     * @return CouponDTO
     * @throws Exception
     */
    public function validate(string $code, float $orderTotal, array $cartProductIds = [], ?int $userId = null, ?string $paymentMethod = null): CouponDTO
    {
        // Eager-load relations to avoid N+1 inside restriction checks
        $coupon = Coupon::where('code', strtoupper(trim($code)))
            ->with(['products:id', 'categories:id', 'allowedUsers:id'])
            ->first();

        if (!$coupon) {
            throw new Exception('Cupom não encontrado.', 404);
        }

        if (!$coupon->isValid()) {
            throw new Exception('Cupom inválido, expirado ou esgotado.', 422);
        }

        // Check minimum order value
        if ($coupon->min_order_value !== null && $orderTotal < (float) $coupon->min_order_value) {
            throw new Exception(
                sprintf(
                    'Este cupom requer um pedido mínimo de R$ %s.',
                    number_format((float) $coupon->min_order_value, 2, ',', '.')
                ),
                422
            );
        }

        // Check product restrictions
        if (!empty($cartProductIds) && !$coupon->isApplicableToProducts($cartProductIds)) {
            throw new Exception(
                'Este cupom não é válido para os produtos selecionados.',
                422
            );
        }

        // Check category restrictions
        if (!empty($cartProductIds) && !$coupon->isApplicableToCategories($cartProductIds)) {
            throw new Exception(
                'Este cupom não é válido para a categoria dos produtos selecionados.',
                422
            );
        }

        // Check user restrictions
        if ($coupon->allowedUsers->isNotEmpty()) {
            if (!$userId || !$coupon->allowedUsers->contains('id', $userId)) {
                throw new Exception('Este cupom é restrito a clientes específicos.', 422);
            }
        }

        // Check allowed payment methods
        if ($paymentMethod && !empty($coupon->allowed_payment_methods)) {
            if (!in_array($paymentMethod, $coupon->allowed_payment_methods, true)) {
                throw new Exception('Este cupom não é válido para o método de pagamento selecionado.', 422);
            }
        }

        // Calculate discount
        $discountValue = match ($coupon->type) {
            'percent' => ($orderTotal * (float) $coupon->value) / 100,
            'fixed'   => (float) $coupon->value,
            default   => 0.0,
        };

        // Apply max discount cap (for percentage coupons with a ceiling)
        if ($coupon->max_discount_value !== null) {
            $discountValue = min($discountValue, (float) $coupon->max_discount_value);
        }

        // Discount can never exceed the order total
        $discountValue = min($discountValue, $orderTotal);

        return new CouponDTO($coupon->code, (float) $discountValue, $coupon->type);
    }

    /**
     * Redeem a coupon safely, incrementing usage count inside a locked transaction.
     *
     * @throws Exception if the coupon became invalid between validation and redemption
     */
    public function redeem(Coupon $coupon): void
    {
        DB::transaction(function () use ($coupon) {
            $lockedCoupon = Coupon::where('id', $coupon->id)->lockForUpdate()->first();

            if (!$lockedCoupon->isValid()) {
                throw new Exception('O cupom se tornou inválido durante o processo.');
            }

            $lockedCoupon->increment('uses_count');
        });
    }
}
