import { create } from "zustand";
import { persist } from "zustand/middleware";
import { useCartStore } from "./cart";
import { apiFetch } from "@/lib/api";

export interface UserProfile {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  google_id?: string;
  is_admin?: boolean;
}

interface AuthState {
  user: UserProfile | null;
  token: string | null;
  isAuthModalOpen: boolean;
  login: (token: string, user: UserProfile) => void;
  logout: () => void;
  setAuthModalOpen: (isOpen: boolean) => void;
  syncCartWithBackend: () => Promise<void>;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthModalOpen: false,

      login: (token, user) => {
        set({ token, user, isAuthModalOpen: false });
        get().syncCartWithBackend();
      },

      logout: () => {
        // Opcionalmente podemos chamar o /api/logout do backend aqui
        set({ token: null, user: null });
        useCartStore.getState().clearCart(); // Limpa o carrinho ao deslogar
      },

      setAuthModalOpen: (isOpen) => set({ isAuthModalOpen: isOpen }),

      syncCartWithBackend: async () => {
        const token = get().token;
        if (!token) return;

        const cartState = useCartStore.getState();
        const localItems = cartState.items;

        try {
          const data = await apiFetch<{ cart_data: typeof localItems }>("/cart/load", { token });
          const serverItems = data.cart_data || [];

          if (localItems.length === 0 && serverItems.length > 0) {
            cartState.setItems(serverItems);
          } else if (localItems.length > 0) {
            await apiFetch("/cart/sync", {
              method: "POST",
              token,
              body: JSON.stringify({ cart_data: localItems }),
            });
          }
        } catch (e) {
          console.error("Falha ao sincronizar o carrinho:", e);
        }
      },
    }),
    {
      name: "mtdstore-auth",
    }
  )
);
