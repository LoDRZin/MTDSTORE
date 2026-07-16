"use client";

import { useCartStore } from "@/store/cart";
import PremiumButton from "./ui/PremiumButton";
import { ShoppingCart } from "lucide-react";

interface AddToCartButtonProps {
  product: {
    id: number;
    slug: string;
    name: string;
    price: number;
  };
  isOutOfStock: boolean;
}

export default function AddToCartButton({ product, isOutOfStock }: AddToCartButtonProps) {
  const addItem = useCartStore((state) => state.addItem);

  const handleAddToCart = () => {
    addItem({
      id: product.id,
      slug: product.slug,
      name: product.name,
      price: Number(product.price),
    });
  };

  return (
    <PremiumButton
      variant="primary"
      size="lg"
      disabled={isOutOfStock}
      onClick={handleAddToCart}
      aria-label={isOutOfStock ? "Produto esgotado" : `Adicionar ${product.name} ao carrinho`}
    >
      <ShoppingCart size={20} />
      {isOutOfStock ? "Fora de Estoque" : "Adicionar ao Carrinho"}
    </PremiumButton>
  );
}
