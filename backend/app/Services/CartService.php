<?php

namespace App\Services;

use App\Models\Product;
use App\DTOs\CartTotalDTO;

class CartService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected CouponService $couponService
    ) {}

    /**
     * Calcula o total do carrinho, validando produtos, estoque e cupom.
     *
     * @param array $cartItems Ex: [['product_id' => 1, 'quantity' => 2], ...]
     * @param string|null $couponCode
     * @return CartTotalDTO
     */
    public function calculateTotal(array $cartItems, ?string $couponCode = null): CartTotalDTO
    {
        $subtotal = 0.0;
        $errors = [];

        // Verifica cada item
        foreach ($cartItems as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];

            $product = Product::find($productId);

            if (!$product || $product->status !== 'active') {
                $errors[] = "Produto #{$productId} não encontrado ou inativo.";
                continue;
            }

            // Checa estoque
            $availableCount = $this->inventoryService->getAvailableCount($productId);
            if ($availableCount < $quantity) {
                $errors[] = "Estoque insuficiente para o produto {$product->name}. (Disponível: {$availableCount})";
                // Mesmo sem estoque, para calcular o subtotal a gente soma? Geralmente não.
                continue;
            }

            $subtotal += ($product->price * $quantity);
        }

        $discount = 0.0;
        $appliedCoupon = null;

        // Tenta aplicar cupom se não houver erros no carrinho
        if (empty($errors) && $couponCode) {
            try {
                $couponDto = $this->couponService->validate($couponCode, $subtotal);
                $discount = $couponDto->discountValue;
                $appliedCoupon = $couponDto->code;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $total = max(0, $subtotal - $discount);

        return new CartTotalDTO(
            subtotal: $subtotal,
            discount: $discount,
            total: $total,
            couponCode: $appliedCoupon,
            errors: $errors
        );
    }
}
