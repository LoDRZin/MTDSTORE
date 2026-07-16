"use client";

import { useEffect, useState } from "react";
import { motion, useMotionValue } from "framer-motion";

export default function CustomCursor() {
  const [isVisible, setIsVisible] = useState(false);

  const cursorX = useMotionValue(-100);
  const cursorY = useMotionValue(-100);
  const cursorScale = useMotionValue(1);
  const cursorOpacity = useMotionValue(1);

  useEffect(() => {
    // Check if it's a touch device (pointer: coarse)
    if (window.matchMedia("(pointer: coarse)").matches) return;

    setIsVisible(true);

    const moveCursor = (e: MouseEvent) => {
      cursorX.set(e.clientX);
      cursorY.set(e.clientY);
    };

    // Usando event delegation otimizado para evitar re-renders do React
    const handleMouseOver = (e: MouseEvent) => {
      const target = e.target as HTMLElement;
      const isClickable = 
        target.tagName.toLowerCase() === "button" ||
        target.tagName.toLowerCase() === "a" ||
        target.closest("button") ||
        target.closest("a") ||
        target.getAttribute("role") === "button";
        
      if (isClickable) {
        cursorScale.set(1.5);
        cursorOpacity.set(0.8);
      } else {
        cursorScale.set(1);
        cursorOpacity.set(1);
      }
    };

    window.addEventListener("mousemove", moveCursor, { passive: true });
    window.addEventListener("mouseover", handleMouseOver, { passive: true });

    return () => {
      window.removeEventListener("mousemove", moveCursor);
      window.removeEventListener("mouseover", handleMouseOver);
    };
  }, [cursorX, cursorY, cursorScale, cursorOpacity]);

  if (!isVisible) return null;

  return (
    <motion.div
      className="fixed top-0 left-0 pointer-events-none z-[9999] hidden md:block"
      style={{
        x: cursorX,
        y: cursorY,
        translateX: "-50%",
        translateY: "-50%",
        mixBlendMode: "screen",
        scale: cursorScale,
        opacity: cursorOpacity
      }}
    >
      {/* Puro Glow: Largura e altura 0, apenas a sombra expansiva cria a luz */}
      <motion.div
        className="w-0 h-0 rounded-full"
        style={{
          boxShadow: "0 0 60px 30px rgba(220, 38, 38, 0.7), 0 0 100px 60px rgba(220, 38, 38, 0.4)",
        }}
        transition={{ duration: 0.15 }}
      />
    </motion.div>
  );
}
