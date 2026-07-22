"use client";

import { useEffect, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter, useSearchParams, usePathname } from "next/navigation";
import ProductCard from "./ProductCard";
import { Search, PackageX, ChevronLeft, LayoutGrid, ChevronRight, ChevronLeft as IconChevronLeft } from "lucide-react";
import { motion } from "framer-motion";

interface Category {
  id: number;
  name: string;
  slug: string;
  image_url?: string | null;
}

interface Product {
  id: number;
  slug: string;
  name: string;
  description: string;
  price: number;
  available_count: number;
  image_url?: string;
}

interface PaginationMeta {
  current_page: number;
  last_page: number;
  total: number;
}

interface ProductListProps {
  initialProducts: Product[];
  categories: Category[];
  meta: PaginationMeta | null;
}

export default function ProductList({ initialProducts, categories, meta }: ProductListProps) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  
  const searchParam = searchParams.get("search") ?? "";
  const categoryParam = searchParams.get("category");
  const pageParam = parseInt(searchParams.get("page") ?? "1", 10);
  
  const [search, setSearch] = useState(searchParam);

  // Sync search input to URL with debounce
  useEffect(() => {
    const timer = setTimeout(() => {
      const params = new URLSearchParams(searchParams.toString());
      if (search) {
        params.set("search", search);
      } else {
        params.delete("search");
      }
      
      // Sempre que pesquisa, volta pra página 1
      if (search !== searchParam) {
        params.delete("page");
      }
      
      const query = params.toString();
      router.push(query ? `${pathname}?${query}` : pathname, { scroll: false });
    }, 400);

    return () => clearTimeout(timer);
  }, [search, pathname, router, searchParams, searchParam]);

  const selectCategory = (slug: string) => {
    const params = new URLSearchParams(searchParams.toString());
    params.set("category", slug);
    params.delete("page"); // Reset page when changing category
    router.push(`${pathname}?${params.toString()}`, { scroll: false });
  };

  const clearCategory = () => {
    const params = new URLSearchParams(searchParams.toString());
    params.delete("category");
    params.delete("page");
    router.push(params.toString() ? `${pathname}?${params.toString()}` : pathname, { scroll: false });
  };

  const goToPage = (page: number) => {
    const params = new URLSearchParams(searchParams.toString());
    params.set("page", page.toString());
    router.push(`${pathname}?${params.toString()}`, { scroll: true });
  };

  // Se o usuário digitou uma busca ou filtrou algo (como categoria), mostramos a grade de produtos.
  // Caso contrário, mostramos a grade de categorias.
  const isCategoryView = !searchParam && !categoryParam && !searchParams.get("in_stock") && !searchParams.get("min_price");

  return (
    <div className="flex-1 min-w-0 min-h-[100vh]">
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

      {isCategoryView ? (
        // CATEGORY VIEW
        <>
          <div className="flex items-center justify-between mb-6 pb-4 border-b border-white/5">
            <h2 className="flex items-center gap-3 font-display text-2xl font-semibold text-white">
              <span className="w-1 h-6 bg-brand-600 rounded-full" aria-hidden="true" />
              Categorias
            </h2>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {categories.map((cat) => {
              const params = new URLSearchParams(searchParams.toString());
              params.set("category", cat.slug);
              params.delete("page");
              const href = `${pathname}?${params.toString()}`;
              return (
                <motion.div
                  key={cat.id}
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                >
                  <Link
                    href={href}
                    scroll={false}
                    className="flex flex-col items-center justify-center p-8 bg-surface-900/50 border border-white/10 rounded-2xl hover:border-brand-500/50 hover:bg-surface-800 transition-all group h-full w-full"
                  >
                    <div className="relative w-16 h-16 rounded-full bg-brand-500/10 flex items-center justify-center mb-4 group-hover:bg-brand-500/20 transition-colors overflow-hidden">
                      {cat.image_url ? (
                        <Image src={cat.image_url} alt={cat.name} fill className="object-cover" sizes="64px" />
                      ) : (
                        <LayoutGrid size={28} className="text-brand-500" />
                      )}
                    </div>
                    <h3 className="text-xl font-display font-semibold text-white group-hover:text-brand-400 transition-colors">
                      {cat.name}
                    </h3>
                  </Link>
                </motion.div>
              );
            })}
          </div>
        </>
      ) : (
        // PRODUCT VIEW
        <>
          {/* Section Header */}
          <div className="flex flex-wrap gap-4 items-center justify-between mb-6 pb-4 border-b border-white/5">
            <div className="flex items-center gap-4">
              {categoryParam && (
                <button 
                  onClick={clearCategory}
                  className="flex items-center gap-2 text-sm text-text-tertiary hover:text-white bg-surface-800 px-3 py-1.5 rounded-lg border border-white/10 transition-colors"
                >
                  <ChevronLeft size={16} /> Voltar
                </button>
              )}
              <h2 className="flex items-center gap-3 font-display text-2xl font-semibold text-white">
                <span className="w-1 h-6 bg-brand-600 rounded-full" aria-hidden="true" />
                Resultados
                {meta && (
                  <span className="ml-2 text-sm font-inter font-medium px-2.5 py-1 rounded-md bg-surface-800 text-text-tertiary border border-white/5">
                    {meta.total} produto{meta.total !== 1 ? "s" : ""}
                  </span>
                )}
              </h2>
            </div>
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
                {searchParam
                  ? `Nenhum resultado para "${searchParam}" ou filtros aplicados.`
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
            <>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12" role="list" aria-label="Lista de produtos">
                {initialProducts.map((product, i) => (
                  <div key={product.id} role="listitem" className="h-full">
                    <ProductCard product={product} index={i} />
                  </div>
                ))}
              </div>

              {/* Pagination */}
              {meta && meta.last_page > 1 && (
                <div className="flex items-center justify-center gap-2 mt-8">
                  <button
                    onClick={() => goToPage(pageParam - 1)}
                    disabled={pageParam <= 1}
                    className="p-2 rounded-lg bg-surface-800 text-white border border-white/10 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-surface-700 transition-colors"
                  >
                    <IconChevronLeft size={20} />
                  </button>
                  
                  {Array.from({ length: meta.last_page }, (_, i) => i + 1).map((p) => (
                    <button
                      key={p}
                      onClick={() => goToPage(p)}
                      className={`w-10 h-10 rounded-lg font-medium text-sm border transition-colors ${
                        pageParam === p 
                          ? "bg-brand-600 text-white border-brand-500" 
                          : "bg-surface-800 text-text-tertiary border-white/10 hover:bg-surface-700 hover:text-white"
                      }`}
                    >
                      {p}
                    </button>
                  ))}

                  <button
                    onClick={() => goToPage(pageParam + 1)}
                    disabled={pageParam >= meta.last_page}
                    className="p-2 rounded-lg bg-surface-800 text-white border border-white/10 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-surface-700 transition-colors"
                  >
                    <ChevronRight size={20} />
                  </button>
                </div>
              )}
            </>
          )}
        </>
      )}
    </div>
  );
}
