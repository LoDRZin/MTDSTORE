"use client";

import { useCartStore } from "@/store/cart";
import { useEffect, useState } from "react";
import Link from "next/link";
import { X, ShoppingCart, Trash2, Minus, Plus, ArrowRight, Package } from "lucide-react";
import CouponInput from "./CouponInput";
import { AnimatePresence, motion } from "framer-motion";

export default function CartSidebar() {
  const { items, isOpen, setIsOpen, removeItem, updateQuantity, coupon } = useCartStore();
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) return null;

  const subtotal = items.reduce((acc, item) => acc + item.price * item.quantity, 0);
  const total = Math.max(0, subtotal - (coupon?.discount || 0));
  const itemCount = items.reduce((acc, item) => acc + item.quantity, 0);

  const formatPrice = (v: number) =>
    v.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

  return (
    <AnimatePresence>
      {isOpen && (
        <>
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="fixed inset-0 bg-black/60 backdrop-blur-sm z-[200]"
            onClick={() => setIsOpen(false)}
            aria-hidden="true"
          />

          {/* Sidebar Drawer */}
          <motion.aside
            initial={{ x: "100%", opacity: 0.5 }}
            animate={{ x: 0, opacity: 1 }}
            exit={{ x: "100%", opacity: 0.5 }}
            transition={{ type: "spring", damping: 25, stiffness: 200 }}
            className="fixed top-0 right-0 w-full md:w-[420px] h-full bg-surface-950 border-l border-white/5 z-[210] flex flex-col shadow-2xl"
            aria-label="Carrinho de compras"
            aria-hidden={!isOpen}
          >
            {/* Header */}
            <div className="flex items-center justify-between p-6 border-b border-white/5 shrink-0">
              <div className="flex items-center gap-3">
                <ShoppingCart size={20} className="text-brand-600" />
                <h2 className="font-display text-xl font-semibold text-white m-0">
                  Carrinho
                  {itemCount > 0 && (
                    <span className="ml-3 bg-brand-600/15 text-brand-500 text-xs font-bold px-2 py-0.5 rounded-full font-inter inline-block align-middle">
                      {itemCount}
                    </span>
                  )}
                </h2>
              </div>
              <button
                onClick={() => setIsOpen(false)}
                className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-text-secondary hover:bg-white/10 hover:text-white transition-colors"
                aria-label="Fechar carrinho"
              >
                <X size={18} />
              </button>
            </div>

            {/* Items */}
            <div className="flex-1 overflow-y-auto p-6 scrollbar-thin scrollbar-thumb-surface-700 scrollbar-track-transparent">
              {items.length === 0 ? (
                <div className="flex flex-col items-center justify-center h-full text-center">
                  <div className="w-20 h-20 rounded-full bg-surface-900 border border-white/5 flex items-center justify-center mb-6">
                    <Package size={32} className="text-brand-600/30" />
                  </div>
                  <p className="text-lg font-semibold text-white mb-2">
                    Seu carrinho está vazio
                  </p>
                  <p className="text-sm text-text-tertiary mb-8">
                    Adicione produtos para continuar
                  </p>
                  <button
                    className="px-6 py-3 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-xl transition-colors text-sm"
                    onClick={() => setIsOpen(false)}
                  >
                    Explorar Catálogo
                  </button>
                </div>
              ) : (
                <div className="flex flex-col gap-4">
                  {items.map((item) => (
                    <div key={item.id} className="flex flex-col gap-3 p-4 rounded-xl bg-surface-900 border border-white/5 group">
                      <div className="flex justify-between items-start gap-4">
                        <p className="font-semibold text-white text-sm line-clamp-2 leading-snug flex-1">
                          {item.name}
                        </p>
                        <p className="font-bold text-white shrink-0">
                          {formatPrice(item.price)}
                        </p>
                      </div>
                      <div className="flex items-center justify-between">
                        {/* Quantity Controls */}
                        <div className="flex items-center gap-4 bg-surface-950 border border-white/10 rounded-lg p-1">
                          <button
                            onClick={() => updateQuantity(item.id, item.quantity - 1)}
                            className="w-7 h-7 flex items-center justify-center text-text-tertiary hover:text-white hover:bg-white/5 rounded-md transition-colors"
                            aria-label="Diminuir quantidade"
                          >
                            <Minus size={14} />
                          </button>
                          <span className="text-sm font-semibold w-4 text-center">{item.quantity}</span>
                          <button
                            onClick={() => updateQuantity(item.id, item.quantity + 1)}
                            className="w-7 h-7 flex items-center justify-center text-text-tertiary hover:text-white hover:bg-white/5 rounded-md transition-colors"
                            aria-label="Aumentar quantidade"
                          >
                            <Plus size={14} />
                          </button>
                        </div>
                        {/* Remove */}
                        <button
                          className="w-9 h-9 flex items-center justify-center text-text-tertiary hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-colors"
                          onClick={() => removeItem(item.id)}
                          aria-label={`Remover ${item.name}`}
                        >
                          <Trash2 size={16} />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Footer */}
            {items.length > 0 && (
              <div className="p-6 border-t border-white/5 bg-surface-950/80 backdrop-blur-md shrink-0">
                <CouponInput />
                
                <div className="flex justify-between items-center mt-6 mb-4">
                  <span className="text-text-secondary font-medium">Total</span>
                  <span className="text-2xl font-bold text-white tracking-tight">{formatPrice(total)}</span>
                </div>
                
                <p className="text-[11px] text-text-tertiary text-center mb-4 font-medium uppercase tracking-wider">
                  À vista no PIX — entrega automática após aprovação
                </p>
                
                <Link
                  href="/checkout"
                  onClick={() => setIsOpen(false)}
                  className="flex items-center justify-center gap-2 w-full py-4 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-xl transition-all shadow-brand-md"
                >
                  Finalizar Compra
                  <ArrowRight size={18} />
                </Link>
              </div>
            )}
          </motion.aside>
        </>
      )}
    </AnimatePresence>
  );
}
