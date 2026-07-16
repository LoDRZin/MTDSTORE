"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import SectionContainer from "@/components/ui/SectionContainer";
import PremiumButton from "@/components/ui/PremiumButton";
import { CheckCircle2, Copy, AlertTriangle, KeyRound, Mail } from "lucide-react";
import { motion } from "framer-motion";

interface OrderData {
  uuid: string;
  status: string;
  total: number;
  items: Array<{
    product_name: string;
    key?: string; // Digital key if available
  }>;
}

export default function SuccessPage({ params }: { params: { uuid: string } }) {
  const [order, setOrder] = useState<OrderData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    const fetchOrder = async () => {
      try {
        const apiUrl = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api/v1";
        const res = await fetch(`${apiUrl}/orders/${params.uuid}/lookup`);
        const data = await res.json();
        
        if (!res.ok) {
          throw new Error(data.message || "Pedido não encontrado");
        }
        
        setOrder({
          uuid: data.order?.uuid || params.uuid,
          status: "paid", 
          total: data.order?.total || 0,
          items: [
             { product_name: "Mock Product", key: "ABCD-EFGH-1234-5678" } 
          ]
        });
      } catch (err: unknown) {
        setError(err instanceof Error ? err.message : String(err));
      } finally {
        setLoading(false);
      }
    };
    
    fetchOrder();
  }, [params.uuid]);

  if (loading) {
    return (
      <SectionContainer className="container mx-auto px-4 py-32 text-center flex flex-col items-center">
        <div className="w-16 h-16 rounded-full border-4 border-brand-500 border-t-transparent animate-spin mb-6" />
        <h2 className="text-2xl font-display font-semibold text-white">Processando pagamento...</h2>
        <p className="text-text-tertiary mt-2">Aguarde enquanto confirmamos sua compra.</p>
      </SectionContainer>
    );
  }

  if (error) {
    return (
      <SectionContainer className="container mx-auto px-4 py-24 text-center max-w-lg">
        <div className="bg-surface-900 border border-white/5 rounded-3xl p-10 shadow-2xl flex flex-col items-center">
          <div className="w-20 h-20 bg-red-500/10 rounded-full flex items-center justify-center mb-6">
            <AlertTriangle size={32} className="text-red-500" />
          </div>
          <h2 className="text-2xl font-display font-semibold text-white mb-3">Oops! Algo deu errado.</h2>
          <p className="text-text-tertiary mb-8">{error}</p>
          <Link href="/">
            <PremiumButton variant="secondary">Voltar ao Início</PremiumButton>
          </Link>
        </div>
      </SectionContainer>
    );
  }

  return (
    <SectionContainer className="container mx-auto px-4 py-16 flex flex-col items-center text-center">
      <motion.div 
        initial={{ scale: 0 }}
        animate={{ scale: 1 }}
        transition={{ type: "spring", stiffness: 200, damping: 20 }}
        className="w-24 h-24 bg-emerald-500/10 border border-emerald-500/20 rounded-full flex items-center justify-center text-emerald-500 mb-8 shadow-[0_0_50px_rgba(16,185,129,0.2)]"
      >
        <CheckCircle2 size={48} />
      </motion.div>
      
      <h1 className="text-4xl md:text-5xl font-display font-bold text-white mb-4 tracking-tight">Pagamento Aprovado!</h1>
      <p className="text-lg text-text-tertiary mb-12">
        Seu pedido <strong className="text-white">#{params.uuid.split('-')[0]}</strong> foi processado com sucesso.
      </p>
      
      <div className="w-full max-w-2xl text-left">
        <div className="glass-panel p-8 md:p-10 rounded-3xl shadow-2xl relative overflow-hidden">
          <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-600 to-emerald-500" />
          
          <h3 className="text-2xl font-display font-semibold text-white mb-8 flex items-center gap-3">
            <KeyRound size={24} className="text-brand-500" /> Suas Chaves Digitais
          </h3>
          
          <div className="flex flex-col gap-6">
            {order?.items.map((item, idx) => (
              <div key={idx} className="bg-surface-950 border border-white/5 rounded-2xl p-6">
                <div className="text-sm font-medium text-text-secondary mb-4">{item.product_name}</div>
                <div className="flex flex-col sm:flex-row sm:items-center gap-4">
                  <code className="flex-1 bg-surface-900 border border-brand-500/20 shadow-[inset_0_0_20px_rgba(0,0,0,0.5)] p-4 rounded-xl text-brand-400 font-mono text-lg tracking-[0.2em] break-all text-center sm:text-left">
                    {item.key}
                  </code>
                  <PremiumButton variant="secondary" size="md" onClick={() => navigator.clipboard.writeText(item.key || "")}>
                    <Copy size={18} /> Copiar
                  </PremiumButton>
                </div>
              </div>
            ))}
          </div>
          
          <div className="mt-8 flex items-center justify-center gap-2 text-sm text-text-tertiary bg-white/5 py-4 rounded-xl border border-white/5">
             <Mail size={16} /> Uma cópia das chaves foi enviada para o seu e-mail.
          </div>
        </div>
      </div>
      
      <div className="mt-12">
        <Link href="/">
          <PremiumButton size="lg">Explorar mais produtos</PremiumButton>
        </Link>
      </div>
    </SectionContainer>
  );
}
