import { create } from "zustand";
import { persist } from "zustand/middleware";

export interface CartItem {
  id: number;
  slug: string;
  name: string;
  price: number;
  quantity: number;
  variant_id?: number;
  variant_name?: string;
  cartItemId: string;
}

export interface CouponData {
  code: string;
  discount: number;
  type?: string;
}

interface CartState {
  items: CartItem[];
  isOpen: boolean;
  coupon: CouponData | null;
  addItem: (item: Omit<CartItem, "quantity" | "cartItemId">) => void;
  removeItem: (cartItemId: string) => void;
  updateQuantity: (cartItemId: string, quantity: number) => void;
  clearCart: () => void;
  setItems: (items: CartItem[]) => void;
  setIsOpen: (isOpen: boolean) => void;
  toggleCart: () => void;
  applyCoupon: (coupon: CouponData) => void;
  removeCoupon: () => void;
}

export const useCartStore = create<CartState>()(
  persist(
    (set) => ({
      items: [],
      isOpen: false,
      coupon: null,
      
      addItem: (newItem) =>
        set((state) => {
          const cartItemId = newItem.variant_id 
            ? `${newItem.id}-${newItem.variant_id}` 
            : `${newItem.id}`;
            
          const existingItem = state.items.find((i) => i.cartItemId === cartItemId);
          
          if (existingItem) {
            return {
              items: state.items.map((i) =>
                i.cartItemId === cartItemId ? { ...i, quantity: i.quantity + 1 } : i
              ),
              isOpen: true,
            };
          }
          return { 
            items: [...state.items, { ...newItem, cartItemId, quantity: 1 }], 
            isOpen: true 
          };
        }),
        
      removeItem: (cartItemId) =>
        set((state) => ({
          items: state.items.filter((i) => i.cartItemId !== cartItemId),
        })),
        
      updateQuantity: (cartItemId, quantity) =>
        set((state) => ({
          items: state.items.map((i) =>
            i.cartItemId === cartItemId ? { ...i, quantity: Math.max(1, quantity) } : i
          ),
        })),
        
      clearCart: () => set({ items: [], coupon: null }),
      
      setItems: (items) => set({ items }),
      
      setIsOpen: (isOpen) => set({ isOpen }),
      
      toggleCart: () => set((state) => ({ isOpen: !state.isOpen })),

      applyCoupon: (coupon) => set({ coupon }),
      removeCoupon: () => set({ coupon: null }),
    }),
    {
      name: "mtdstore-cart", // key no localStorage
    }
  )
);
