"use client";

import { useState } from "react";
import AddToCartButton from "./AddToCartButton";

interface Variant {
  id: number;
  name: string;
  price: number;
  available_count: number;
  is_in_stock: boolean;
}

interface ProductPurchaseAreaProps {
  product: {
    id: number;
    slug: string;
    name: string;
    price: number;
    available_count: number;
    variants?: Variant[];
  };
}

export default function ProductPurchaseArea({ product }: ProductPurchaseAreaProps) {
  const hasVariants = product.variants && product.variants.length > 0;
  
  // Se não tem variantes, usa null. Se tem, começa com o primeiro ou nenhum?
  // Vamos auto-selecionar o primeiro variante por padrão se houver
  const [selectedVariantId, setSelectedVariantId] = useState<number | null>(
    hasVariants ? product.variants![0].id : null
  );

  const selectedVariant = hasVariants 
    ? product.variants!.find(v => v.id === selectedVariantId) 
    : null;

  const displayPrice = selectedVariant ? selectedVariant.price : product.price;
  const displayStock = selectedVariant ? selectedVariant.available_count : product.available_count;
  const isOutOfStock = displayStock === 0;

  return (
    <div>
      <div className="glass-panel p-6 rounded-2xl mb-8">
        <span className="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-brand-400 via-brand-500 to-brand-600 tracking-tight block mb-1 transition-all duration-300">
          R$ {Number(displayPrice).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}
        </span>
        <span className="text-sm text-text-tertiary font-medium">À vista no PIX com entrega imediata</span>
      </div>

      {hasVariants && (
        <div className="mb-8">
          <h3 className="text-sm font-semibold text-text-secondary uppercase tracking-wider mb-4">Escolha uma Opção</h3>
          <div className="grid grid-cols-2 gap-3">
            {product.variants!.map((variant) => {
              const isSelected = selectedVariantId === variant.id;
              const isVariantOutOfStock = variant.available_count === 0;
              
              return (
                <button
                  key={variant.id}
                  onClick={() => !isVariantOutOfStock && setSelectedVariantId(variant.id)}
                  disabled={isVariantOutOfStock}
                  className={`
                    relative px-4 py-3 rounded-xl border text-sm font-medium transition-all duration-200 text-left
                    ${isSelected 
                      ? "border-brand-500 bg-brand-500/10 text-white" 
                      : isVariantOutOfStock
                        ? "border-white/5 bg-white/5 text-text-tertiary cursor-not-allowed opacity-50"
                        : "border-white/10 bg-surface-800 text-text-secondary hover:border-white/20 hover:bg-surface-700 hover:text-white"
                    }
                  `}
                >
                  <div className="flex justify-between items-center mb-1">
                    <span className="font-semibold">{variant.name}</span>
                    {isSelected && (
                      <span className="w-2 h-2 rounded-full bg-brand-500 shadow-[0_0_10px_rgba(239,68,68,0.8)]" />
                    )}
                  </div>
                  <div className="flex justify-between items-center text-xs">
                    <span className={isSelected ? "text-brand-300" : "text-text-tertiary"}>
                      R$ {Number(variant.price).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}
                    </span>
                    {isVariantOutOfStock ? (
                      <span className="text-red-400 font-semibold">Esgotado</span>
                    ) : (
                      <span className={isSelected ? "text-brand-400" : "text-emerald-400/80"}>
                        {variant.available_count} {variant.available_count === 1 ? 'un' : 'uns'}
                      </span>
                    )}
                  </div>
                </button>
              );
            })}
          </div>
        </div>
      )}

      <div className="mt-auto pt-6 border-t border-white/10">
        <AddToCartButton 
          product={{
            id: product.id,
            slug: product.slug,
            name: product.name,
            price: displayPrice,
            variant_id: selectedVariant?.id,
            variant_name: selectedVariant?.name,
          }}
          isOutOfStock={isOutOfStock}
        />
      </div>
    </div>
  );
}
