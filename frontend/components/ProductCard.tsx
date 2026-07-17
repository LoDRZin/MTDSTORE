"use client";

import Link from "next/link";
import Image from "next/image";
import { useCartStore } from "@/store/cart";
import { ShoppingCart, Package, TrendingUp } from "lucide-react";
import { motion } from "framer-motion";

interface Product {
  id: number;
  slug: string;
  name: string;
  description: string;
  price: number;
  available_count: number;
  image_url?: string;
  has_variants?: boolean;
}

interface ProductCardProps {
  product: Product;
  index?: number;
}

export default function ProductCard({ product, index = 0 }: ProductCardProps) {
  const isOutOfStock = product.available_count === 0;
  const isLowStock = product.available_count > 0 && product.available_count <= 5;
  const addItem = useCartStore((state) => state.addItem);

  const handleAddToCart = () => {
    if (isOutOfStock) return;
    addItem({
      id: product.id,
      slug: product.slug,
      name: product.name,
      price: Number(product.price),
    });
  };

  const formatPrice = (v: number) =>
    v.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

  const icons = ["🎮", "💻", "🔑", "⚡", "🛡️", "🎯"];
  const icon = icons[product.id % icons.length];

  return (
    <motion.article
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5, delay: index * 0.05 }}
      whileHover={{ y: -8, scale: 1.02 }}
      className={`bg-surface-900 border rounded-2xl overflow-hidden group relative flex flex-col h-full ${
        isOutOfStock ? "opacity-60 border-white/5" : "border-white/10"
      }`}
    >
      <Link href={`/produtos/${product.slug}`} className="flex flex-col h-full absolute inset-0 z-0" aria-label={`Ver detalhes de ${product.name}`} />
      
      {/* Shimmer border effect on hover */}
      {!isOutOfStock && (
        <div className="absolute inset-0 rounded-2xl bg-gradient-to-tr from-brand-600/0 via-brand-600/0 to-brand-600/0 group-hover:via-brand-600/20 transition-all duration-500 pointer-events-none" />
      )}

      {/* Image Area */}
      <div className="relative h-48 bg-surface-800 overflow-hidden shrink-0 flex items-center justify-center pointer-events-none">
        {/* Product Image */}
        {product.image_url ? (
          <Image
            src={product.image_url}
            alt={product.name}
            fill
            className="object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-700"
            sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
          />
        ) : (
          <motion.span 
            whileHover={{ scale: 1.1 }}
            transition={{ duration: 0.6, ease: "easeOut" }}
            className="text-6xl filter grayscale opacity-70"
          >
            {icon}
          </motion.span>
        )}

        <div className="absolute inset-0 bg-gradient-to-t from-surface-900 via-transparent to-transparent pointer-events-none" />

        {/* Badges */}

        <span className={`absolute top-3 right-3 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 backdrop-blur-md border ${
          isOutOfStock 
            ? "bg-surface-800/80 text-text-tertiary border-white/5" 
            : "bg-brand-500/10 text-brand-400 border-brand-500/20"
        }`}>
          {!isOutOfStock && (
            <span className="w-1.5 h-1.5 bg-brand-400 rounded-full animate-pulse" aria-hidden="true" />
          )}
          {isOutOfStock ? "Esgotado" : "Online"}
        </span>
      </div>

      {/* Content */}
      <div className="p-5 flex flex-col flex-1 z-10 pointer-events-none">
        <h3 className="font-display font-semibold text-lg text-white mb-auto line-clamp-2">
          {product.name}
        </h3>

        <div className="mt-4 mb-4">
          <p className="text-2xl font-bold text-white tracking-tight">
            {product.has_variants && <span className="text-sm font-normal text-text-tertiary mr-1">A partir de</span>}
            {formatPrice(product.price)}
          </p>
          <p className="text-xs text-text-tertiary font-medium">À vista no PIX</p>
        </div>

        {!isOutOfStock && (
          <p className={`text-xs flex items-center gap-1.5 mb-5 ${
            isLowStock ? "text-orange-400 font-medium" : "text-text-tertiary"
          }`}>
            {isLowStock ? (
              <>
                <TrendingUp size={14} /> Restam apenas {product.available_count} unidades!
              </>
            ) : (
              <>
                <Package size={14} /> {product.available_count} unidades disponíveis
              </>
            )}
          </p>
        )}

        {/* Actions */}
        <div className="flex gap-2 mt-auto pointer-events-auto">
          <motion.button
            whileTap={!isOutOfStock ? { scale: 0.95 } : undefined}
            disabled={isOutOfStock}
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              handleAddToCart();
            }}
            className={`flex-1 flex items-center justify-center gap-1.5 text-white text-sm font-semibold py-2.5 rounded-xl transition-all shadow-brand-sm border border-brand-500/30 ${
              isOutOfStock 
                ? "bg-surface-800 text-text-tertiary border-white/5 cursor-not-allowed shadow-none" 
                : "bg-gradient-to-r from-brand-600 to-brand-800 hover:from-brand-500 hover:to-brand-700 hover:shadow-brand-md"
            }`}
          >
            <ShoppingCart size={16} /> Comprar Direto
          </motion.button>
        </div>
      </div>
    </motion.article>
  );
}
