<?php

namespace App\Services;

use App\DTOs\CouponDTO;
use App\Models\Coupon;
use Exception;
use Illuminate\Support\Facades\DB;

class CouponService
{
    public function validate(
        string $code,
        float $orderTotal,
        array $cartProductIds = [],
        ?int $userId = null,
        ?string $paymentMethod = null,
    ): CouponDTO {
        $coupon = Coupon::query()
            ->where('code', strtoupper(trim($code)))
            ->with(['products:id', 'categories:id', 'allowedUsers:id'])
            ->first();

        if (! $coupon) {
            throw new Exception('Cupom nÃ£o encontrado.', 404);
        }

        if (! $coupon->isValid()) {
            throw new Exception('Cupom invÃ¡lido, expirado ou esgotado.', 422);
        }

        if ($coupon->min_purchase_amount !== null && $orderTotal < (float) $coupon->min_purchase_amount) {
            throw new Exception('Este cupom requer um valor mÃ­nimo de compra.', 422);
        }

        if ($cartProductIds !== [] && ! $coupon->isApplicableToProducts($cartProductIds)) {
            throw new Exception('Este cupom nÃ£o Ã© vÃ¡lido para os produtos selecionados.', 422);
        }

        if ($cartProductIds !== [] && ! $coupon->isApplicableToCategories($cartProductIds)) {
            throw new Exception('Este cupom nÃ£o Ã© vÃ¡lido para a categoria dos produtos selecionados.', 422);
        }

        if ($coupon->allowedUsers->isNotEmpty() && ! $coupon->allowedUsers->contains('id', $userId)) {
            throw new Exception('Este cupom Ã© restrito a clientes especÃ­ficos.', 422);
        }

        if ($coupon->allowed_payment_methods !== null && $coupon->allowed_payment_methods !== []) {
            if (! $paymentMethod || ! in_array($paymentMethod, $coupon->allowed_payment_methods, true)) {
                throw new Exception('Este cupom nÃ£o Ã© vÃ¡lido para o mÃ©todo de pagamento selecionado.', 422);
            }
        }

        $discount = $coupon->type === 'percent'
            ? ($orderTotal * (float) $coupon->value) / 100
            : (float) $coupon->value;

        if ($coupon->max_discount_value !== null) {
            $discount = min($discount, (float) $coupon->max_discount_value);
        }

        return new CouponDTO($coupon->code, min($discount, $orderTotal), $coupon->type);
    }

    /** Redeem only after the payment webhook has confirmed the order. */
    public function redeem(Coupon $coupon): void
    {
        DB::transaction(function () use ($coupon): void {
            $lockedCoupon = Coupon::query()->lockForUpdate()->findOrFail($coupon->id);

            if (! $lockedCoupon->isValid()) {
                throw new Exception('O cupom se tornou invÃ¡lido durante o pagamento.');
            }

            $lockedCoupon->increment('uses_count');
        });
    }
}
