"use client";

import { motion, HTMLMotionProps } from "framer-motion";
import { forwardRef, ReactNode } from "react";

interface PremiumButtonProps extends HTMLMotionProps<"button"> {
  variant?: "primary" | "secondary" | "ghost";
  size?: "sm" | "md" | "lg";
  isLoading?: boolean;
  children?: ReactNode;
}

const PremiumButton = forwardRef<HTMLButtonElement, PremiumButtonProps>(
  (
    { children, variant = "primary", size = "md", isLoading, className = "", disabled, ...props },
    ref
  ) => {
    const baseStyles = "relative inline-flex items-center justify-center font-semibold rounded-xl transition-colors overflow-hidden group";
    
    const sizes = {
      sm: "px-4 py-2 text-sm",
      md: "px-6 py-3 text-sm",
      lg: "px-8 py-4 text-base w-full",
    };

    const variants = {
      primary: "bg-gradient-to-r from-brand-600 to-brand-800 text-white shadow-brand-md border border-brand-500/30",
      secondary: "bg-surface-800 text-white border border-white/10 hover:border-white/20 hover:bg-surface-700",
      ghost: "bg-transparent text-text-tertiary hover:text-white hover:bg-white/5",
    };

    const isDisabled = disabled || isLoading;

    return (
      <motion.button
        ref={ref}
        whileHover={!isDisabled ? { scale: 1.03 } : undefined}
        whileTap={!isDisabled ? { scale: 0.95 } : undefined}
        className={`${baseStyles} ${sizes[size]} ${variants[variant]} ${isDisabled ? "opacity-50 cursor-not-allowed" : ""} ${className}`}
        disabled={isDisabled}
        {...props}
      >
        {variant === "primary" && !isDisabled && (
          <div className="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
        )}
        
        {isLoading ? (
          <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        ) : null}
        
        <span className="relative z-10 flex items-center gap-2">{children}</span>
      </motion.button>
    );
  }
);

PremiumButton.displayName = "PremiumButton";
export default PremiumButton;
