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
            // Opcional: Enviar para um endpoint de log se > 3000ms
            if (perf.loadEventEnd > 3000) {
              // Enviar métrica silenciosamente para API de telemetria
            }
          }
        }, 0);
      });
    }
  }, []);

  return null;
}
