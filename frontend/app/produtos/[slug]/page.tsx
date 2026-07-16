import { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import AddToCartButton from "@/components/AddToCartButton";
import SectionContainer from "@/components/ui/SectionContainer";
import { Package, TrendingUp, ShieldCheck, ChevronRight } from "lucide-react";

interface Product {
  id: number;
  slug: string;
  name: string;
  description: string;
  price: number;
  available_count: number;
}

type Props = {
  params: { slug: string }
}

async function getProduct(slug: string): Promise<Product | null> {
  try {
    const apiUrl = process.env.NEXT_PUBLIC_API_URL || "/api/v1";
    const res = await fetch(`${apiUrl}/products/${slug}`, {
      next: { revalidate: 3600, tags: [`product-${slug}`] }
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
  const isLowStock = product.available_count > 0 && product.available_count <= 5;
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

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
        {/* Product Image Gallery */}
        <div className="lg:col-span-7">
          <div className="bg-surface-900 border border-white/5 rounded-3xl overflow-hidden aspect-[4/3] flex items-center justify-center relative shadow-card group">
             <div className="absolute inset-0 bg-gradient-to-tr from-surface-950 via-transparent to-brand-900/20 opacity-50 pointer-events-none" />
             <span className="text-9xl filter grayscale opacity-50 group-hover:scale-110 group-hover:opacity-100 transition-all duration-700">
               {icon}
             </span>
             {isOutOfStock && (
                <div className="absolute inset-0 bg-black/60 flex items-center justify-center backdrop-blur-sm">
                   <span className="bg-red-500/20 text-red-500 border border-red-500/30 px-6 py-2 rounded-full font-bold uppercase tracking-widest text-lg rotate-12">
                     Esgotado
                   </span>
                </div>
             )}
          </div>
        </div>
        
        {/* Product Info */}
        <div className="lg:col-span-5 flex flex-col">
          <div className="mb-2">
            {!isOutOfStock && (
              <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-4 ${
                isLowStock 
                  ? "bg-orange-500/10 text-orange-400 border border-orange-500/20" 
                  : "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
              }`}>
                {isLowStock ? <TrendingUp size={14} /> : <Package size={14} />}
                {isLowStock ? `Restam apenas ${product.available_count} unidades!` : `Em Estoque: ${product.available_count}`}
              </span>
            )}
          </div>
          
          <h1 className="text-3xl md:text-4xl font-display font-bold text-white mb-6 leading-tight">
            {product.name}
          </h1>
          
          <div className="glass-panel p-6 rounded-2xl mb-8">
            <span className="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-brand-400 via-brand-500 to-brand-600 tracking-tight block mb-1">
              R$ {Number(product.price).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}
            </span>
            <span className="text-sm text-text-tertiary font-medium">À vista no PIX com entrega imediata</span>
          </div>
          
          <div className="mb-8 flex-1">
            <h3 className="text-lg font-display font-semibold text-white mb-3">Descrição do Produto</h3>
            <p className="text-text-secondary leading-relaxed whitespace-pre-line text-sm md:text-base">
              {product.description || "Nenhuma descrição detalhada disponível para este produto. Em caso de dúvidas, contate o suporte antes de realizar a compra."}
            </p>
          </div>
          
          <div className="mt-auto pt-6 border-t border-white/10">
            <AddToCartButton 
              product={{
                id: product.id,
                slug: product.slug,
                name: product.name,
                price: product.price,
              }}
              isOutOfStock={isOutOfStock}
            />
            
            <p className="flex items-center justify-center gap-2 text-xs text-text-tertiary mt-6 bg-white/5 py-3 rounded-lg border border-white/5">
              <ShieldCheck size={16} className="text-brand-500" />
              Compra protegida e chaves 100% originais.
            </p>
          </div>
        </div>
      </div>
    </SectionContainer>
  );
}
