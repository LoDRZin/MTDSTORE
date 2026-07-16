import { create } from "zustand";
import { persist } from "zustand/middleware";
import { useCartStore } from "./cart";

export interface UserProfile {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  google_id?: string;
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
          // Primeiro, tenta carregar o carrinho do servidor
          const res = await fetch("http://localhost:8000/api/v1/cart/load", {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          });

          if (res.ok) {
            const data = await res.json();
            const serverItems = data.cart_data || [];

            // Se o carrinho local estiver vazio, carrega o do servidor.
            // Se o carrinho local tiver itens, vamos mesclar (local prevalece).
            if (localItems.length === 0 && serverItems.length > 0) {
              cartState.setItems(serverItems);
            } else if (localItems.length > 0) {
              // Se temos itens locais, enviamos para o backend para salvar
              await fetch("http://localhost:8000/api/v1/cart/sync", {
                method: "POST",
                headers: {
                  Authorization: `Bearer ${token}`,
                  "Content-Type": "application/json",
                  Accept: "application/json",
                },
                body: JSON.stringify({ cart_data: localItems }),
              });
            }
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
