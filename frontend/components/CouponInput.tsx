"use client";

import { useState } from "react";
import { useCartStore } from "@/store/cart";
import { Tag, Loader2, XCircle, CheckCircle2 } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

export default function CouponInput() {
  const { items, coupon, applyCoupon, removeCoupon } = useCartStore();
  const [code, setCode] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const orderTotal = items.reduce((acc, item) => acc + item.price * item.quantity, 0);

  const handleApply = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!code.trim()) return;

    setLoading(true);
    setError("");

    try {
      const productIds = items.map((i) => i.id);

      const res = await fetch("http://localhost:8000/api/v1/coupon/validate", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          code: code.trim(),
          order_total: orderTotal,
          product_ids: productIds,
        }),
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data?.error?.message || "Erro ao validar cupom.");
      }

      applyCoupon({
        code: data.code,
        discount: data.discount,
        type: data.type,
      });
      setCode("");
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setLoading(false);
    }
  };

  if (coupon) {
    return (
      <motion.div 
        initial={{ opacity: 0, y: -10 }}
        animate={{ opacity: 1, y: 0 }}
        className="bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-3 flex justify-between items-center mt-4"
      >
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center">
            <CheckCircle2 size={16} className="text-emerald-500" />
          </div>
          <div>
            <p className="text-sm font-semibold text-white">Cupom {coupon.code}</p>
            <p className="text-xs font-medium text-emerald-400">
              -{coupon.discount.toLocaleString("pt-BR", { style: "currency", currency: "BRL" })}
            </p>
          </div>
        </div>
        <button 
          onClick={removeCoupon} 
          className="p-1.5 text-text-tertiary hover:bg-white/10 hover:text-red-400 rounded-lg transition-colors" 
          aria-label="Remover cupom"
        >
          <XCircle size={18} />
        </button>
      </motion.div>
    );
  }

  return (
    <div className="mt-4">
      <form onSubmit={handleApply} className="flex gap-2">
        <div className="relative flex-1 group">
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-text-tertiary group-focus-within:text-brand-500 transition-colors">
            <Tag size={16} />
          </span>
          <input
            type="text"
            placeholder="Cupom de desconto"
            value={code}
            onChange={(e) => setCode(e.target.value.toUpperCase())}
            className="w-full bg-surface-900 border border-white/10 rounded-xl py-3 pr-4 pl-10 text-sm text-white placeholder:text-text-tertiary focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all font-inter uppercase"
          />
        </div>
        <button
          type="submit"
          disabled={!code.trim() || loading}
          className="bg-surface-800 border border-white/10 hover:bg-surface-700 hover:border-white/20 text-white text-sm font-semibold px-4 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center min-w-[90px]"
        >
          {loading ? <Loader2 size={16} className="animate-spin text-text-tertiary" /> : "Aplicar"}
        </button>
      </form>
      <AnimatePresence>
        {error && (
          <motion.p 
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: "auto" }}
            exit={{ opacity: 0, height: 0 }}
            className="text-red-400 text-xs mt-2 ml-1"
          >
            {error}
          </motion.p>
        )}
      </AnimatePresence>
    </div>
  );
}
