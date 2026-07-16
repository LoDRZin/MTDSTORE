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
  
  const [gateway, setGateway] = useState("mercadopago"); // mercadopago (PIX) or stripe (CartÃ£o)
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  
  const [qrCode, setQrCode] = useState(""); // Simulate PIX return
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
        items: items.map(i => ({ product_id: i.id, quantity: i.quantity }))
      };
      
      const data = await apiFetch<{ order: { uuid: string } }>("/checkout", {
        method: "POST",
        body: JSON.stringify(payload)
      });
      
      const uuid = data.order.uuid;
      
      // Simulate gateway returns
      if (gateway === "mercadopago") {
        setOrderId(uuid);
        setQrCode("00020126360014br.gov.bcb.pix0114+5511999999999520400005303986540510.005802BR5913MTD STORE LTDA6009SAO PAULO62070503***6304A1B2");
      } else {
        // Stripe success simulation -> redirect immediately to success page
        clearCart();
        router.push(`/pedido/${uuid}/sucesso`);
      }
      
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setLoading(false);
    }
  };
  
  const simulatePaymentSuccess = () => {
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
          <h2 className="text-2xl font-display font-semibold mb-3">Seu carrinho estÃ¡ vazio.</h2>
          <p className="text-text-tertiary mb-8">VocÃª precisa adicionar produtos antes de finalizar a compra.</p>
          <Link href="/">
            <PremiumButton>Voltar ao CatÃ¡logo</PremiumButton>
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
          <h2 className="text-2xl font-display font-semibold mb-3">AutenticaÃ§Ã£o NecessÃ¡ria</h2>
          <p className="text-text-tertiary mb-8">Para sua seguranÃ§a e envio automÃ¡tico do produto, vocÃª precisa estar logado para finalizar a compra.</p>
          <PremiumButton onClick={() => setAuthModalOpen(true)}>Fazer Login ou Criar Conta</PremiumButton>
        </div>
      </SectionContainer>
    );
  }

  if (qrCode) {
    return (
      <SectionContainer className="container mx-auto px-4 py-16 text-center max-w-xl">
        <h2 className="text-3xl font-display font-bold mb-3">Pagamento PIX</h2>
        <p className="text-text-tertiary mb-10">Escaneie o QR Code abaixo no app do seu banco para pagar.</p>
        
        <div className="glass-panel p-10 rounded-3xl flex flex-col items-center shadow-2xl">
          <div className="bg-white p-6 rounded-2xl mb-8 shadow-[0_0_40px_rgba(255,255,255,0.1)]">
             <div className="w-48 h-48 border-4 border-black border-dashed flex items-center justify-center rounded-xl bg-gray-50 text-black font-mono text-sm opacity-50">
               Mock QR CODE
             </div>
          </div>
          
          <div className="w-full relative mb-8">
             <input 
               type="text" 
               className="w-full bg-surface-950 border border-white/10 rounded-xl p-4 text-center text-sm font-mono text-text-secondary focus:outline-none" 
               readOnly 
               value={qrCode} 
             />
             <p className="text-brand-400 text-xs mt-3 uppercase tracking-wider font-bold">Copia e Cola</p>
          </div>
          
          <PremiumButton variant="secondary" size="lg" onClick={simulatePaymentSuccess} className="w-full">
            [DEV] Simular Pagamento Aprovado
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
              <p className="text-sm text-text-secondary font-medium">Os produtos serÃ£o entregues no seguinte e-mail:</p>
              <div className="w-full bg-surface-950 border border-brand-500/30 rounded-xl p-4 text-white">
                <span className="font-semibold text-brand-400">{user?.email}</span>
              </div>
              <p className="text-xs text-text-tertiary mt-1">Este Ã© o e-mail cadastrado na sua conta.</p>
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
                <div className="text-sm">AprovaÃ§Ã£o imediata</div>
                {gateway === 'mercadopago' && (
                  <div className="absolute -top-3 -right-3 w-6 h-6 bg-brand-500 text-white rounded-full flex items-center justify-center text-sm shadow-brand-sm">âœ“</div>
                )}
              </label>

              <label className={`relative p-5 border rounded-2xl cursor-pointer transition-all ${gateway === 'stripe' ? 'border-brand-500 bg-brand-600/5' : 'border-white/10 bg-surface-950 hover:bg-white/5 text-text-tertiary'}`}>
                <input type="radio" name="gateway" value="stripe" checked={gateway === 'stripe'} onChange={() => setGateway("stripe")} className="hidden" />
                <div className="flex items-center justify-between mb-2">
                  <strong className={`font-semibold ${gateway === 'stripe' ? 'text-brand-400' : 'text-white'}`}>CartÃ£o de CrÃ©dito</strong>
                  <CreditCard size={20} className={gateway === 'stripe' ? 'text-brand-400' : ''} />
                </div>
                <div className="text-sm">AtÃ© 12x s/ juros</div>
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
                  <div key={item.id} className="flex justify-between items-center py-3 border-b border-white/5">
                     <span className="text-sm font-medium text-text-secondary"><span className="text-brand-400 mr-2">{item.quantity}x</span> {item.name}</span>
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
