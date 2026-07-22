"use client";

import { useCartStore } from "@/store/cart";
import { useAuthStore } from "@/store/auth";
import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import PremiumButton from "@/components/ui/PremiumButton";
import SectionContainer from "@/components/ui/SectionContainer";
import {
  ShieldCheck, Mail, ShoppingCart, CreditCard, Bitcoin,
  Globe, Copy, Check, Clock, ChevronRight, QrCode, Tag, X, ChevronDown
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { apiFetch } from "@/lib/api";
import Image from "next/image";

// ─── Tipos ────────────────────────────────────────────────────────────────────

type GatewayId = "stripe" | "efi" | "oxapay" | "wise";

interface PaymentMethod {
  id: GatewayId;
  label: string;
  description: string;
  badge?: string;
  badgeColor?: string;
  icon: React.ReactNode;
  gradient: string;
}

interface WiseData {
  account_email: string;
  reference: string;
  amount: string;
  currency: string;
  instructions: string;
}

// ─── Dados dos Métodos de Pagamento ───────────────────────────────────────────

const PAYMENT_METHODS: PaymentMethod[] = [
  {
    id: "stripe",
    label: "Cartão / PIX",
    description: "Cartão de crédito ou PIX via Stripe. Aprovação instantânea.",
    badge: "Recomendado",
    badgeColor: "text-emerald-400 bg-emerald-400/10 border-emerald-400/20",
    icon: <CreditCard size={22} />,
    gradient: "from-blue-600/20 to-indigo-600/20",
  },
  {
    id: "efi",
    label: "PIX Direto (Efí)",
    description: "QR Code PIX gerado diretamente. Pague sem sair do site.",
    icon: <QrCode size={22} />,
    gradient: "from-green-600/20 to-emerald-600/20",
  },
  {
    id: "oxapay",
    label: "Criptomoeda",
    description: "BTC, ETH, USDT e 100+ moedas. Ideal para pagamentos internacionais.",
    badge: "Gringo-friendly",
    badgeColor: "text-yellow-400 bg-yellow-400/10 border-yellow-400/20",
    icon: <Bitcoin size={22} />,
    gradient: "from-orange-600/20 to-yellow-600/20",
  },
  {
    id: "wise",
    label: "Wise (Internacional)",
    description: "Transferência bancária global com taxas mínimas. Confirmação manual.",
    icon: <Globe size={22} />,
    gradient: "from-teal-600/20 to-cyan-600/20",
  },
];

// ─── Componentes auxiliares ────────────────────────────────────────────────────

function CopyButton({ text }: { text: string }) {
  const [copied, setCopied] = useState(false);
  const copy = () => {
    navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };
  return (
    <button
      type="button"
      onClick={copy}
      className="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-brand-600/20 text-brand-400 hover:bg-brand-600/30 transition-colors border border-brand-500/20"
    >
      {copied ? <Check size={13} /> : <Copy size={13} />}
      {copied ? "Copiado!" : "Copiar"}
    </button>
  );
}

function PixModal({ qrCode, orderId, onClose }: { qrCode: string; orderId: string; onClose: () => void }) {
  const router = useRouter();
  const { clearCart } = useCartStore();

  const handleDone = () => {
    clearCart();
    router.push(`/pedido/${orderId}/sucesso`);
  };

  return (
    <div className="fixed inset-0 z-[400] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
      <motion.div
        initial={{ scale: 0.9, opacity: 0 }}
        animate={{ scale: 1, opacity: 1 }}
        className="bg-surface-950 border border-white/10 rounded-3xl w-full max-w-md p-8 shadow-2xl"
      >
        <div className="flex flex-col items-center text-center">
          <div className="w-14 h-14 rounded-2xl bg-green-500/10 border border-green-500/20 flex items-center justify-center mb-4">
            <QrCode size={28} className="text-green-400" />
          </div>
          <h2 className="text-xl font-display font-bold mb-1">Pague via PIX</h2>
          <p className="text-sm text-text-tertiary mb-6">Escaneie o QR Code abaixo com seu banco ou use o código Copia e Cola.</p>

          {/* QR Code Image */}
          <div className="bg-white p-4 rounded-2xl mb-6 w-52 h-52 flex items-center justify-center">
            <Image
              src={`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrCode)}`}
              alt="QR Code PIX"
              width={192}
              height={192}
              unoptimized
            />
          </div>

          {/* Copia e Cola */}
          <div className="w-full bg-surface-900 border border-white/10 rounded-xl p-4 mb-6">
            <p className="text-xs text-text-tertiary mb-2 font-medium uppercase tracking-wide">PIX Copia e Cola</p>
            <div className="flex items-center gap-3">
              <p className="text-xs text-text-secondary font-mono truncate flex-1">{qrCode.slice(0, 40)}...</p>
              <CopyButton text={qrCode} />
            </div>
          </div>

          <div className="flex items-center gap-2 text-xs text-yellow-400/80 bg-yellow-400/5 border border-yellow-400/10 rounded-xl p-3 mb-6 w-full">
            <Clock size={14} />
            <span>Este QR Code expira em 1 hora. Pague agora para garantir seu pedido.</span>
          </div>

          <PremiumButton className="w-full mb-3" onClick={handleDone}>
            Já Paguei — Ver meu Pedido
          </PremiumButton>
          <button type="button" onClick={onClose} className="text-sm text-text-tertiary hover:text-white transition-colors">
            Cancelar e voltar
          </button>
        </div>
      </motion.div>
    </div>
  );
}

function WiseModal({ data, orderId, onClose }: { data: WiseData; orderId: string; onClose: () => void }) {
  const router = useRouter();
  const { clearCart } = useCartStore();

  const handleDone = () => {
    clearCart();
    router.push(`/pedido/${orderId}/sucesso`);
  };

  return (
    <div className="fixed inset-0 z-[400] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
      <motion.div
        initial={{ scale: 0.9, opacity: 0 }}
        animate={{ scale: 1, opacity: 1 }}
        className="bg-surface-950 border border-white/10 rounded-3xl w-full max-w-lg p-8 shadow-2xl"
      >
        <div className="flex flex-col items-center text-center mb-6">
          <div className="w-14 h-14 rounded-2xl bg-teal-500/10 border border-teal-500/20 flex items-center justify-center mb-4">
            <Globe size={28} className="text-teal-400" />
          </div>
          <h2 className="text-xl font-display font-bold mb-1">Pague via Wise</h2>
          <p className="text-sm text-text-tertiary">Envie a transferência com os dados abaixo. Após confirmação manual, suas chaves serão liberadas.</p>
        </div>

        <div className="flex flex-col gap-3 mb-6">
          {/* Valor */}
          <div className="bg-brand-600/10 border border-brand-500/20 rounded-2xl p-4">
            <p className="text-xs text-text-tertiary mb-1 uppercase tracking-wide font-medium">Valor a Transferir</p>
            <p className="text-3xl font-display font-bold text-brand-400">
              {data.currency} {data.amount}
            </p>
          </div>

          {/* Conta */}
          <div className="bg-surface-900 border border-white/10 rounded-2xl p-4">
            <p className="text-xs text-text-tertiary mb-1 uppercase tracking-wide font-medium">E-mail Wise do Destinatário</p>
            <div className="flex items-center justify-between gap-3">
              <p className="font-semibold text-white">{data.account_email}</p>
              <CopyButton text={data.account_email} />
            </div>
          </div>

          {/* Referência */}
          <div className="bg-surface-900 border border-white/10 rounded-2xl p-4">
            <p className="text-xs text-text-tertiary mb-1 uppercase tracking-wide font-medium">Referência da Transferência (Obrigatório)</p>
            <div className="flex items-center justify-between gap-3">
              <p className="font-mono font-bold text-yellow-400">{data.reference}</p>
              <CopyButton text={data.reference} />
            </div>
          </div>
        </div>

        <div className="flex items-start gap-2 text-xs text-yellow-400/80 bg-yellow-400/5 border border-yellow-400/10 rounded-xl p-3 mb-6">
          <Clock size={14} className="mt-0.5 shrink-0" />
          <span><strong>Importante:</strong> {data.instructions}</span>
        </div>

        <PremiumButton className="w-full mb-3" onClick={handleDone}>
          Já Transferi — Aguardar Confirmação
        </PremiumButton>
        <button type="button" onClick={onClose} className="text-sm text-text-tertiary hover:text-white transition-colors w-full text-center">
          Cancelar e voltar
        </button>
      </motion.div>
    </div>
  );
}

// ─── Página Principal ──────────────────────────────────────────────────────────

export default function CheckoutPage() {
  const { items, clearCart, coupon, applyCoupon, removeCoupon } = useCartStore();
  const { user, setAuthModalOpen } = useAuthStore();
  const [mounted, setMounted] = useState(false);
  const router = useRouter();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [orderId, setOrderId] = useState("");
  const [selectedGateway, setSelectedGateway] = useState<GatewayId>("stripe");

  // Coupon states
  const [couponInput, setCouponInput] = useState("");
  const [isApplyingCoupon, setIsApplyingCoupon] = useState(false);
  const [couponError, setCouponError] = useState("");
  const [isCouponOpen, setIsCouponOpen] = useState(false);

  // Modal states
  const [pixQrCode, setPixQrCode] = useState<string | null>(null);
  const [wiseData, setWiseData] = useState<WiseData | null>(null);

  useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) return null;

  const subtotal = items.reduce((acc, item) => acc + item.price * item.quantity, 0);
  const total = Math.max(0, subtotal - (coupon?.discount || 0));

  const handleApplyCoupon = async () => {
    if (!couponInput.trim()) return;
    setIsApplyingCoupon(true);
    setCouponError("");

    try {
      const data = await apiFetch<{ code: string; discount: number; type: string }>("/coupon/validate", {
        method: "POST",
        body: JSON.stringify({
          code: couponInput.trim(),
          order_total: subtotal,
          product_ids: items.map(i => i.id),
          payment_method: selectedGateway
        }),
      });

      applyCoupon(data);
      setCouponInput("");
      setIsCouponOpen(false);
    } catch (err: unknown) {
      setCouponError(err instanceof Error ? err.message : "Cupom inválido");
    } finally {
      setIsApplyingCoupon(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (items.length === 0) return;

    setLoading(true);
    setError("");

    try {
      const payload = {
        email: user?.email,
        gateway: selectedGateway,
        coupon_code: coupon?.code || null,
        items: items.map((i) => ({
          product_id: i.id,
          variant_id: i.variant_id || null,
          quantity: i.quantity,
        })),
      };

      const data = await apiFetch<{
        order: { uuid: string };
        payment: { qr_code?: string; checkout_url?: string; external_reference?: string };
      }>("/checkout", {
        method: "POST",
        body: JSON.stringify(payload),
      });

      const uuid = data.order.uuid;
      setOrderId(uuid);

      // Stripe ou OxaPay: redirecionar
      if (data.payment?.checkout_url) {
        window.location.href = data.payment.checkout_url;
        return;
      }

      // Efí PIX: exibir QR Code no site
      if (selectedGateway === "efi" && data.payment?.qr_code) {
        setPixQrCode(data.payment.qr_code);
        return;
      }

      // Wise: exibir dados bancários
      if (selectedGateway === "wise" && data.payment?.qr_code) {
        try {
          setWiseData(JSON.parse(data.payment.qr_code) as WiseData);
        } catch {
          setError("Erro ao carregar dados da Wise. Tente novamente.");
        }
        return;
      }

      // Fallback
      clearCart();
      router.push(`/pedido/${uuid}/sucesso`);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setLoading(false);
    }
  };

  // ─── Tela: carrinho vazio ─────────────────────────────────────────────────
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

  // ─── Tela: sem autenticação ───────────────────────────────────────────────
  if (!user && !orderId) {
    return (
      <SectionContainer className="container mx-auto px-4 py-24 text-center max-w-lg">
        <div className="bg-surface-900 border border-white/5 rounded-3xl p-12 shadow-2xl flex flex-col items-center">
          <div className="w-20 h-20 bg-brand-600/10 rounded-full flex items-center justify-center mb-6">
            <ShieldCheck size={32} className="text-brand-500" />
          </div>
          <h2 className="text-2xl font-display font-semibold mb-3">Autenticação Necessária</h2>
          <p className="text-text-tertiary mb-8">
            Para sua segurança e envio automático do produto, você precisa estar logado.
          </p>
          <PremiumButton onClick={() => setAuthModalOpen(true)}>Fazer Login ou Criar Conta</PremiumButton>
        </div>
      </SectionContainer>
    );
  }

  // ─── Tela principal do checkout ───────────────────────────────────────────
  return (
    <>
      {/* Modais */}
      <AnimatePresence>
        {pixQrCode && (
          <PixModal
            qrCode={pixQrCode}
            orderId={orderId}
            onClose={() => setPixQrCode(null)}
          />
        )}
        {wiseData && (
          <WiseModal
            data={wiseData}
            orderId={orderId}
            onClose={() => setWiseData(null)}
          />
        )}
      </AnimatePresence>

      <SectionContainer className="container mx-auto px-4 py-16">
        <div className="flex items-center gap-3 mb-10">
          <ShieldCheck size={28} className="text-brand-500" />
          <h1 className="text-3xl font-display font-bold">Finalizar Compra</h1>
        </div>

        {error && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            className="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl mb-8"
          >
            {error}
          </motion.div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
          <form onSubmit={handleSubmit} className="lg:col-span-7 flex flex-col gap-8">
            
            {/* E-mail de entrega */}
            <div className="glass-panel p-8 rounded-3xl">
              <h3 className="text-xl font-display font-semibold mb-6 flex items-center gap-2">
                <Mail size={20} className="text-text-tertiary" /> Entrega
              </h3>
              <div className="flex flex-col gap-2">
                <p className="text-sm text-text-secondary font-medium">
                  Os produtos serão entregues no seguinte e-mail:
                </p>
                <div className="w-full bg-surface-950 border border-brand-500/30 rounded-xl p-4 text-white">
                  <span className="font-semibold text-brand-400">{user?.email}</span>
                </div>
                <p className="text-xs text-text-tertiary mt-1">Este é o e-mail cadastrado na sua conta.</p>
              </div>
            </div>

            {/* Seletor de Método de Pagamento */}
            <div className="glass-panel p-8 rounded-3xl">
              <h3 className="text-xl font-display font-semibold mb-6 flex items-center gap-2">
                <CreditCard size={20} className="text-text-tertiary" /> Método de Pagamento
              </h3>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-8">
                {PAYMENT_METHODS.map((method) => {
                  const isSelected = selectedGateway === method.id;
                  return (
                    <motion.button
                      key={method.id}
                      type="button"
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      onClick={() => setSelectedGateway(method.id)}
                      className={`relative text-left p-5 rounded-2xl border-2 transition-all bg-gradient-to-br ${method.gradient} ${
                        isSelected
                          ? "border-brand-500 shadow-lg shadow-brand-500/10"
                          : "border-white/10 hover:border-white/20"
                      }`}
                    >
                      {method.badge && (
                        <span className={`absolute top-3 right-3 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border ${method.badgeColor}`}>
                          {method.badge}
                        </span>
                      )}
                      <div className={`w-10 h-10 rounded-xl flex items-center justify-center mb-3 ${isSelected ? "bg-brand-500/20 text-brand-400" : "bg-white/5 text-text-tertiary"}`}>
                        {method.icon}
                      </div>
                      <p className={`font-display font-semibold mb-1 ${isSelected ? "text-white" : "text-text-secondary"}`}>
                        {method.label}
                      </p>
                      <p className="text-xs text-text-tertiary leading-relaxed">{method.description}</p>
                      {isSelected && (
                        <div className="absolute top-3 left-3 w-2 h-2 rounded-full bg-brand-400 animate-pulse" />
                      )}
                    </motion.button>
                  );
                })}
              </div>

              {/* Info contextual do método selecionado */}
              <AnimatePresence mode="wait">
                {selectedGateway === "stripe" && (
                  <motion.div key="stripe-info" initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -8 }}
                    className="bg-blue-500/5 border border-blue-500/20 rounded-2xl p-5 mb-6 flex items-start gap-3">
                    <CreditCard size={18} className="text-blue-400 mt-0.5 shrink-0" />
                    <div>
                      <p className="font-semibold text-white text-sm mb-1">Checkout Stripe</p>
                      <p className="text-xs text-text-tertiary">Você será redirecionado para o ambiente seguro do Stripe. Suporta PIX e Cartões de Crédito. Aprovação instantânea.</p>
                    </div>
                  </motion.div>
                )}
                {selectedGateway === "efi" && (
                  <motion.div key="efi-info" initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -8 }}
                    className="bg-green-500/5 border border-green-500/20 rounded-2xl p-5 mb-6 flex items-start gap-3">
                    <QrCode size={18} className="text-green-400 mt-0.5 shrink-0" />
                    <div>
                      <p className="font-semibold text-white text-sm mb-1">PIX Direto via Efí Bank</p>
                      <p className="text-xs text-text-tertiary">Um QR Code PIX será gerado e exibido aqui mesmo no site. Escaneie com seu banco. Confirmação automática em segundos.</p>
                    </div>
                  </motion.div>
                )}
                {selectedGateway === "oxapay" && (
                  <motion.div key="oxapay-info" initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -8 }}
                    className="bg-orange-500/5 border border-orange-500/20 rounded-2xl p-5 mb-6 flex items-start gap-3">
                    <Bitcoin size={18} className="text-orange-400 mt-0.5 shrink-0" />
                    <div>
                      <p className="font-semibold text-white text-sm mb-1">Pagamento em Cripto via OxaPay</p>
                      <p className="text-xs text-text-tertiary">Aceita BTC, ETH, USDT, USDC, LTC e mais de 100 moedas. Você será redirecionado para o checkout seguro da OxaPay.</p>
                    </div>
                  </motion.div>
                )}
                {selectedGateway === "wise" && (
                  <motion.div key="wise-info" initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -8 }}
                    className="bg-teal-500/5 border border-teal-500/20 rounded-2xl p-5 mb-6 flex items-start gap-3">
                    <Globe size={18} className="text-teal-400 mt-0.5 shrink-0" />
                    <div>
                      <p className="font-semibold text-white text-sm mb-1">Transferência Internacional via Wise</p>
                      <p className="text-xs text-text-tertiary">Perfeito para clientes fora do Brasil. Após finalizar, você receberá os dados da conta Wise para realizar a transferência. Liberação manual em até 24h.</p>
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>

              <PremiumButton type="submit" size="lg" isLoading={loading} className="w-full">
                <span className="flex items-center gap-2">
                  Confirmar e Pagar <ChevronRight size={18} />
                </span>
              </PremiumButton>

              <div className="flex items-center justify-center gap-1.5 mt-4 text-xs text-text-tertiary">
                <ShieldCheck size={12} />
                <span>Transação 100% segura e criptografada</span>
              </div>
            </div>
          </form>

          {/* Resumo do Pedido */}
          <div className="lg:col-span-5 sticky top-32">
            <div className="glass-panel p-8 rounded-3xl shadow-2xl">
              <h3 className="text-xl font-display font-semibold mb-6">Resumo do Pedido</h3>

              <div className="flex flex-col gap-4 mb-6 max-h-[300px] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-surface-700">
                {items.map((item) => (
                  <div key={item.cartItemId} className="flex justify-between items-center py-3 border-b border-white/5">
                    <div className="flex flex-col">
                      <span className="text-sm font-medium text-text-secondary">
                        <span className="text-brand-400 mr-2">{item.quantity}x</span>
                        {item.name}
                      </span>
                      {item.variant_name && (
                        <span className="text-xs text-brand-400 mt-0.5 ml-6">{item.variant_name}</span>
                      )}
                    </div>
                    <span className="font-semibold whitespace-nowrap">
                      R$ {(item.price * item.quantity).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}
                    </span>
                  </div>
                ))}
              </div>

              {/* Cupom Section */}
              <div className="mt-4 pt-4 border-t border-white/5">
                {!coupon ? (
                  <div className="flex flex-col gap-2">
                    <button 
                      type="button"
                      onClick={() => setIsCouponOpen(!isCouponOpen)}
                      className="flex items-center justify-between text-sm font-medium text-brand-400 hover:text-brand-300 transition-colors"
                    >
                      <span className="flex items-center gap-2"><Tag size={16}/> Adicionar cupom de desconto</span>
                      <ChevronDown size={16} className={`transition-transform ${isCouponOpen ? "rotate-180" : ""}`} />
                    </button>
                    <AnimatePresence>
                      {isCouponOpen && (
                        <motion.div
                          initial={{ height: 0, opacity: 0 }}
                          animate={{ height: "auto", opacity: 1 }}
                          exit={{ height: 0, opacity: 0 }}
                          className="overflow-hidden"
                        >
                          <div className="flex items-center gap-2 mt-3">
                            <input
                              type="text"
                              placeholder="Digite seu cupom..."
                              value={couponInput}
                              onChange={(e) => setCouponInput(e.target.value)}
                              className="flex-1 bg-surface-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-brand-500/50 transition-colors"
                              onKeyDown={(e) => e.key === "Enter" && (e.preventDefault(), handleApplyCoupon())}
                            />
                            <button
                              type="button"
                              onClick={handleApplyCoupon}
                              disabled={isApplyingCoupon || !couponInput.trim()}
                              className="bg-brand-600 hover:bg-brand-500 disabled:opacity-50 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors"
                            >
                              {isApplyingCoupon ? "..." : "Aplicar"}
                            </button>
                          </div>
                          {couponError && <p className="text-red-400 text-xs mt-2">{couponError}</p>}
                        </motion.div>
                      )}
                    </AnimatePresence>
                  </div>
                ) : (
                  <div className="flex items-center justify-between bg-brand-500/10 border border-brand-500/20 rounded-xl p-3">
                    <div className="flex items-center gap-2 text-brand-400">
                      <Tag size={16} />
                      <span className="text-sm font-semibold uppercase">{coupon.code}</span>
                    </div>
                    <div className="flex items-center gap-3">
                      <span className="text-sm font-bold text-green-400">- R$ {coupon.discount.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}</span>
                      <button type="button" onClick={removeCoupon} className="text-text-tertiary hover:text-white transition-colors">
                        <X size={16} />
                      </button>
                    </div>
                  </div>
                )}
              </div>

              <div className="flex flex-col gap-2 mt-6 pt-6 border-t border-white/10">
                <div className="flex justify-between items-center text-text-secondary font-medium">
                  <span>Subtotal</span>
                  <span>R$ {subtotal.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}</span>
                </div>
                {coupon && (
                  <div className="flex justify-between items-center text-green-400 font-medium">
                    <span>Desconto ({coupon.code})</span>
                    <span>- R$ {coupon.discount.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}</span>
                  </div>
                )}
                <div className="flex justify-between items-center mt-2 text-xl font-bold">
                  <span>Total</span>
                  <span className="text-brand-400 text-3xl">
                    R$ {total.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}
                  </span>
                </div>
              </div>

              {/* Logos dos gateways aceitos */}
              <div className="mt-6 pt-6 border-t border-white/5">
                <p className="text-xs text-text-tertiary text-center mb-3">Métodos aceitos</p>
                <div className="flex items-center justify-center gap-3 flex-wrap">
                  {[
                    { label: "Stripe", color: "text-blue-400", bg: "bg-blue-500/10", icon: <CreditCard size={14} /> },
                    { label: "PIX", color: "text-green-400", bg: "bg-green-500/10", icon: <QrCode size={14} /> },
                    { label: "Cripto", color: "text-orange-400", bg: "bg-orange-500/10", icon: <Bitcoin size={14} /> },
                    { label: "Wise", color: "text-teal-400", bg: "bg-teal-500/10", icon: <Globe size={14} /> },
                  ].map(({ label, color, bg, icon }) => (
                    <span key={label} className={`flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-lg ${color} ${bg}`}>
                      {icon} {label}
                    </span>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </SectionContainer>
    </>
  );
}
