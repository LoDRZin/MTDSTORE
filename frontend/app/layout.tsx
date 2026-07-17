import type { Metadata } from "next";
import { Inter, Space_Grotesk, Sora } from "next/font/google";
import "./globals.css";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import InitialLoader from "@/components/ui/InitialLoader";
import dynamic from "next/dynamic";

const AmbientGlow = dynamic(() => import("@/components/ui/AmbientGlow"), { ssr: false });
const GridBackground = dynamic(() => import("@/components/ui/GridBackground"), { ssr: false });
const CustomCursor = dynamic(() => import("@/components/ui/CustomCursor"), { ssr: false });
const AuthModal = dynamic(() => import("@/components/auth/AuthModal"), { ssr: false });

const inter = Inter({
  subsets: ["latin"],
  variable: "--font-inter",
  display: "swap",
});

const spaceGrotesk = Space_Grotesk({
  subsets: ["latin"],
  variable: "--font-space-grotesk",
  display: "swap",
});

const sora = Sora({
  subsets: ["latin"],
  variable: "--font-sora",
  display: "swap",
});

export const metadata: Metadata = {
  title: "MTD STORE | Chaves e Produtos Digitais Premium",
  description:
    "A melhor loja para compra de chaves de jogos, licenças de software e produtos digitais com entrega automática e imediata via PIX e Cartão.",
  keywords: [
    "jogos digitais",
    "licenças de software",
    "gift cards",
    "comprar chaves",
    "MTD store",
  ],
  openGraph: {
    title: "MTD STORE | Chaves e Produtos Digitais",
    description:
      "Compre licenças originais com envio automático após a aprovação do pagamento.",
    url: "https://mtdstore.com",
    siteName: "MTD STORE",
    images: [{ url: "/og-image.jpg", width: 1200, height: 630 }],
    locale: "pt_BR",
    type: "website",
  },
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="pt-BR" className="dark">
      <body
        className={`${inter.variable} ${spaceGrotesk.variable} ${sora.variable} bg-surface-950 text-white selection:bg-brand-600/30 font-inter antialiased min-h-screen flex flex-col relative`}
      >
        <div id="scroll-progress" aria-hidden="true" />
        
        {/* Initial Loading Screen */}
        <InitialLoader />
        
        {/* Background Effects */}
        <GridBackground />
        <AmbientGlow />
        
        {/* Custom Cursor (Desktop Only) */}
        <CustomCursor />

        {/* Modals */}
        <AuthModal />

        <div className="relative z-10 flex flex-col min-h-screen">
          <Header />
          <main className="flex-1 pt-[90px]">{children}</main>
          <Footer />
        </div>
      </body>
    </html>
  );
}
