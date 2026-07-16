"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams, usePathname } from "next/navigation";
import ProductCard from "./ProductCard";
import { Search, PackageX } from "lucide-react";
import { motion } from "framer-motion";

interface Product {
  id: number;
  slug: string;
  name: string;
  description: string;
  price: number;
  available_count: number;
}

interface ProductListProps {
  initialProducts: Product[];
}

export default function ProductList({ initialProducts }: ProductListProps) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  
  const [search, setSearch] = useState(searchParams.get("search") ?? "");

  // Sync search input to URL with debounce
  useEffect(() => {
    const timer = setTimeout(() => {
      const params = new URLSearchParams(searchParams.toString());
      if (search) {
        params.set("search", search);
      } else {
        params.delete("search");
      }
      
      const query = params.toString();
      router.push(query ? `${pathname}?${query}` : pathname, { scroll: false });
    }, 400);

    return () => clearTimeout(timer);
  }, [search, pathname, router, searchParams]);

  return (
    <div className="flex-1 min-w-0">
      {/* Search */}
      <div className="relative mb-8">
        <span className="absolute left-4 top-1/2 -translate-y-1/2 text-text-tertiary" aria-hidden="true">
          <Search size={18} />
        </span>
        <input
          id="product-search"
          type="search"
          className="w-full bg-surface-900 border border-white/10 rounded-xl py-3.5 pl-12 pr-4 text-white placeholder:text-text-tertiary focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all shadow-sm font-inter"
          placeholder="Buscar produtos, jogos, licenças..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          aria-label="Buscar produtos"
        />
      </div>

      {/* Section Header */}
      <div className="flex items-center justify-between mb-6 pb-4 border-b border-white/5">
        <h2 className="flex items-center gap-3 font-display text-2xl font-semibold text-white">
          <span className="w-1 h-6 bg-brand-600 rounded-full" aria-hidden="true" />
          Resultados
          <span className="ml-2 text-sm font-inter font-medium px-2.5 py-1 rounded-md bg-surface-800 text-text-tertiary border border-white/5">
            {initialProducts.length} produto
            {initialProducts.length !== 1 ? "s" : ""}
          </span>
        </h2>
      </div>

      {/* Grid */}
      {initialProducts.length === 0 ? (
        <motion.div 
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          className="flex flex-col items-center justify-center py-20 px-4 text-center border border-dashed border-white/10 rounded-2xl bg-white/[0.01]" 
          role="alert" 
          aria-live="polite"
        >
          <div className="w-16 h-16 rounded-2xl bg-surface-800 border border-white/5 flex items-center justify-center mb-6">
            <PackageX size={32} className="text-text-tertiary" />
          </div>
          <h3 className="font-display text-xl font-semibold text-white mb-2">
            Nenhum produto encontrado
          </h3>
          <p className="text-text-tertiary max-w-sm mb-6">
            {search
              ? `Nenhum resultado para "${search}" ou filtros aplicados.`
              : "Não há produtos que correspondam a esses filtros."}
          </p>
          <button
            className="px-5 py-2.5 rounded-lg font-medium text-sm bg-surface-800 text-white border border-white/10 hover:bg-surface-700 transition-colors"
            onClick={() => router.push(pathname, { scroll: false })}
          >
            Limpar todos os filtros
          </button>
        </motion.div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" role="list" aria-label="Lista de produtos">
          {initialProducts.map((product, i) => (
            <div key={product.id} role="listitem" className="h-full">
              <ProductCard product={product} index={i} />
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
