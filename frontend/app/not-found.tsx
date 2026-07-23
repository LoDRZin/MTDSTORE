import Link from "next/link";
import PremiumButton from "@/components/ui/PremiumButton";
import SectionContainer from "@/components/ui/SectionContainer";
import { AlertTriangle } from "lucide-react";

export default function NotFound() {
  return (
    <SectionContainer className="container mx-auto px-4 py-32 text-center max-w-lg">
      <div className="bg-surface-900 border border-white/5 rounded-3xl p-12 shadow-2xl flex flex-col items-center">
        <div className="w-20 h-20 bg-brand-600/10 rounded-full flex items-center justify-center mb-6">
          <AlertTriangle size={32} className="text-brand-500" />
        </div>
        <h2 className="text-3xl font-display font-semibold mb-3">Página não encontrada</h2>
        <p className="text-text-tertiary mb-8">
          Desculpe, a página que você está procurando não existe ou foi movida.
        </p>
        <Link href="/">
          <PremiumButton>Voltar para a Loja</PremiumButton>
        </Link>
      </div>
    </SectionContainer>
  );
}
