"use client";

import { useCartStore } from "@/store/cart";

import { ShoppingCart } from "lucide-react";

interface AddToCartButtonProps {
  product: {
    id: number;
    slug: string;
    name: string;
    price: number;
    variant_id?: number;
    variant_name?: string;
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
      variant_id: product.variant_id,
      variant_name: product.variant_name,
    });
  };

  const handleBuyNow = () => {
    handleAddToCart();
    // futuramente, redirecionar direto pro checkout aqui
  };

  return (
    <div className="flex flex-col gap-3 w-full">
      <button
        disabled={isOutOfStock}
        onClick={handleBuyNow}
        aria-label={isOutOfStock ? "Produto esgotado" : `Comprar agora ${product.name}`}
        className={`w-full py-3.5 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2
          ${isOutOfStock 
            ? "bg-surface-800 text-text-tertiary cursor-not-allowed" 
            : "bg-brand-500 hover:bg-brand-400 text-white shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_20px_rgba(59,130,246,0.5)]"
          }`}
      >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
        {isOutOfStock ? "Fora de Estoque" : "Comprar agora"}
      </button>

      <button
        disabled={isOutOfStock}
        onClick={handleAddToCart}
        aria-label={isOutOfStock ? "Produto esgotado" : `Adicionar ${product.name} ao carrinho`}
        className={`w-full py-3.5 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 border
          ${isOutOfStock 
            ? "border-white/5 bg-transparent text-text-tertiary cursor-not-allowed" 
            : "border-white/10 bg-transparent text-white hover:bg-white/5"
          }`}
      >
        <ShoppingCart size={18} />
        Adicionar ao carrinho
      </button>
    </div>
  );
}
