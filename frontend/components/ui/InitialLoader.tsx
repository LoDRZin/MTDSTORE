"use client";

import { useEffect, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";

export default function InitialLoader() {
  const [showLoader, setShowLoader] = useState(false);
  const [progress, setProgress] = useState(0);

  useEffect(() => {
    // Check if we should show the loader (e.g., once per day)
    const lastSeen = localStorage.getItem("mtdstore_last_visit");
    const now = new Date().getTime();
    const hours24 = 24 * 60 * 60 * 1000;

    if (!lastSeen || now - parseInt(lastSeen) > hours24) {
      setShowLoader(true);
      localStorage.setItem("mtdstore_last_visit", now.toString());
    }
  }, []);

  useEffect(() => {
    if (!showLoader) return;

    // Simulate loading progress
    let currentProgress = 0;
    setProgress(0); // Reset progress in case of StrictMode remounts

    const interval = setInterval(() => {
      // Adiciona de 3 a 7 por vez (média 5)
      currentProgress += Math.floor(Math.random() * 5) + 3;
      
      if (currentProgress >= 100) {
        currentProgress = 100;
        clearInterval(interval);
        setTimeout(() => {
          setShowLoader(false);
        }, 500); // Segura 100% por meio segundo
      }
      setProgress(currentProgress);
    }, 200); // 200ms por tick. ~20 ticks de 5 = 4000ms (4 segundos)

    return () => clearInterval(interval);
  }, [showLoader]);

  if (!showLoader) return null;

  return (
    <AnimatePresence>
      {showLoader && (
        <motion.div
          key="initial-loader"
          initial={{ opacity: 1 }}
          exit={{ opacity: 0, transition: { duration: 0.8, ease: "easeInOut" } }}
          className="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-surface-950"
        >
          {/* Subtle background glow */}
          <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
             <div className="w-[50vw] h-[50vw] bg-brand-600/10 rounded-full blur-[120px]" />
          </div>

          <div className="relative z-10 flex flex-col items-center">
            {/* Title with Glow */}
            <h1 
              className="text-5xl md:text-7xl font-display font-black text-white mb-8 tracking-tighter"
              style={{ textShadow: "0 0 40px rgba(220, 38, 38, 0.8), 0 0 100px rgba(220, 38, 38, 0.4)" }}
            >
              MTD <span className="text-brand-500">STORE</span>
            </h1>

            {/* Progress Bar Container */}
            <div className="w-64 max-w-[80vw] h-1 bg-surface-800 rounded-full overflow-hidden mb-4 relative shadow-[0_0_15px_rgba(220,38,38,0.3)]">
              {/* Animated Progress Line */}
              <motion.div
                className="h-full bg-brand-500 rounded-full shadow-[0_0_10px_rgba(220,38,38,0.8)]"
                initial={{ width: "0%" }}
                animate={{ width: `${progress}%` }}
                transition={{ duration: 0.2, ease: "easeOut" }}
              />
            </div>

            {/* Percentage Text */}
            <div className="text-brand-400 font-mono text-sm font-bold tracking-widest uppercase">
              {progress}%
            </div>
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  );
}
