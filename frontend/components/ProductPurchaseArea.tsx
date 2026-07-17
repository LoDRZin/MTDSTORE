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
  
  const [selectedVariantId, setSelectedVariantId] = useState<number | null>(
    hasVariants ? product.variants![0].id : null
  );
  const [searchQuery, setSearchQuery] = useState("");

  const selectedVariant = hasVariants 
    ? product.variants!.find(v => v.id === selectedVariantId) 
    : null;

  const displayPrice = selectedVariant ? selectedVariant.price : product.price;
  const displayStock = selectedVariant ? selectedVariant.available_count : product.available_count;
  const isOutOfStock = displayStock === 0;

  // Fake sales for UI (can be replaced by real DB data later)
  const fakeSales = (product.id * 7) % 50 + 10;

  const filteredVariants = hasVariants 
    ? product.variants!.filter(v => v.name.toLowerCase().includes(searchQuery.toLowerCase()))
    : [];

  const maxPrice = hasVariants ? Math.max(...product.variants!.map(v => v.price)) : product.price;
  const minPrice = hasVariants ? Math.min(...product.variants!.map(v => v.price)) : product.price;

  return (
    <div className="flex flex-col h-full">
      {/* Header Info */}
      <div className="mb-4">
        <div className="flex items-center gap-2 text-sm text-text-tertiary mb-1">
          <span>+{fakeSales} Vendido(s)</span>
          <span>•</span>
          <span>+{product.available_count} Restante(s)</span>
        </div>
        
        <h1 className="text-2xl font-display font-bold text-white mb-3">
          {selectedVariant && hasVariants ? `${product.name} - ${selectedVariant.name}` : product.name}
        </h1>

        {/* Pricing */}
        <div className="mb-6">
          <span className="text-3xl font-bold text-white tracking-tight block">
            {hasVariants && !selectedVariantId
              ? `R$ ${minPrice.toLocaleString("pt-BR", { minimumFractionDigits: 2 })} — R$ ${maxPrice.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`
              : `R$ ${Number(displayPrice).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`
            }
          </span>
          {hasVariants && !selectedVariantId && (
            <span className="text-xs text-text-tertiary mt-1 block">Selecione um item para ver o preço exato</span>
          )}
        </div>
      </div>

      {hasVariants && (
        <div className="flex flex-col gap-3 mb-6 flex-1">
          {/* Search input for variants */}
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-text-tertiary"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
            <input 
              type="text" 
              placeholder="Buscar item..." 
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full bg-surface-800 border border-white/10 rounded-xl py-2.5 pl-10 pr-4 text-sm text-white placeholder-text-tertiary focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all"
            />
          </div>

          <div className="flex flex-col gap-2 max-h-[300px] overflow-y-auto pr-1 custom-scrollbar">
            {filteredVariants.map((variant) => {
              const isSelected = selectedVariantId === variant.id;
              const isVariantOutOfStock = variant.available_count === 0;
              
              return (
                <button
                  key={variant.id}
                  onClick={() => !isVariantOutOfStock && setSelectedVariantId(variant.id)}
                  disabled={isVariantOutOfStock}
                  className={`
                    relative flex items-center p-3 rounded-xl border transition-all duration-200 text-left group
                    ${isSelected 
                      ? "border-brand-500 bg-brand-500/10 shadow-[inset_4px_0_0_0_#3b82f6] shadow-brand-500" 
                      : isVariantOutOfStock
                        ? "border-white/5 bg-white/5 opacity-50 cursor-not-allowed"
                        : "border-white/5 bg-surface-800 hover:border-white/20 hover:bg-surface-700"
                    }
                  `}
                >
                  <div className="w-12 h-10 bg-surface-950 rounded border border-white/10 flex items-center justify-center shrink-0 mr-3 overflow-hidden">
                    {/* Placeholder for variant mini image */}
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-text-tertiary opacity-50"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="text-sm font-bold text-white truncate">{variant.name}</div>
                    <div className="text-[10px] font-medium text-emerald-400">({variant.available_count} em estoque)</div>
                  </div>
                  <div className="text-right shrink-0 ml-2">
                    <span className="text-sm font-bold text-white">R$ {Number(variant.price).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}</span>
                  </div>
                </button>
              );
            })}
            
            {filteredVariants.length === 0 && (
              <div className="text-center py-4 text-sm text-text-tertiary">Nenhuma variação encontrada.</div>
            )}
          </div>
        </div>
      )}

      <div className="mt-auto flex flex-col gap-2 pt-4">
        {/* Adicionar ao carrinho renderiza os botões reais (precisamos ajustar isso depois) */}
        <AddToCartButton 
          product={{
            id: product.id,
            slug: product.slug,
            name: product.name,
            price: displayPrice,
            variant_id: selectedVariant?.id,
            variant_name: selectedVariant?.name,
          }}
          isOutOfStock={isOutOfStock || !!(hasVariants && !selectedVariantId)}
        />
      </div>
    </div>
  );
}
