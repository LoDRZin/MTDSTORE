"use client";

import { useCartStore } from "@/store/cart";
import { useAuthStore } from "@/store/auth";
import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import PremiumButton from "@/components/ui/PremiumButton";
import SectionContainer from "@/components/ui/SectionContainer";
import { ShieldCheck, Mail, CreditCard, QrCode, ShoppingCart } from "lucide-react";
import { motion } from "framer-motion";
import { apiFetch } from "@/lib/api";

export default function CheckoutPage() {
  const { items, clearCart, coupon } = useCartStore();
  const { user, setAuthModalOpen } = useAuthStore();
  const [mounted, setMounted] = useState(false);
  const router = useRouter();
  
  const [gateway, setGateway] = useState("mercadopago"); // mercadopago (PIX) or stripe (Cartão)
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  
  const [qrCode, setQrCode] = useState("");
  const [pixCopyPaste, setPixCopyPaste] = useState("");
  const [pixUrl, setPixUrl] = useState("");
  const [orderId, setOrderId] = useState("");

  useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) return null;

  const total = items.reduce((acc, item) => acc + item.price * item.quantity, 0);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (items.length === 0) return;
    
    setLoading(true);
    setError("");
    
    try {
      const payload = {
        email: user?.email,
        gateway,
        coupon_code: coupon?.code || null,
        items: items.map(i => ({ 
          product_id: i.id, 
          variant_id: i.variant_id || null,
          quantity: i.quantity 
        }))
      };
      
      const data = await apiFetch<{ order: { uuid: string }; payment: { qr_code?: string; checkout_url?: string; external_reference?: string } }>("/checkout", {
        method: "POST",
        body: JSON.stringify(payload)
      });
      
      const uuid = data.order.uuid;
      setOrderId(uuid);
      
      if (gateway === "mercadopago") {
        setQrCode(data.payment?.checkout_url || ""); // URL do ticket do PIX
        setPixCopyPaste(data.payment?.qr_code || ""); // Código copia e cola
        setPixUrl(data.payment?.checkout_url || "");
      } else {
        // Stripe: redirecionar para URL de checkout
        if (data.payment?.checkout_url) {
          window.location.href = data.payment.checkout_url;
        } else {
          clearCart();
          router.push(`/pedido/${uuid}/sucesso`);
        }
      }
      
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setLoading(false);
    }
  };
  
  const handlePixPaid = () => {
    clearCart();
    router.push(`/pedido/${orderId}/sucesso`);
  };

  if (items.length === 0 && !orderId) {
    return (
      <SectionContainer className="container mx-auto px-4 py-24 text-center max-w-lg">
        <div className="bg-surface-900 border border-white/5 rounded-3xl p-12 shadow-2xl flex flex-col items-center">
          <div className="w-20 h-20 bg-brand-600/10 rounded-full flex items-center justify-center mb-6">
            <ShoppingCart size={32} className="text-brand-500" />
          </div>
          <h2 className="text-2xl font-display font-semibold mb-3">Seu carrinho está vazio.</h2>
          <p className="text-text-tertiary mb-8">Você precisa adicionar produtos antes de finalizar a compra.</p>
          <Link href="/">
            <PremiumButton>Voltar ao Catálogo</PremiumButton>
          </Link>
        </div>
      </SectionContainer>
    );
  }

  if (!user && !orderId) {
    return (
      <SectionContainer className="container mx-auto px-4 py-24 text-center max-w-lg">
        <div className="bg-surface-900 border border-white/5 rounded-3xl p-12 shadow-2xl flex flex-col items-center">
          <div className="w-20 h-20 bg-brand-600/10 rounded-full flex items-center justify-center mb-6">
            <ShieldCheck size={32} className="text-brand-500" />
          </div>
          <h2 className="text-2xl font-display font-semibold mb-3">Autenticação Necessária</h2>
          <p className="text-text-tertiary mb-8">Para sua segurança e envio automático do produto, você precisa estar logado para finalizar a compra.</p>
          <PremiumButton onClick={() => setAuthModalOpen(true)}>Fazer Login ou Criar Conta</PremiumButton>
        </div>
      </SectionContainer>
    );
  }

  if (qrCode || pixUrl) {
    return (
      <SectionContainer className="container mx-auto px-4 py-16 text-center max-w-xl">
        <h2 className="text-3xl font-display font-bold mb-3">Pagamento PIX</h2>
        <p className="text-text-tertiary mb-10">Escaneie o QR Code ou use o código Copia e Cola no app do seu banco.</p>
        
        <div className="glass-panel p-10 rounded-3xl flex flex-col items-center shadow-2xl">
          {pixUrl ? (
            <a href={pixUrl} target="_blank" rel="noopener noreferrer" className="bg-white p-6 rounded-2xl mb-8 shadow-[0_0_40px_rgba(255,255,255,0.1)] inline-block">
              <div className="w-48 h-48 flex items-center justify-center">
                <QrCode size={100} className="text-black" />
              </div>
            </a>
          ) : (
            <div className="bg-white p-6 rounded-2xl mb-8 shadow-[0_0_40px_rgba(255,255,255,0.1)]">
              <div className="w-48 h-48 flex items-center justify-center">
                <QrCode size={100} className="text-black" />
              </div>
            </div>
          )}
          
          {pixCopyPaste && (
            <div className="w-full relative mb-8">
              <input 
                type="text" 
                className="w-full bg-surface-950 border border-white/10 rounded-xl p-4 text-center text-sm font-mono text-text-secondary focus:outline-none" 
                readOnly 
                value={pixCopyPaste} 
              />
              <button
                onClick={() => navigator.clipboard.writeText(pixCopyPaste)}
                className="mt-3 text-brand-400 text-xs uppercase tracking-wider font-bold hover:text-brand-300 transition-colors"
              >
                Copiar código Pix
              </button>
            </div>
          )}

          <p className="text-sm text-text-tertiary mb-6">
            Após pagar, seus produtos serão entregues automaticamente por e-mail.
          </p>
          
          <PremiumButton variant="secondary" size="lg" onClick={handlePixPaid} className="w-full">
            Já paguei — ver meu pedido
          </PremiumButton>
        </div>
      </SectionContainer>
    );
  }

  return (
    <SectionContainer className="container mx-auto px-4 py-16">
      <div className="flex items-center gap-3 mb-10">
        <ShieldCheck size={28} className="text-brand-500" />
        <h1 className="text-3xl font-display font-bold">Finalizar Compra</h1>
      </div>
      
      {error && (
        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} className="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl mb-8">
          {error}
        </motion.div>
      )}
      
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
        <form onSubmit={handleSubmit} className="lg:col-span-7 flex flex-col gap-8">
          
          {/* Email Section (Read Only now since we have user auth) */}
          <div className="glass-panel p-8 rounded-3xl">
            <h3 className="text-xl font-display font-semibold mb-6 flex items-center gap-2">
              <Mail size={20} className="text-text-tertiary" /> Recebimento
            </h3>
            <div className="flex flex-col gap-2">
              <p className="text-sm text-text-secondary font-medium">Os produtos serão entregues no seguinte e-mail:</p>
              <div className="w-full bg-surface-950 border border-brand-500/30 rounded-xl p-4 text-white">
                <span className="font-semibold text-brand-400">{user?.email}</span>
              </div>
              <p className="text-xs text-text-tertiary mt-1">Este é o e-mail cadastrado na sua conta.</p>
            </div>
          </div>
          
          {/* Payment Section */}
          <div className="glass-panel p-8 rounded-3xl">
            <h3 className="text-xl font-display font-semibold mb-6 flex items-center gap-2">
              <CreditCard size={20} className="text-text-tertiary" /> Pagamento
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
              <label className={`relative p-5 border rounded-2xl cursor-pointer transition-all ${gateway === 'mercadopago' ? 'border-brand-500 bg-brand-600/5' : 'border-white/10 bg-surface-950 hover:bg-white/5 text-text-tertiary'}`}>
                <input type="radio" name="gateway" value="mercadopago" checked={gateway === 'mercadopago'} onChange={() => setGateway("mercadopago")} className="hidden" />
                <div className="flex items-center justify-between mb-2">
                  <strong className={`font-semibold ${gateway === 'mercadopago' ? 'text-brand-400' : 'text-white'}`}>PIX</strong>
                  <QrCode size={20} className={gateway === 'mercadopago' ? 'text-brand-400' : ''} />
                </div>
                <div className="text-sm">Aprovação imediata</div>
                {gateway === 'mercadopago' && (
                  <div className="absolute -top-3 -right-3 w-6 h-6 bg-brand-500 text-white rounded-full flex items-center justify-center text-sm shadow-brand-sm">âœ“</div>
                )}
              </label>

              <label className={`relative p-5 border rounded-2xl cursor-pointer transition-all ${gateway === 'stripe' ? 'border-brand-500 bg-brand-600/5' : 'border-white/10 bg-surface-950 hover:bg-white/5 text-text-tertiary'}`}>
                <input type="radio" name="gateway" value="stripe" checked={gateway === 'stripe'} onChange={() => setGateway("stripe")} className="hidden" />
                <div className="flex items-center justify-between mb-2">
                  <strong className={`font-semibold ${gateway === 'stripe' ? 'text-brand-400' : 'text-white'}`}>Cartão de Crédito</strong>
                  <CreditCard size={20} className={gateway === 'stripe' ? 'text-brand-400' : ''} />
                </div>
                <div className="text-sm">Até 12x s/ juros</div>
                {gateway === 'stripe' && (
                  <div className="absolute -top-3 -right-3 w-6 h-6 bg-brand-500 text-white rounded-full flex items-center justify-center text-sm shadow-brand-sm">âœ“</div>
                )}
              </label>
            </div>
            
            <PremiumButton type="submit" size="lg" isLoading={loading}>
              Confirmar e Pagar
            </PremiumButton>
          </div>
        </form>
        
        {/* Order Summary */}
        <div className="lg:col-span-5 sticky top-32">
          <div className="glass-panel p-8 rounded-3xl shadow-2xl">
             <h3 className="text-xl font-display font-semibold mb-6">Resumo do Pedido</h3>
             
             <div className="flex flex-col gap-4 mb-6 max-h-[300px] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-surface-700">
               {items.map(item => (
                  <div key={item.cartItemId} className="flex justify-between items-center py-3 border-b border-white/5">
                     <div className="flex flex-col">
                       <span className="text-sm font-medium text-text-secondary"><span className="text-brand-400 mr-2">{item.quantity}x</span> {item.name}</span>
                       {item.variant_name && <span className="text-xs text-brand-400 mt-0.5 ml-6">{item.variant_name}</span>}
                     </div>
                     <span className="font-semibold whitespace-nowrap">R$ {(item.price * item.quantity).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}</span>
                  </div>
               ))}
             </div>
             
             <div className="flex justify-between items-center mt-6 pt-6 border-t border-white/10 text-xl font-bold">
                <span>Total</span>
                <span className="text-brand-400 text-3xl">R$ {total.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}</span>
             </div>
          </div>
        </div>
      </div>
    </SectionContainer>
  );
}
