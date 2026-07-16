import Link from "next/link";
import {
  ShieldCheck,
  RefreshCw,
  Zap,
  MessageCircle,
  Mail,
  Globe,
} from "lucide-react";

export default function Footer() {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="bg-surface-950 border-t border-white/5 pt-16 pb-8">
      <div className="container mx-auto px-4">
        {/* Top Grid */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
          {/* Brand */}
          <div className="col-span-1 md:col-span-1">
            <Link
              href="/"
              className="inline-block font-display font-bold text-2xl tracking-tighter mb-4"
              aria-label="MTD STORE — Página Inicial"
            >
              MTD<span className="text-brand-600">STORE</span>
            </Link>
            <p className="text-sm text-text-tertiary leading-relaxed mb-6">
              A sua loja definitiva para produtos digitais premium. Chaves
              originais, entrega na velocidade da luz e suporte 24/7.
            </p>
            <div className="flex items-center gap-3">
              {[
                { Icon: Globe, label: "Site" },
                { Icon: Mail, label: "Contato" },
                { Icon: MessageCircle, label: "Discord" },
              ].map(({ Icon, label }) => (
                <a
                  key={label}
                  href="#"
                  className="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-text-secondary hover:bg-brand-600/10 hover:border-brand-500/30 hover:text-brand-500 transition-all"
                  aria-label={`Siga-nos no ${label}`}
                >
                  <Icon size={18} />
                </a>
              ))}
            </div>
          </div>

          {/* Links: Navegação */}
          <div>
            <h4 className="font-display font-semibold mb-6">Navegação</h4>
            <ul className="flex flex-col gap-3">
              {[
                { label: "Catálogo de Produtos", href: "/" },
                { label: "Rastrear Pedido", href: "/consultar" },
                { label: "Termos de Serviço", href: "#" },
                { label: "Política de Privacidade", href: "#" },
              ].map((link) => (
                <li key={link.label}>
                  <Link
                    href={link.href}
                    className="text-sm text-text-tertiary hover:text-white transition-colors relative group"
                  >
                    {link.label}
                    <span className="absolute -bottom-1 left-0 w-0 h-px bg-brand-500 transition-all duration-300 group-hover:w-full" />
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Links: Dúvidas */}
          <div>
            <h4 className="font-display font-semibold mb-6">Dúvidas Frequentes</h4>
            <ul className="flex flex-col gap-3">
              {[
                { label: "Como recebo minha chave?", href: "#" },
                { label: "Quais são as formas de pagamento?", href: "#" },
                { label: "Tive um problema, e agora?", href: "#" },
                { label: "Garantia e Reembolsos", href: "#" },
              ].map((link) => (
                <li key={link.label}>
                  <Link
                    href={link.href}
                    className="text-sm text-text-tertiary hover:text-white transition-colors relative group"
                  >
                    {link.label}
                    <span className="absolute -bottom-1 left-0 w-0 h-px bg-brand-500 transition-all duration-300 group-hover:w-full" />
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Trust Badges */}
          <div>
            <h4 className="font-display font-semibold mb-6">Garantias</h4>
            <ul className="flex flex-col gap-4">
              {[
                {
                  Icon: Zap,
                  title: "Entrega Instantânea",
                  desc: "Chaves enviadas automaticamente.",
                },
                {
                  Icon: ShieldCheck,
                  title: "Pagamento Seguro",
                  desc: "Transações criptografadas.",
                },
                {
                  Icon: RefreshCw,
                  title: "Garantia Total",
                  desc: "Reembolso ou substituição fácil.",
                },
              ].map(({ Icon, title, desc }) => (
                <li key={title} className="flex gap-3 items-start">
                  <div className="w-8 h-8 shrink-0 rounded bg-brand-600/10 border border-brand-500/20 flex items-center justify-center mt-1">
                    <Icon size={14} className="text-brand-500" />
                  </div>
                  <div>
                    <strong className="block text-sm text-text-secondary">
                      {title}
                    </strong>
                    <span className="block text-xs text-text-tertiary leading-relaxed mt-0.5">
                      {desc}
                    </span>
                  </div>
                </li>
              ))}
            </ul>
          </div>
        </div>

        {/* Bottom */}
        <div className="pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-text-tertiary">
          <p>© {currentYear} MTD STORE. Todos os direitos reservados.</p>
          <div className="flex items-center gap-2">
            Desenvolvido com
            <span className="w-2 h-2 rounded-full bg-brand-500 animate-pulse" />
            para máxima velocidade.
          </div>
        </div>
      </div>
    </footer>
  );
}
