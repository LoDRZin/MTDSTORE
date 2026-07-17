import { Metadata } from "next";
import Link from "next/link";
import Image from "next/image";
import { notFound } from "next/navigation";
import ProductPurchaseArea from "@/components/ProductPurchaseArea";
import SectionContainer from "@/components/ui/SectionContainer";
import { ShieldCheck, ChevronRight } from "lucide-react";
import ReactMarkdown from "react-markdown";
import remarkGfm from "remark-gfm";
import remarkEmoji from "remark-emoji";

interface Product {
  id: number;
  slug: string;
  name: string;
  description: string;
  price: number;
  available_count: number;
  image_url?: string;
  variants?: {
    id: number;
    name: string;
    price: number;
    available_count: number;
    is_in_stock: boolean;
  }[];
}

type Props = {
  params: { slug: string }
}

async function getProduct(slug: string): Promise<Product | null> {
  try {
    let apiUrl = process.env.NEXT_PUBLIC_API_URL || "/api/v1";
    
    // Determina a URL base: 
    // Na Vercel, forçamos para a API de produção (Render) se o NEXT_PUBLIC_API_URL estiver local.
    const isVercel = process.env.VERCEL === '1' || process.env.NEXT_PUBLIC_VERCEL_ENV;
    
    if (isVercel || apiUrl.includes('mtdstore.test') || apiUrl.includes('localhost')) {
      apiUrl = "https://mtdstore.onrender.com/api/v1";
    } else if (apiUrl.startsWith("/")) {
      const baseUrl = process.env.NEXT_PUBLIC_APP_URL || "https://mtdstore.onrender.com";
      apiUrl = `${baseUrl}${apiUrl}`;
    }

    const res = await fetch(`${apiUrl}/products/${slug}`, {
      cache: "no-store"
    });
    
    if (!res.ok) {
      if (res.status === 404) return null;
      throw new Error("Failed to fetch product");
    }
    
    const data = await res.json();
    return data.data;
  } catch (error) {
    console.error("Error fetching product:", error);
    return null;
  }
}

export async function generateMetadata(
  { params }: Props
): Promise<Metadata> {
  const product = await getProduct(params.slug);
  
  if (!product) {
    return { title: 'Produto não encontrado | MTD STORE' };
  }

  return {
    title: `${product.name} | MTD STORE`,
    description: product.description,
    openGraph: {
      title: product.name,
      description: product.description,
      type: 'website',
    },
  };
}

export default async function ProductPage({ params }: Props) {
  const product = await getProduct(params.slug);

  if (!product) {
    notFound();
  }

  const isOutOfStock = product.available_count === 0;
  const icons = ["🎮", "💻", "🔑", "⚡", "🛡️", "🎯"];
  const icon = icons[product.id % icons.length];

  return (
    <SectionContainer className="container mx-auto px-4 py-12">
      
      {/* Breadcrumb */}
      <nav className="flex items-center gap-2 text-sm text-text-tertiary mb-8 font-medium">
        <Link href="/" className="hover:text-white transition-colors">Catálogo</Link> 
        <ChevronRight size={14} /> 
        <span className="text-brand-400 truncate max-w-[200px] sm:max-w-none">{product.name}</span>
      </nav>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {/* Left Column: Image and Description */}
        <div className="lg:col-span-7 xl:col-span-8 flex flex-col gap-8">
          <div className="bg-surface-900 border border-white/5 rounded-2xl overflow-hidden aspect-video flex items-center justify-center relative shadow-card group">
             <div className="absolute inset-0 bg-gradient-to-tr from-surface-950 via-transparent to-brand-900/20 opacity-50 pointer-events-none" />
             
             {product.image_url ? (
               <Image
                 src={product.image_url}
                 alt={product.name}
                 fill
                 className="object-cover group-hover:scale-105 transition-all duration-700"
                 sizes="(max-width: 1024px) 100vw, 70vw"
                 priority
               />
             ) : (
               <span className="text-9xl filter grayscale opacity-50 group-hover:scale-110 group-hover:opacity-100 transition-all duration-700">
                 {icon}
               </span>
             )}
             {isOutOfStock && (
                <div className="absolute inset-0 bg-black/60 flex items-center justify-center backdrop-blur-sm">
                   <span className="bg-red-500/20 text-red-500 border border-red-500/30 px-6 py-2 rounded-full font-bold uppercase tracking-widest text-lg rotate-12">
                     Esgotado
                   </span>
                </div>
             )}
          </div>

          <div className="bg-surface-900 border border-white/5 rounded-2xl p-6 md:p-8">
            <h3 className="text-xl font-display font-semibold text-white mb-6 flex items-center gap-2">
              <span className="bg-brand-500/20 text-brand-400 p-2 rounded-lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  <path d="M14 2V8H20" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  <path d="M16 13H8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  <path d="M16 17H8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  <path d="M10 9H8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </span>
              Descrição
            </h3>
            <div className="text-text-secondary leading-relaxed text-sm md:text-base prose prose-invert max-w-none">
              <ReactMarkdown
                remarkPlugins={[remarkGfm, remarkEmoji]}
                components={{
                  p: (props) => <p className="mb-4 text-text-secondary" {...props} />,
                  ul: (props) => <ul className="list-disc pl-5 mb-4 space-y-1 text-text-secondary" {...props} />,
                  ol: (props) => <ol className="list-decimal pl-5 mb-4 space-y-1 text-text-secondary" {...props} />,
                  li: (props) => <li {...props} />,
                  h1: (props) => <h1 className="text-2xl font-bold text-white mb-4 mt-6" {...props} />,
                  h2: (props) => <h2 className="text-xl font-bold text-white mb-3 mt-5" {...props} />,
                  h3: (props) => <h3 className="text-lg font-bold text-white mb-2 mt-4" {...props} />,
                  strong: (props) => <strong className="font-bold text-white" {...props} />,
                  a: (props) => <a className="text-brand-400 hover:underline" {...props} />,
                  hr: (props) => <hr className="border-white/10 my-6" {...props} />,
                }}
              >
                {product.description || "Nenhuma descrição detalhada disponível para este produto."}
              </ReactMarkdown>
            </div>
          </div>
        </div>
        
        {/* Right Column: Checkout Area and Details */}
        <div className="lg:col-span-5 xl:col-span-4 flex flex-col gap-6">
          {/* Caixa 1: Compra */}
          <div className="bg-surface-900 border border-white/5 rounded-2xl p-6 shadow-card">
            <ProductPurchaseArea product={product} />
          </div>

          {/* Caixa 2: Informações de Segurança */}
          <div className="bg-surface-900 border border-white/5 rounded-2xl p-6 shadow-card flex flex-col gap-5">
            <div className="flex gap-4 items-start">
              <div className="bg-brand-500/10 text-brand-400 p-2.5 rounded-xl shrink-0 mt-1">
                <ShieldCheck size={20} />
              </div>
              <div>
                <h4 className="text-white font-bold mb-1">Compra Segura</h4>
                <p className="text-text-tertiary text-sm leading-relaxed">
                  Sua compra é protegida por criptografia de ponta a ponta.
                </p>
              </div>
            </div>

            <div className="flex gap-4 items-start">
              <div className="bg-blue-500/10 text-blue-400 p-2.5 rounded-xl shrink-0 mt-1">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
              </div>
              <div>
                <h4 className="text-white font-bold mb-1">Entrega Automática</h4>
                <p className="text-text-tertiary text-sm leading-relaxed">
                  Receba seu produto imediatamente após a aprovação do pagamento.
                </p>
              </div>
            </div>

            <div className="border-t border-white/5 pt-5 mt-2">
              <h4 className="text-white font-bold mb-3">Métodos de pagamento</h4>
              <p className="text-text-tertiary text-xs mb-3">Pix via MercadoPago ou Asaas (À vista)</p>
              <div className="flex gap-2">
                <div className="bg-white/5 border border-white/10 p-2 rounded-lg" title="Pix">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2.5L20.5 7.4V16.6L12 21.5L3.5 16.6V7.4L12 2.5Z" stroke="#32BCAD" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M12 11.5L16 9.2" stroke="#32BCAD" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M12 11.5L8 9.2" stroke="#32BCAD" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M12 11.5V16" stroke="#32BCAD" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
              </div>
            </div>
          </div>

          {/* Caixa 3: Avaliações */}
          <div className="bg-surface-900 border border-white/5 rounded-2xl p-6 shadow-card">
            <div className="flex items-end gap-3 mb-6">
              <span className="text-5xl font-display font-bold text-brand-400 leading-none">5.0</span>
              <div className="flex flex-col gap-1 mb-1">
                <div className="flex text-brand-400">
                  {Array.from({length: 5}).map((_, i) => (
                    <svg key={i} width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                  ))}
                </div>
                <span className="text-xs text-text-tertiary">Mais de 10 avaliações</span>
              </div>
            </div>

            <div className="space-y-3 mb-6">
              {/* Fake bars for ratings */}
              {[5, 4, 3, 2, 1].map(num => (
                <div key={num} className="flex items-center gap-3 text-xs text-text-tertiary">
                  <span className="w-2">{num}</span>
                  <div className="flex-1 h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div className="h-full bg-white/40 rounded-full" style={{ width: num === 5 ? '90%' : num === 4 ? '10%' : '0%' }}></div>
                  </div>
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" className="text-text-tertiary"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                </div>
              ))}
            </div>

            <div className="border-t border-white/5 pt-5 space-y-4">
              <div className="flex flex-col gap-2">
                <div className="flex justify-between items-center">
                  <span className="font-bold text-sm text-white">Cliente Verificado</span>
                  <span className="text-[10px] text-text-tertiary">há 2 dias</span>
                </div>
                <div className="flex text-brand-400 gap-0.5">
                  {Array.from({length: 5}).map((_, i) => (
                    <svg key={i} width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                  ))}
                </div>
                <p className="text-xs text-text-secondary leading-relaxed">
                  Entrega super rápida, comprei e em menos de 1 minuto já estava com o acesso. Recomendo!
                </p>
              </div>
            </div>

            <button className="w-full mt-4 py-3 text-xs font-bold text-white bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-colors">
              Mostrar mais
            </button>
          </div>
        </div>
      </div>
    </SectionContainer>
  );
}
