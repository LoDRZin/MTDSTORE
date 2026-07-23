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
    public function calculateTotal(
        array $cartItems,
        ?string $couponCode = null,
        ?int $userId = null,
        ?string $paymentMethod = null,
    ): CartTotalDTO
    {
        $subtotal = 0.0;
        $errors = [];
        $cartProductIds = [];

        // Verifica cada item
        foreach ($cartItems as $item) {
            $productId = $item['product_id'];
            $variantId = $item['variant_id'] ?? null;
            $quantity = $item['quantity'] ?? 1;

            $product = Product::with('variants')->find($productId);

            if (!$product || $product->status !== 'active') {
                $errors[] = "Produto #{$productId} não encontrado ou inativo.";
                continue;
            }

            $price = $product->price;
            $itemName = $product->name;

            if ($product->variants->isNotEmpty() && !$variantId) {
                $errors[] = "Selecione uma variação para o produto {$product->name}.";
                continue;
            }

            if ($variantId) {
                $variant = $product->variants->firstWhere('id', $variantId);
                if (!$variant) {
                    $errors[] = "Variação #{$variantId} do produto {$product->name} não encontrada.";
                    continue;
                }
                $price = $variant->price;
                $itemName .= ' (' . $variant->name . ')';
            }

            // Checa estoque
            $availableCount = $this->inventoryService->getAvailableCount($productId, $variantId);
            if ($availableCount < $quantity) {
                $errors[] = "Estoque insuficiente para {$itemName}. (Disponível: {$availableCount})";
                // Mesmo sem estoque, para calcular o subtotal a gente soma? Geralmente não.
                continue;
            }

            $subtotal += ($price * $quantity);
            $cartProductIds[] = $product->id;
        }

        $discount = 0.0;
        $appliedCoupon = null;

        // Tenta aplicar cupom se não houver erros no carrinho
        if (empty($errors) && $couponCode) {
            try {
                $couponDto = $this->couponService->validate(
                    $couponCode,
                    $subtotal,
                    array_values(array_unique($cartProductIds)),
                    $userId,
                    $paymentMethod,
                );
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
