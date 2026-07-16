"use client";

import { useEffect } from "react";
import PremiumButton from "@/components/ui/PremiumButton";
import { AlertCircle } from "lucide-react";

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    // Log the error to an error reporting service
    console.error(error);
  }, [error]);

  return (
    <div className="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
      <div className="w-16 h-16 bg-red-500/10 text-red-500 rounded-full flex items-center justify-center mb-6">
        <AlertCircle size={32} />
      </div>
      <h2 className="text-3xl font-display font-bold mb-4">Ops! Algo deu errado.</h2>
      <p className="text-text-secondary mb-8 max-w-md mx-auto">
        Ocorreu um erro inesperado ao tentar carregar esta página. Nossa equipe já foi notificada.
      </p>
      <PremiumButton onClick={() => reset()} variant="secondary">
        Tentar Novamente
      </PremiumButton>
    </div>
  );
}
