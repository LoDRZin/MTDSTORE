"use client";

import { useEffect, useState, Suspense } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import SectionContainer from "@/components/ui/SectionContainer";
import PremiumButton from "@/components/ui/PremiumButton";
import { CheckCircle2, Copy, AlertTriangle, KeyRound, Download, FileText } from "lucide-react";
import { motion } from "framer-motion";

interface OrderItem {
  product_name: string;
  delivery_type: string;
  post_purchase_instructions?: string;
  file_url?: string;
  keys?: string[];
}

interface OrderData {
  uuid: string;
  status: string;
  items: OrderItem[];
}

function SuccessContent({ uuid }: { uuid: string }) {
  const searchParams = useSearchParams();
  const signature = searchParams.get("signature");
  const expires = searchParams.get("expires");

  const [order, setOrder] = useState<OrderData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    const fetchOrder = async () => {
      try {
        const apiUrl = process.env.NEXT_PUBLIC_API_URL || "/api/v1";
        
        if (signature && expires) {
          // Acesso via link assinado (e-mail)
          const res = await fetch(`${apiUrl}/orders/${uuid}/success?expires=${expires}&signature=${signature}`);
          const data = await res.json();
          
          if (!res.ok) {
            throw new Error(data.message || "Link inválido ou expirado.");
          }
          
          setOrder({
            uuid: data.order,
            status: data.status,
            items: data.items
          });
        } else {
          // Acesso logo após o checkout (só mostra que tá processando ou confirmação de envio)
          await fetch(`${apiUrl}/orders/${uuid}/lookup`);
          
          // Não dá erro aqui se o pedido não estiver pago, pois lookup só exige email se for consultar histórico, mas nesse caso a gente só diz pro usuário olhar o email.
          setOrder({
            uuid,
            status: "pending_or_email_sent",
            items: []
          });
        }
      } catch (err: unknown) {
        setError(err instanceof Error ? err.message : String(err));
      } finally {
        setLoading(false);
      }
    };
    
    fetchOrder();
  }, [uuid, signature, expires]);

  if (loading) {
    return (
      <SectionContainer className="container mx-auto px-4 py-32 text-center flex flex-col items-center">
        <div className="w-16 h-16 rounded-full border-4 border-brand-500 border-t-transparent animate-spin mb-6" />
        <h2 className="text-2xl font-display font-semibold text-white">Carregando pedido...</h2>
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
          <h2 className="text-2xl font-display font-semibold text-white mb-3">Acesso negado</h2>
          <p className="text-text-tertiary mb-8">{error}</p>
          <Link href="/">
            <PremiumButton variant="secondary">Voltar ao Início</PremiumButton>
          </Link>
        </div>
      </SectionContainer>
    );
  }

  // Se não veio do e-mail, apenas mostrar tela de aguarde
  if (!signature) {
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
        
        <h1 className="text-4xl md:text-5xl font-display font-bold text-white mb-4 tracking-tight">Pedido Recebido!</h1>
        <p className="text-lg text-text-tertiary mb-12 max-w-lg">
          Seu pedido <strong className="text-white">#{uuid.split('-')[0]}</strong> está sendo processado. 
          Assim que o pagamento for aprovado, enviaremos o link de acesso aos seus produtos por <strong>e-mail</strong>.
        </p>
        <Link href="/">
          <PremiumButton size="lg">Voltar à Loja</PremiumButton>
        </Link>
      </SectionContainer>
    );
  }

  // Tela Completa com os produtos (Veio do e-mail)
  return (
    <SectionContainer className="container mx-auto px-4 py-16 flex flex-col items-center text-center">
      <motion.div 
        initial={{ scale: 0 }}
        animate={{ scale: 1 }}
        transition={{ type: "spring", stiffness: 200, damping: 20 }}
        className="w-24 h-24 bg-brand-500/10 border border-brand-500/20 rounded-full flex items-center justify-center text-brand-500 mb-8 shadow-[0_0_50px_rgba(220,38,38,0.2)]"
      >
        <CheckCircle2 size={48} />
      </motion.div>
      
      <h1 className="text-4xl md:text-5xl font-display font-bold text-white mb-4 tracking-tight">Acesso Liberado!</h1>
      <p className="text-lg text-text-tertiary mb-12">
        Pedido <strong className="text-white">#{uuid.split('-')[0]}</strong>. Aqui estão seus acessos:
      </p>
      
      <div className="w-full max-w-2xl text-left space-y-8">
        {order?.items.map((item, idx) => (
          <div key={idx} className="glass-panel p-8 md:p-10 rounded-3xl shadow-2xl relative overflow-hidden">
            <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-600 to-emerald-500" />
            
            <h3 className="text-2xl font-display font-semibold text-white mb-6 flex items-center gap-3">
              {item.delivery_type === 'file_download' ? <Download size={24} className="text-brand-500" /> : <KeyRound size={24} className="text-brand-500" />}
              {item.product_name}
            </h3>

            {/* Instruções Pós-Compra */}
            {item.post_purchase_instructions && (
              <div className="mb-8 p-6 bg-brand-900/10 border border-brand-500/20 rounded-2xl">
                <div className="flex items-center gap-2 text-brand-400 font-medium mb-3">
                  <FileText size={18} /> Instruções de Uso
                </div>
                <div 
                  className="prose prose-invert prose-brand max-w-none text-sm text-text-secondary"
                  dangerouslySetInnerHTML={{ __html: item.post_purchase_instructions }}
                />
              </div>
            )}
            
            {/* Download de Arquivo */}
            {item.delivery_type === 'file_download' && item.file_url && (
              <div className="flex justify-center my-6">
                <a href={process.env.NEXT_PUBLIC_API_URL?.replace('/api/v1', '') + item.file_url} target="_blank" rel="noopener noreferrer" className="w-full">
                  <PremiumButton size="lg" className="w-full flex items-center justify-center gap-2">
                    <Download size={20} /> Baixar Arquivo
                  </PremiumButton>
                </a>
              </div>
            )}

            {/* Chaves de Texto */}
            {item.delivery_type === 'unique_key' && item.keys && item.keys.length > 0 && (
              <div className="flex flex-col gap-4">
                {item.keys.map((keyStr, kIdx) => (
                  <div key={kIdx} className="bg-surface-950 border border-white/5 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center gap-4">
                    <code className="flex-1 bg-surface-900 border border-brand-500/20 shadow-[inset_0_0_20px_rgba(0,0,0,0.5)] p-4 rounded-xl text-brand-400 font-mono text-lg tracking-[0.2em] break-all text-center sm:text-left">
                      {keyStr}
                    </code>
                    <PremiumButton variant="secondary" size="md" onClick={() => navigator.clipboard.writeText(keyStr)}>
                      <Copy size={18} /> Copiar
                    </PremiumButton>
                  </div>
                ))}
              </div>
            )}
          </div>
        ))}
      </div>
      
      <div className="mt-12">
        <Link href="/">
          <PremiumButton variant="secondary" size="lg">Voltar à Loja</PremiumButton>
        </Link>
      </div>
    </SectionContainer>
  );
}

export default function SuccessPage({ params }: { params: { uuid: string } }) {
  return (
    <Suspense fallback={<div className="h-screen w-full flex items-center justify-center"><div className="w-16 h-16 rounded-full border-4 border-brand-500 border-t-transparent animate-spin" /></div>}>
      <SuccessContent uuid={params.uuid} />
    </Suspense>
  );
}
