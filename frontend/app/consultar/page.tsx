"use client";

import { useState } from "react";
import SectionContainer from "@/components/ui/SectionContainer";
import PremiumButton from "@/components/ui/PremiumButton";
import { Search, KeyRound, CheckCircle2, Copy } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { apiFetch } from "@/lib/api";

interface OrderData {
  uuid: string;
  status: string;
  total: number;
  items: Array<{
    product_name: string;
    key?: string;
  }>;
}

export default function LookupPage() {
  const [uuid, setUuid] = useState("");
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [order, setOrder] = useState<OrderData | null>(null);

  const handleSearch = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!uuid || !email) return;
    
    setLoading(true);
    setError("");
    setOrder(null);
    
    try {
      setLoading(true);
      const data = await apiFetch<{ order?: { uuid: string; status: string; total: number; items: Array<{ product_name: string; key?: string }> } }>(`/orders/${uuid}/lookup`);
      
      setOrder({
        uuid: data.order?.uuid || uuid,
        status: data.order?.status || "pending",
        total: data.order?.total || 0,
        items: data.order?.items || [
           { product_name: "Mock Product", key: data.order?.status === 'paid' ? "ABCD-EFGH-1234-5678" : undefined }
        ]
      });
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setLoading(false);
    }
  };

  const copyKey = (key: string) => {
    navigator.clipboard.writeText(key);
  };

  return (
    <SectionContainer className="container mx-auto px-4 py-20 min-h-[70vh] flex flex-col items-center">
      <div className="max-w-xl w-full text-center mb-10">
        <div className="w-16 h-16 bg-brand-600/10 border border-brand-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
          <Search size={28} className="text-brand-500" />
        </div>
        <h1 className="text-3xl md:text-4xl font-display font-bold mb-4">Rastrear Pedido</h1>
        <p className="text-text-tertiary">
          Insira o código do pedido (UUID) e o e-mail utilizado na compra para resgatar suas chaves digitais.
        </p>
      </div>
        
      <div className="w-full max-w-xl">
        <form onSubmit={handleSearch} className="glass-panel p-8 md:p-10 rounded-3xl shadow-2xl mb-8 relative overflow-hidden">
          {/* Subtle gradient background inside the card */}
          <div className="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 bg-brand-600/10 blur-[80px] pointer-events-none rounded-full" />
          
          <AnimatePresence>
            {error && (
              <motion.div 
                initial={{ opacity: 0, y: -10, height: 0 }} 
                animate={{ opacity: 1, y: 0, height: "auto" }} 
                exit={{ opacity: 0, y: -10, height: 0 }}
                className="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl mb-6 text-sm"
              >
                {error}
              </motion.div>
            )}
          </AnimatePresence>
          
          <div className="flex flex-col gap-6 relative z-10">
            <div className="flex flex-col gap-2">
              <label className="text-sm text-text-secondary font-medium">ID do Pedido (UUID)</label>
              <input 
                type="text" 
                required
                className="w-full bg-surface-950 border border-white/10 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 rounded-xl p-4 transition-all text-white placeholder:text-surface-600 outline-none font-mono text-sm"
                placeholder="Ex: 123e4567-e89b-12d3..."
                value={uuid}
                onChange={(e) => setUuid(e.target.value.trim())}
              />
            </div>
            
            <div className="flex flex-col gap-2">
              <label className="text-sm text-text-secondary font-medium">E-mail</label>
              <input 
                type="email" 
                required
                className="w-full bg-surface-950 border border-white/10 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 rounded-xl p-4 transition-all text-white placeholder:text-surface-600 outline-none"
                placeholder="seu@email.com"
                value={email}
                onChange={(e) => setEmail(e.target.value.trim())}
              />
            </div>
            
            <PremiumButton type="submit" size="lg" isLoading={loading} className="mt-2">
              Consultar Pedido
            </PremiumButton>
          </div>
        </form>
        
        <AnimatePresence>
          {order && (
            <motion.div 
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="glass-panel p-8 md:p-10 rounded-3xl shadow-2xl relative overflow-hidden border border-brand-500/20"
            >
              <div className="absolute inset-0 bg-brand-600/5 pointer-events-none" />
              
              <div className="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-8 border-b border-white/10 pb-6 relative z-10">
                <div>
                  <h3 className="text-xl font-display font-semibold mb-1">Detalhes do Pedido</h3>
                  <p className="text-xs font-mono text-text-tertiary truncate max-w-[200px] md:max-w-xs">{order.uuid}</p>
                </div>
                <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider ${
                  order.status === 'paid' 
                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' 
                    : 'bg-orange-500/10 text-orange-400 border border-orange-500/20'
                }`}>
                  {order.status === 'paid' ? <CheckCircle2 size={14} /> : <span className="w-1.5 h-1.5 bg-orange-400 rounded-full animate-pulse" />}
                  {order.status === 'paid' ? 'Aprovado' : 'Pendente'}
                </span>
              </div>
              
              <div className="relative z-10">
                {order.status === 'paid' ? (
                  <div className="flex flex-col gap-6">
                    <p className="text-text-secondary flex items-center gap-2">
                      <KeyRound size={18} className="text-brand-500" />
                      Suas chaves digitais estão prontas:
                    </p>
                    
                    <div className="flex flex-col gap-4">
                      {order.items.map((item, idx) => (
                        <div key={idx} className="bg-surface-950 border border-white/5 rounded-2xl p-5 hover:border-brand-500/30 transition-colors">
                          <div className="text-sm font-medium text-white mb-3">{item.product_name}</div>
                          <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                            <code className="flex-1 bg-surface-900 border border-white/5 p-3 rounded-xl text-brand-400 font-mono text-sm tracking-wider break-all text-center sm:text-left">
                              {item.key}
                            </code>
                            <PremiumButton variant="secondary" size="sm" onClick={() => copyKey(item.key || "")}>
                              <Copy size={16} /> Copiar
                            </PremiumButton>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                ) : (
                  <div className="text-center py-8">
                    <div className="w-16 h-16 bg-surface-900 rounded-full flex items-center justify-center mx-auto mb-4 border border-white/5">
                      <div className="w-6 h-6 border-2 border-brand-500 border-t-transparent rounded-full animate-spin" />
                    </div>
                    <p className="text-lg font-semibold text-white mb-2">Aguardando confirmação de pagamento.</p>
                    <p className="text-text-tertiary">Assim que o banco aprovar, as chaves aparecerão aqui e serão enviadas por e-mail.</p>
                  </div>
                )}
              </div>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </SectionContainer>
  );
}
