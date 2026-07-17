import ProductList from "@/components/ProductList";
import FilterSidebar from "@/components/FilterSidebar";
import SectionContainer from "@/components/ui/SectionContainer";
import { Zap, ShieldCheck, RefreshCw, Clock } from "lucide-react";
import type { Metadata } from "next";
import { Suspense } from "react";

export const metadata: Metadata = {
  title: "MTD STORE | Chaves e Produtos Digitais",
  description:
    "Compre chaves de jogos, licenças de software e produtos digitais com entrega automática 24/7. Os melhores preços com segurança garantida.",
};

async function getProducts(searchParams: { [key: string]: string | string[] | undefined }) {
  try {
    const params = new URLSearchParams();
    if (searchParams.search) params.append("search", String(searchParams.search));
    if (searchParams.category) params.append("category", String(searchParams.category));
    if (searchParams.min_price) params.append("min_price", String(searchParams.min_price));
    if (searchParams.max_price) params.append("max_price", String(searchParams.max_price));
    if (searchParams.in_stock) params.append("in_stock", String(searchParams.in_stock));
    if (searchParams.page) params.append("page", String(searchParams.page));

    const apiUrl = process.env.NEXT_PUBLIC_API_URL || "/api/v1";
    const res = await fetch(`${apiUrl}/products?${params.toString()}`, {
      cache: "no-store",
    });
    if (!res.ok) {
      const errText = await res.text();
      return { products: [{ id: 999, name: `API Error: ${res.status}`, slug: "err", description: errText.substring(0, 50), price: 0, available_count: 0 }], meta: null };
    }
    const data = await res.json();
    return { products: data.data || [], meta: data.meta || null };
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : String(err);
    return { products: [{ id: 998, name: `Fetch Catch: ${msg}`, slug: "catch-err", description: "", price: 0, available_count: 0 }], meta: null };
  }
}

async function getCategories() {
  try {
    const apiUrl = process.env.NEXT_PUBLIC_API_URL || "/api/v1";
    const res = await fetch(`${apiUrl}/categories`, {
      cache: "no-store",
    });
    if (!res.ok) {
      const errText = await res.text();
      return [{ id: 999, name: `API Error: ${res.status} | URL: ${apiUrl} | Response: ${errText.substring(0, 100)}`, slug: "error", image_url: null }];
    }
    const data = await res.json();
    return data.data || [];
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : String(err);
    return [{ id: 998, name: `Fetch Catch Error: ${msg}`, slug: "catch-error", image_url: null }];
  }
}

const FEATURES = [
  {
    Icon: Zap,
    title: "Entrega Instantânea",
    desc: "Chave entregue automaticamente após a aprovação do pagamento.",
  },
  {
    Icon: ShieldCheck,
    title: "100% Seguro",
    desc: "Pagamentos criptografados. Suas informações protegidas.",
  },
  {
    Icon: RefreshCw,
    title: "Garantia Total",
    desc: "Chave inválida? Substituímos ou reembolsamos sem burocracia.",
  },
  {
    Icon: Clock,
    title: "Suporte 24/7",
    desc: "Nossa equipe está disponível a qualquer hora para te ajudar.",
  },
] as const;

export default async function Home({
  searchParams,
}: {
  searchParams: { [key: string]: string | string[] | undefined };
}) {
  const { products, meta } = await getProducts(searchParams);
  const categories = await getCategories();

  return (
    <>
      {/* ===== HERO ===== */}
      <section className="relative pt-24 pb-32 overflow-hidden" aria-labelledby="hero-title">
        <div className="container mx-auto px-4 relative z-10 text-center">
          
          {/* Online badge */}
          <div className="inline-flex items-center gap-2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest mb-8 backdrop-blur-md" role="status" aria-label="Loja online">
            <span className="w-2 h-2 bg-emerald-400 rounded-full animate-pulse" aria-hidden="true" />
            Loja Online — Disponível 24/7
          </div>

          {/* Title */}
          <h1 className="font-display font-bold text-5xl md:text-7xl leading-[1.1] tracking-tighter mb-6 mx-auto max-w-4xl text-white" id="hero-title">
            O Melhor Lugar Para Suas <br className="hidden md:block" />
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-brand-400 via-brand-500 to-brand-600">
              Chaves Digitais
            </span>
          </h1>

          {/* Subtitle */}
          <p className="text-lg md:text-xl text-text-secondary max-w-2xl mx-auto mb-12 leading-relaxed">
            Entrega automática na velocidade da luz. PIX, Cartão e Boleto.
            Mais de 500 produtos com garantia total.
          </p>

          {/* Stats */}
          <div className="flex flex-wrap justify-center items-center gap-6 md:gap-12 max-w-3xl mx-auto py-8 px-6 bg-surface-900/50 backdrop-blur-md border border-white/10 rounded-2xl shadow-2xl" aria-label="Estatísticas da loja">
            {[
              { value: "500+", label: "Produtos" },
              { value: "10k+", label: "Clientes" },
              { value: "24/7", label: "Entrega Auto" },
              { value: "4.9★", label: "Avaliação" },
            ].map((stat, i) => (
              <div key={i} className="flex flex-col items-center">
                <p className="font-display font-bold text-2xl md:text-3xl text-white">{stat.value}</p>
                <p className="text-xs text-text-tertiary uppercase tracking-wider font-semibold mt-1">{stat.label}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ===== FEATURES ===== */}
      <SectionContainer className="bg-surface-900/30 border-y border-white/5 py-16 backdrop-blur-sm">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {FEATURES.map(({ Icon, title, desc }) => (
              <div
                key={title}
                className="flex flex-col gap-3 p-6 rounded-2xl bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] transition-colors"
              >
                <div className="w-10 h-10 rounded-xl bg-brand-600/10 border border-brand-500/20 flex items-center justify-center">
                  <Icon size={20} className="text-brand-500" />
                </div>
                <p className="font-display font-semibold text-lg text-white">
                  {title}
                </p>
                <p className="text-sm text-text-tertiary leading-relaxed">
                  {desc}
                </p>
              </div>
            ))}
          </div>
        </div>
      </SectionContainer>

      {/* ===== PRODUCTS ===== */}
      <SectionContainer className="py-24" delay={0.2}>
        <div className="container mx-auto px-4">
          <h2 id="products-title" className="sr-only">
            Catálogo de Produtos
          </h2>
          <div className="flex flex-col lg:flex-row gap-8 items-start">
            <Suspense fallback={<div className="w-full text-center py-10">Carregando...</div>}>
              <FilterSidebar categories={categories} />
              <ProductList initialProducts={products} categories={categories} meta={meta} />
            </Suspense>
          </div>
        </div>
      </SectionContainer>
    </>
  );
}
