<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductStockItem;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected CouponService $couponService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Process checkout for a customer.
     *
     * @param User $customer
     * @param array $cartItems [['product_id' => 1, 'quantity' => 2]]
     * @param string|null $couponCode
     * @param string $gateway
     * @return Order
     * @throws Exception
     */
    public function process(User $customer, array $cartItems, ?string $couponCode, string $gateway): Order
    {
        return DB::transaction(function () use ($customer, $cartItems, $couponCode, $gateway) {
            // 1. Calcular total e validar
            $cartTotalDto = $this->cartService->calculateTotal($cartItems, $couponCode);

            if (!empty($cartTotalDto->errors)) {
                throw new Exception("Erro no carrinho: " . implode(" | ", $cartTotalDto->errors));
            }

            // 2. Criar registro do pedido
            $coupon = $couponCode ? Coupon::where('code', $couponCode)->first() : null;

            $order = Order::create([
                'uuid' => (string) Str::uuid(),
                'customer_id' => $customer->id,
                'status' => 'pending',
                'total' => $cartTotalDto->total,
                'coupon_id' => $coupon?->id,
                // O gateway será guardado no payment logic ou em external_reference depois.
            ]);

            // 3. Processar itens (Apenas cria os OrderItems, sem alocar chaves ainda)
            foreach ($cartItems as $item) {
                $productId = $item['product_id'];
                $variantId = $item['variant_id'] ?? null;
                $quantity = $item['quantity'] ?? 1;

                $product = \App\Models\Product::with('variants')->find($productId);

                if (!$product) {
                    throw new Exception("Produto #{$productId} não encontrado.");
                }

                $available = $this->inventoryService->getAvailableCount($productId, $variantId);
                if ($available < $quantity) {
                    throw new Exception("Estoque insuficiente para o produto {$product->name}. Disponível: {$available}, Solicitado: {$quantity}");
                }

                $price = $product->price;
                if ($variantId) {
                    $variant = $product->variants->firstWhere('id', $variantId);
                    if ($variant) {
                        $price = $variant->price;
                    }
                }

                // Cria 1 OrderItem para cada quantidade solicitada (já que a relação chave -> item é 1:1)
                for ($i = 0; $i < $quantity; $i++) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'variant_id' => $variantId,
                        'unit_price' => $price,
                        'stock_item_id' => null, // Será preenchido no webhook
                    ]);
                }
            }

            // 5. Cupom já associado ao pedido. Será redimido no webhook.

            // 6. Aqui seria o disparo do Job de Pagamento
            // dispatch(new \App\Jobs\ProcessPaymentJob($order, $gateway));

            return $order;
        });
    }
}
