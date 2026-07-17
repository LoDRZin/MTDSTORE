"use client";

import Link from "next/link";
import { useState, useEffect } from "react";
import { useCartStore } from "@/store/cart";
import { useAuthStore } from "@/store/auth";
import Image from "next/image";
import CartSidebar from "./CartSidebar";
import { ShoppingCart, X, Zap, User, LogOut } from "lucide-react";
import { AnimatePresence, motion } from "framer-motion";

export default function Header() {
  const [scrolled, setScrolled] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [mounted, setMounted] = useState(false);
  const { items, toggleCart } = useCartStore();
  const { user, setAuthModalOpen, logout } = useAuthStore();

  const cartCount = mounted ? items.reduce((acc, item) => acc + item.quantity, 0) : 0;

  useEffect(() => {
    setMounted(true);

    const handleScroll = () => {
      setScrolled(window.scrollY > 20);

      // Scroll progress bar
      const doc = document.documentElement;
      const scrollTop = window.scrollY;
      const docHeight = doc.scrollHeight - doc.clientHeight;
      const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
      const bar = document.getElementById("scroll-progress");
      if (bar) bar.style.width = `${progress}%`;
    };

    window.addEventListener("scroll", handleScroll, { passive: true });
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  // Close mobile nav on resize
  useEffect(() => {
    const onResize = () => {
      if (window.innerWidth > 768) setMobileOpen(false);
    };
    window.addEventListener("resize", onResize);
    return () => window.removeEventListener("resize", onResize);
  }, []);

  return (
    <>
      <header
        className={`fixed top-0 left-0 w-full z-[100] transition-all duration-300 border-b ${
          scrolled
            ? "bg-surface-950/80 backdrop-blur-md border-white/5 py-4 shadow-card"
            : "bg-transparent border-transparent py-6"
        }`}
      >
        <div className="container mx-auto px-4 flex items-center justify-between">
          {/* Logo */}
          <Link
            href="/"
            className="font-display font-bold text-2xl tracking-tighter"
            aria-label="MTD STORE — Página Inicial"
          >
            MTD<span className="text-brand-600">STORE</span>
          </Link>

          {/* Desktop Navigation */}
          <nav aria-label="Menu principal" className="hidden md:block">
            <ul className="flex items-center gap-8">
              <li>
                <Link
                  href="/"
                  className="text-text-secondary hover:text-brand-400 font-medium transition-colors"
                >
                  Catálogo
                </Link>
              </li>
              <li>
                <Link
                  href="/consultar"
                  className="text-text-secondary hover:text-brand-400 font-medium transition-colors"
                >
                  Rastrear Pedido
                </Link>
              </li>
            </ul>
          </nav>

          {/* Actions */}
          <div className="flex items-center gap-4">
            {/* Cart */}
            <button
              className="relative p-2 text-text-secondary hover:text-white transition-colors group"
              aria-label="Abrir carrinho de compras"
              onClick={toggleCart}
            >
              <ShoppingCart size={22} className="group-hover:scale-110 transition-transform" />
              <AnimatePresence>
                {cartCount > 0 && (
                  <motion.span
                    initial={{ scale: 0 }}
                    animate={{ scale: 1 }}
                    exit={{ scale: 0 }}
                    className="absolute -top-1 -right-1 bg-brand-600 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full shadow-brand-sm"
                    aria-label={`${cartCount} itens no carrinho`}
                  >
                    {cartCount}
                  </motion.span>
                )}
              </AnimatePresence>
            </button>

            {/* User Auth */}
            {mounted && user ? (
              <div className="relative group/user">
                <button className="flex items-center gap-2 p-2 rounded-full border border-white/10 hover:border-brand-500/50 bg-surface-900 transition-colors">
                  {user.avatar ? (
                    <Image src={user.avatar} alt="Avatar" width={28} height={28} className="rounded-full" />
                  ) : (
                    <div className="w-7 h-7 rounded-full bg-brand-600/20 text-brand-500 flex items-center justify-center text-xs font-bold">
                      {user.name.charAt(0).toUpperCase()}
                    </div>
                  )}
                </button>
                <div className="absolute right-0 top-full mt-2 w-48 bg-surface-900 border border-white/10 rounded-xl shadow-xl opacity-0 invisible group-hover/user:opacity-100 group-hover/user:visible transition-all flex flex-col overflow-hidden">
                  <div className="p-3 border-b border-white/5">
                    <p className="text-sm font-semibold truncate">{user.name}</p>
                    <p className="text-xs text-text-tertiary truncate">{user.email}</p>
                  </div>
                  
                  {user.is_admin && (
                    <a
                      href="https://mtdstore.onrender.com/admin"
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center gap-2 p-3 text-sm text-brand-400 hover:text-brand-300 hover:bg-brand-500/10 transition-colors text-left border-b border-white/5"
                    >
                      <Zap size={16} /> Painel Admin
                    </a>
                  )}

                  <button
                    onClick={logout}
                    className="flex items-center gap-2 p-3 text-sm text-text-secondary hover:text-red-400 hover:bg-red-500/10 transition-colors text-left"
                  >
                    <LogOut size={16} /> Sair da conta
                  </button>
                </div>
              </div>
            ) : (
              mounted && (
                <button
                  onClick={() => setAuthModalOpen(true)}
                  className="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full text-sm font-semibold transition-colors"
                >
                  <User size={16} /> Entrar
                </button>
              )
            )}

            {/* Hamburger */}
            <button
              className="md:hidden p-2 text-text-secondary hover:text-white z-50 relative"
              aria-label={mobileOpen ? "Fechar menu" : "Abrir menu"}
              aria-expanded={mobileOpen}
              onClick={() => setMobileOpen((v) => !v)}
            >
              {mobileOpen ? (
                <X size={24} />
              ) : (
                <div className="flex flex-col gap-1.5 w-6">
                  <span className="h-0.5 bg-current w-full rounded-full transition-transform" />
                  <span className="h-0.5 bg-current w-3/4 rounded-full transition-transform" />
                  <span className="h-0.5 bg-current w-full rounded-full transition-transform" />
                </div>
              )}
            </button>
          </div>
        </div>
      </header>

      {/* Mobile Nav Drawer */}
      <AnimatePresence>
        {mobileOpen && (
          <motion.nav
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -20 }}
            className="fixed inset-0 z-40 bg-surface-950/95 backdrop-blur-xl flex flex-col pt-24 px-6 md:hidden"
            aria-label="Menu mobile"
          >
            <Link
              href="/"
              className="text-2xl font-display font-semibold border-b border-white/5 py-6 text-white hover:text-brand-500 transition-colors"
              onClick={() => setMobileOpen(false)}
            >
              Catálogo
            </Link>
            <Link
              href="/consultar"
              className="text-2xl font-display font-semibold border-b border-white/5 py-6 text-white hover:text-brand-500 transition-colors"
              onClick={() => setMobileOpen(false)}
            >
              Rastrear Pedido
            </Link>

            <div className="mt-12 flex items-center justify-center gap-2 text-text-tertiary text-sm font-medium">
              <Zap size={16} className="text-brand-600" />
              Entrega automática 24/7
            </div>
          </motion.nav>
        )}
      </AnimatePresence>

      <CartSidebar />
    </>
  );
}
