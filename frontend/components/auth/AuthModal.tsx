"use client";

import { useAuthStore } from "@/store/auth";
import { useState } from "react";
import { X, Mail, Lock, User, Chrome } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import PremiumButton from "../ui/PremiumButton";

export default function AuthModal() {
  const { isAuthModalOpen, setAuthModalOpen, login } = useAuthStore();
  const [mode, setMode] = useState<"login" | "register">("login");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");

  if (!isAuthModalOpen) return null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      const endpoint = mode === "login" ? "/api/v1/login" : "/api/v1/register";
      const payload =
        mode === "login"
          ? { email, password }
          : { email, password, name, password_confirmation: passwordConfirmation };

      const res = await fetch(`http://localhost:8000${endpoint}`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.message || "Erro de autenticação");
      }

      login(data.access_token, data.user);
    } catch (err: any) {
      setError(err.message || "Ocorreu um erro ao conectar.");
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleLogin = async () => {
    try {
      const res = await fetch("http://localhost:8000/api/v1/auth/google/url");
      const data = await res.json();
      if (data.url) {
        window.location.href = data.url;
      }
    } catch (err) {
      setError("Não foi possível conectar com o Google no momento.");
    }
  };

  return (
    <AnimatePresence>
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        className="fixed inset-0 z-[300] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
      >
        <motion.div
          initial={{ scale: 0.95, opacity: 0 }}
          animate={{ scale: 1, opacity: 1 }}
          exit={{ scale: 0.95, opacity: 0 }}
          className="bg-surface-950 border border-white/10 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl relative"
        >
          {/* Close button */}
          <button
            onClick={() => setAuthModalOpen(false)}
            className="absolute top-6 right-6 text-text-tertiary hover:text-white transition-colors"
          >
            <X size={20} />
          </button>

          <div className="p-8">
            <h2 className="text-2xl font-display font-bold mb-2">
              {mode === "login" ? "Bem-vindo de volta" : "Criar uma conta"}
            </h2>
            <p className="text-text-tertiary text-sm mb-6">
              {mode === "login"
                ? "Entre para acessar seus produtos e o carrinho salvo."
                : "Crie sua conta para comprar de forma segura."}
            </p>

            {/* Google Login */}
            <button
              onClick={handleGoogleLogin}
              type="button"
              className="w-full flex items-center justify-center gap-3 bg-white hover:bg-gray-100 text-black font-semibold rounded-xl py-3.5 transition-colors mb-6 shadow-sm"
            >
              <Chrome size={18} />
              Continuar com o Google
            </button>

            <div className="flex items-center gap-4 mb-6">
              <div className="flex-1 h-px bg-white/10"></div>
              <span className="text-xs text-text-tertiary font-medium uppercase tracking-wider">
                ou com email
              </span>
              <div className="flex-1 h-px bg-white/10"></div>
            </div>

            {error && (
              <div className="bg-red-500/10 border border-red-500/20 text-red-400 p-3 rounded-xl mb-6 text-sm">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="flex flex-col gap-4">
              {mode === "register" && (
                <div className="relative">
                  <User
                    size={18}
                    className="absolute left-4 top-1/2 -translate-y-1/2 text-text-tertiary"
                  />
                  <input
                    type="text"
                    required
                    placeholder="Nome completo"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    className="w-full bg-surface-900 border border-white/10 rounded-xl py-3 pl-11 pr-4 focus:outline-none focus:border-brand-500 transition-colors"
                  />
                </div>
              )}

              <div className="relative">
                <Mail
                  size={18}
                  className="absolute left-4 top-1/2 -translate-y-1/2 text-text-tertiary"
                />
                <input
                  type="email"
                  required
                  placeholder="Seu melhor e-mail"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full bg-surface-900 border border-white/10 rounded-xl py-3 pl-11 pr-4 focus:outline-none focus:border-brand-500 transition-colors"
                />
              </div>

              <div className="relative">
                <Lock
                  size={18}
                  className="absolute left-4 top-1/2 -translate-y-1/2 text-text-tertiary"
                />
                <input
                  type="password"
                  required
                  placeholder="Senha secreta"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full bg-surface-900 border border-white/10 rounded-xl py-3 pl-11 pr-4 focus:outline-none focus:border-brand-500 transition-colors"
                />
              </div>

              {mode === "register" && (
                <div className="relative">
                  <Lock
                    size={18}
                    className="absolute left-4 top-1/2 -translate-y-1/2 text-text-tertiary"
                  />
                  <input
                    type="password"
                    required
                    placeholder="Confirme a senha"
                    value={passwordConfirmation}
                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                    className="w-full bg-surface-900 border border-white/10 rounded-xl py-3 pl-11 pr-4 focus:outline-none focus:border-brand-500 transition-colors"
                  />
                </div>
              )}

              <PremiumButton type="submit" isLoading={loading} className="w-full mt-2">
                {mode === "login" ? "Entrar na MTD" : "Criar Conta Segura"}
              </PremiumButton>
            </form>

            <div className="mt-6 text-center">
              <button
                onClick={() => {
                  setMode(mode === "login" ? "register" : "login");
                  setError("");
                }}
                className="text-sm text-text-secondary hover:text-brand-400 transition-colors"
              >
                {mode === "login"
                  ? "Ainda não tem conta? Crie agora."
                  : "Já possui conta? Faça login."}
              </button>
            </div>
          </div>
        </motion.div>
      </motion.div>
    </AnimatePresence>
  );
}
