"use client";

import { useRouter, useSearchParams, usePathname } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { ChevronDown, ChevronRight, SlidersHorizontal, X, Package, Tag } from "lucide-react";
import { AnimatePresence, motion } from "framer-motion";

interface Category {
  id: number;
  name: string;
  slug: string;
  image_url: string | null;
  children?: Category[];
}

interface FilterState {
  category: string;
  min_price: string;
  max_price: string;
  in_stock: boolean;
}

interface FilterSidebarProps {
  categories: Category[];
}

function CategoryNode({
  cat,
  activeSlug,
  onSelect,
  depth = 0,
}: {
  cat: Category;
  activeSlug: string;
  onSelect: (slug: string) => void;
  depth?: number;
}) {
  const [open, setOpen] = useState(false);
  const hasChildren = cat.children && cat.children.length > 0;
  const isActive = activeSlug === cat.slug;

  return (
    <li className="flex flex-col">
      <div className="flex items-center gap-1.5" style={{ paddingLeft: `${depth * 14}px` }}>
        {hasChildren ? (
          <button
            onClick={() => setOpen((v) => !v)}
            className="p-0.5 text-text-tertiary hover:text-white transition-colors flex items-center justify-center shrink-0"
            aria-label={open ? "Fechar subcategorias" : "Abrir subcategorias"}
          >
            {open ? <ChevronDown size={14} /> : <ChevronRight size={14} />}
          </button>
        ) : (
          <span className="w-[18px] shrink-0" />
        )}

        <button
          onClick={() => onSelect(isActive ? "" : cat.slug)}
          className={`flex-1 text-left px-2.5 py-1.5 rounded-lg text-sm transition-all duration-200 ${
            isActive
              ? "bg-brand-600/15 text-brand-500 font-semibold border border-brand-500/20"
              : "text-text-secondary hover:text-white hover:bg-white/5 border border-transparent font-medium"
          }`}
        >
          {cat.name}
        </button>
      </div>

      <AnimatePresence>
        {hasChildren && open && (
          <motion.ul
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: "auto", opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="flex flex-col gap-1 mt-1 overflow-hidden"
          >
            {cat.children!.map((child) => (
              <CategoryNode
                key={child.id}
                cat={child}
                activeSlug={activeSlug}
                onSelect={onSelect}
                depth={depth + 1}
              />
            ))}
          </motion.ul>
        )}
      </AnimatePresence>
    </li>
  );
}

export default function FilterSidebar({ categories }: FilterSidebarProps) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [mobileOpen, setMobileOpen] = useState(false);

  const [filters, setFilters] = useState<FilterState>({
    category: searchParams.get("category") ?? "",
    min_price: searchParams.get("min_price") ?? "",
    max_price: searchParams.get("max_price") ?? "",
    in_stock: searchParams.get("in_stock") === "1",
  });

  // Atualizar URL (usado para cliques instantâneos)
  const applyFiltersToUrl = useCallback((newFilters: FilterState) => {
    const params = new URLSearchParams(searchParams.toString());
    
    if (newFilters.category) params.set("category", newFilters.category);
    else params.delete("category");
    
    if (newFilters.min_price) params.set("min_price", newFilters.min_price);
    else params.delete("min_price");
    
    if (newFilters.max_price) params.set("max_price", newFilters.max_price);
    else params.delete("max_price");
    
    if (newFilters.in_stock) params.set("in_stock", "1");
    else params.delete("in_stock");

    const query = params.toString();
    router.push(query ? `${pathname}?${query}` : pathname, { scroll: false });
  }, [pathname, router, searchParams]);

  // Sync apenas para preços com debounce
  useEffect(() => {
    const timer = setTimeout(() => {
      // Verifica se o preço mudou em relação à URL antes de fazer push
      const urlMin = searchParams.get("min_price") ?? "";
      const urlMax = searchParams.get("max_price") ?? "";
      if (filters.min_price !== urlMin || filters.max_price !== urlMax) {
        applyFiltersToUrl(filters);
      }
    }, 400);
    return () => clearTimeout(timer);
  }, [filters, applyFiltersToUrl, searchParams]);

  const updateCategory = (slug: string) => {
    const newFilters = { ...filters, category: slug };
    setFilters(newFilters);
    applyFiltersToUrl(newFilters);
  };

  const updateInStock = (checked: boolean) => {
    const newFilters = { ...filters, in_stock: checked };
    setFilters(newFilters);
    applyFiltersToUrl(newFilters);
  };

  const clearAll = useCallback(() => {
    const empty = { category: "", min_price: "", max_price: "", in_stock: false };
    setFilters(empty);
    applyFiltersToUrl(empty);
  }, [applyFiltersToUrl]);

  const hasActiveFilters =
    filters.category || filters.min_price || filters.max_price || filters.in_stock;

  const FilterPanel = () => (
    <aside className="bg-surface-900 border border-white/5 rounded-2xl p-5 flex flex-col gap-6 shadow-card" aria-label="Filtros de produtos">
      {/* Header */}
      <div className="flex items-center justify-between border-b border-white/5 pb-4">
        <span className="flex items-center gap-2 text-white font-display font-semibold">
          <SlidersHorizontal size={16} className="text-brand-600" />
          Filtros
        </span>
        {hasActiveFilters && (
          <button
            onClick={clearAll}
            className="flex items-center gap-1 text-xs text-text-tertiary hover:text-brand-500 transition-colors"
            aria-label="Limpar todos os filtros"
          >
            <X size={12} /> Limpar
          </button>
        )}
      </div>

      {/* Estoque */}
      <div className="flex flex-col gap-3">
        <label className="flex items-center justify-between cursor-pointer group">
          <span className="flex items-center gap-2 text-sm text-text-secondary group-hover:text-white transition-colors font-medium">
            <Package size={15} className="text-text-tertiary group-hover:text-brand-500 transition-colors" />
            Apenas em estoque
          </span>
          <div className="relative inline-flex items-center">
            <input
              type="checkbox"
              checked={filters.in_stock}
              onChange={(e) => updateInStock(e.target.checked)}
              className="peer sr-only"
              aria-label="Apenas produtos em estoque"
            />
            <div className="w-9 h-5 bg-surface-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-600 transition-colors"></div>
          </div>
        </label>
      </div>

      {/* Preço */}
      <div className="flex flex-col gap-3">
        <p className="text-xs font-bold text-text-tertiary uppercase tracking-wider">Faixa de Preço</p>
        <div className="flex items-center gap-2">
          <input
            type="number"
            className="w-full bg-surface-950 border border-white/10 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors placeholder:text-surface-600"
            placeholder="Mín"
            min={0}
            value={filters.min_price}
            onChange={(e) => setFilters((f) => ({ ...f, min_price: e.target.value }))}
            aria-label="Preço mínimo"
          />
          <span className="text-text-tertiary text-sm">—</span>
          <input
            type="number"
            className="w-full bg-surface-950 border border-white/10 rounded-lg py-2 px-3 text-sm text-white focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors placeholder:text-surface-600"
            placeholder="Máx"
            min={0}
            value={filters.max_price}
            onChange={(e) => setFilters((f) => ({ ...f, max_price: e.target.value }))}
            aria-label="Preço máximo"
          />
        </div>
      </div>

      {/* Categorias */}
      {categories.length > 0 && (
        <div className="flex flex-col gap-3">
          <p className="text-xs font-bold text-text-tertiary uppercase tracking-wider flex items-center gap-1.5">
            <Tag size={13} />
            Categorias
          </p>
          <ul className="flex flex-col gap-1">
            {categories.map((cat) => (
              <CategoryNode
                key={cat.id}
                cat={cat}
                activeSlug={filters.category}
                onSelect={(slug) => updateCategory(slug)}
              />
            ))}
          </ul>
        </div>
      )}
    </aside>
  );

  return (
    <>
      {/* Mobile toggle button */}
      <button
        className="lg:hidden flex items-center justify-center gap-2 w-full py-3 bg-surface-900 border border-white/5 rounded-xl text-white font-semibold mb-6 relative hover:bg-surface-800 transition-colors"
        onClick={() => setMobileOpen(true)}
        aria-expanded={mobileOpen}
        aria-label="Abrir filtros"
      >
        <SlidersHorizontal size={16} />
        Filtrar Resultados
        {hasActiveFilters && (
          <span className="absolute top-3 right-3 w-2 h-2 bg-brand-500 rounded-full shadow-[0_0_8px_rgba(220,38,38,0.8)]" aria-hidden="true" />
        )}
      </button>

      {/* Mobile drawer */}
      <AnimatePresence>
        {mobileOpen && (
          <>
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 bg-black/60 backdrop-blur-sm z-[150] lg:hidden" 
              onClick={() => setMobileOpen(false)} 
              aria-hidden="true" 
            />
            <motion.div 
              initial={{ y: "100%" }}
              animate={{ y: 0 }}
              exit={{ y: "100%" }}
              transition={{ type: "spring", damping: 25, stiffness: 200 }}
              className="fixed bottom-0 left-0 w-full max-h-[85vh] overflow-y-auto bg-surface-950 border-t border-white/10 rounded-t-3xl z-[160] p-6 lg:hidden shadow-[0_-10px_40px_rgba(0,0,0,0.5)]"
            >
              <div className="flex justify-end mb-2">
                <button 
                  onClick={() => setMobileOpen(false)} 
                  aria-label="Fechar filtros"
                  className="p-2 bg-white/5 rounded-full text-text-secondary hover:text-white hover:bg-white/10 transition-colors"
                >
                  <X size={18} />
                </button>
              </div>
              <FilterPanel />
            </motion.div>
          </>
        )}
      </AnimatePresence>

      {/* Desktop sidebar */}
      <div className="hidden lg:block w-[260px] shrink-0 sticky top-[100px]">
        <FilterPanel />
      </div>
    </>
  );
}
