"use client";

import { useEffect } from "react";

export default function PerformanceMonitor() {
  useEffect(() => {
    // Apenas monitora em produção e se o navegador suportar
    if (process.env.NODE_ENV === "production" && "performance" in window) {
      window.addEventListener("load", () => {
        setTimeout(() => {
          const perf = performance.getEntriesByType("navigation")[0] as PerformanceNavigationTiming;
          if (perf) {
            console.log(
              `⚡ Performance: DOMContentLoaded em ${perf.domContentLoadedEventEnd.toFixed(0)}ms | Load total: ${perf.loadEventEnd.toFixed(0)}ms`
            );

            // Opcional: Enviar para um endpoint de log se > 3000ms
            if (perf.loadEventEnd > 3000) {
              console.warn("⚠️ Página lenta detectada!");
            }
          }
        }, 0);
      });
    }
  }, []);

  return null;
}
