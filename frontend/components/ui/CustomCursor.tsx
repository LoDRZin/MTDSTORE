"use client";

import { useEffect, useState } from "react";
import { motion, useMotionValue, useSpring } from "framer-motion";

export default function CustomCursor() {
  const [isVisible, setIsVisible] = useState(false);
  const [isHovering, setIsHovering] = useState(false);

  const cursorX = useMotionValue(-100);
  const cursorY = useMotionValue(-100);

  // Smooth out the movement with very low latency
  const springConfig = { damping: 25, stiffness: 1000, mass: 0.1 };
  const smoothX = useSpring(cursorX, springConfig);
  const smoothY = useSpring(cursorY, springConfig);

  useEffect(() => {
    // Check if it's a touch device (pointer: coarse)
    if (window.matchMedia("(pointer: coarse)").matches) return;

    setIsVisible(true);

    const moveCursor = (e: MouseEvent) => {
      cursorX.set(e.clientX);
      cursorY.set(e.clientY);
    };

    const handleMouseOver = (e: MouseEvent) => {
      const target = e.target as HTMLElement;
      if (
        target.tagName.toLowerCase() === "button" ||
        target.tagName.toLowerCase() === "a" ||
        target.closest("button") ||
        target.closest("a") ||
        target.getAttribute("role") === "button"
      ) {
        setIsHovering(true);
      } else {
        setIsHovering(false);
      }
    };

    window.addEventListener("mousemove", moveCursor);
    window.addEventListener("mouseover", handleMouseOver);

    return () => {
      window.removeEventListener("mousemove", moveCursor);
      window.removeEventListener("mouseover", handleMouseOver);
    };
  }, [cursorX, cursorY]);

  if (!isVisible) return null;

  return (
    <motion.div
      className="fixed top-0 left-0 pointer-events-none z-[9999] mix-blend-difference hidden md:block"
      style={{
        x: smoothX,
        y: smoothY,
        translateX: "-50%",
        translateY: "-50%",
      }}
    >
      <motion.div
        className="w-8 h-8 rounded-full flex items-center justify-center relative"
        animate={{
          scale: isHovering ? 1.5 : 1,
        }}
        transition={{ duration: 0.15 }}
      >
        {/* Anel Exterior com Glow */}
        <motion.div
          className="absolute inset-0 rounded-full border border-brand-500"
          style={{
            boxShadow: "0 0 10px rgba(220, 38, 38, 0.5), inset 0 0 5px rgba(220, 38, 38, 0.3)",
          }}
          animate={{
            opacity: isHovering ? 0 : 1,
            scale: isHovering ? 0.8 : 1,
          }}
          transition={{ duration: 0.15 }}
        />

        {/* Ponto Interior com Glow Forte */}
        <motion.div
          className="w-2 h-2 bg-brand-500 rounded-full z-10"
          style={{
            boxShadow: "0 0 8px rgba(220, 38, 38, 0.8)",
          }}
          animate={{
            scale: isHovering ? 1.5 : 1,
          }}
          transition={{ duration: 0.15 }}
        />
      </motion.div>
    </motion.div>
  );
}
