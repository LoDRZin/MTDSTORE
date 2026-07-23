"use client";

import { useState, useEffect, useRef } from "react";
import { Search, X, Loader2 } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import Image from "next/image";
import Link from "next/link";
import { apiFetch } from "@/lib/api";

interface SearchProduct {
  id: number;
  name: string;
  slug: string;
  price: number;
  image?: string;
  categories: { id: number; name: string }[];
}

export default function SearchBar() {
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<SearchProduct[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const wrapperRef = useRef<HTMLDivElement>(null);

  // Debounce and Fetch Logic
  useEffect(() => {
    if (query.trim().length < 2) {
      setResults([]);
      setIsOpen(false);
      return;
    }

    const fetchResults = async () => {
      setIsLoading(true);
      try {
        const response = await apiFetch<{ data: SearchProduct[] }>(
          `/products?search=${encodeURIComponent(query)}&per_page=5`
        );
        setResults(response.data || []);
        setIsOpen(true);
      } catch (error) {
        console.error("Search failed:", error);
        setResults([]);
      } finally {
        setIsLoading(false);
      }
    };

    const debounceTimer = setTimeout(() => {
      fetchResults();
    }, 300);

    return () => clearTimeout(debounceTimer);
  }, [query]);

  // Click outside to close
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleClear = () => {
    setQuery("");
    setResults([]);
    setIsOpen(false);
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Enter" && query.trim()) {
      setIsOpen(false);
      // Opcional: Redirecionar para uma pÃ¡gina completa de busca futura
      // router.push(`/busca?q=${encodeURIComponent(query)}`);
    }
  };

  return (
    <div ref={wrapperRef} className="relative w-full max-w-md mx-auto z-50">
      {/* Input Field */}
      <div className="relative flex items-center">
        <Search className="absolute left-4 text-text-tertiary" size={18} />
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onKeyDown={handleKeyDown}
          onFocus={() => {
            if (results.length > 0) setIsOpen(true);
          }}
          placeholder="Buscar produtos..."
          className="w-full bg-surface-900 border border-white/10 text-white placeholder-text-tertiary rounded-full py-2.5 pl-11 pr-10 focus:outline-none focus:border-brand-500/50 focus:ring-1 focus:ring-brand-500/50 transition-all text-sm"
        />
        {isLoading ? (
          <Loader2 className="absolute right-4 text-brand-500 animate-spin" size={18} />
        ) : query ? (
          <button
            onClick={handleClear}
            className="absolute right-4 text-text-tertiary hover:text-white transition-colors"
          >
            <X size={18} />
          </button>
        ) : null}
      </div>

      {/* Dropdown Results */}
      <AnimatePresence>
        {isOpen && query.trim().length >= 2 && (
          <motion.div
            initial={{ opacity: 0, y: 10, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 10, scale: 0.98 }}
            transition={{ duration: 0.2 }}
            className="absolute top-full left-0 right-0 mt-2 bg-surface-950 border border-white/10 rounded-2xl shadow-2xl overflow-hidden backdrop-blur-xl"
          >
            {results.length > 0 ? (
              <div className="max-h-[60vh] overflow-y-auto custom-scrollbar p-2">
                {results.map((product) => (
                  <Link
                    key={product.id}
                    href={`/produtos/${product.slug}`}
                    onClick={() => setIsOpen(false)}
                    className="flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors group"
                  >
                    {product.image ? (
                      <div className="w-12 h-12 rounded-lg bg-surface-900 overflow-hidden flex-shrink-0 relative">
                        <Image
                          src={product.image}
                          alt={product.name}
                          fill
                          className="object-cover group-hover:scale-110 transition-transform duration-300"
                        />
                      </div>
                    ) : (
                      <div className="w-12 h-12 rounded-lg bg-surface-900 flex items-center justify-center flex-shrink-0 text-text-tertiary">
                        <Search size={16} />
                      </div>
                    )}
                    <div className="flex-1 min-w-0">
                      <h4 className="text-sm font-medium text-white truncate group-hover:text-brand-400 transition-colors">
                        {product.name}
                      </h4>
                      {product.categories?.[0] && (
                        <p className="text-xs text-text-tertiary truncate">
                          {product.categories[0].name}
                        </p>
                      )}
                    </div>
                    <div className="text-sm font-bold text-brand-400 whitespace-nowrap">
                      R$ {Number(product.price).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}
                    </div>
                  </Link>
                ))}
              </div>
            ) : (
              <div className="p-8 text-center text-text-tertiary">
                <Search size={32} className="mx-auto mb-3 opacity-20" />
                <p className="text-sm">Nenhum produto encontrado para &quot;{query}&quot;</p>
              </div>
            )}
            
            {/* Quick Actions Footer */}
            {results.length > 0 && (
              <div className="p-3 bg-surface-900/50 border-t border-white/5 text-center">
                <span className="text-xs text-text-tertiary">
                  Pressione <kbd className="bg-white/10 px-1.5 py-0.5 rounded text-white mx-1">Enter</kbd> para ver mais
                </span>
              </div>
            )}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
