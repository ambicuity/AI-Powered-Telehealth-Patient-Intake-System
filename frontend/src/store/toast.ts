import { create } from 'zustand';
import { ToastState, Toast } from '@/types';

export const useToastStore = create<ToastState>((set, get) => ({
  toasts: [],

  addToast: (toast: Omit<Toast, 'id'>) => {
    const id = Math.random().toString(36).substring(2, 9);
    const newToast = { ...toast, id };
    
    set((state) => ({
      toasts: [...state.toasts, newToast],
    }));

    // Auto remove after duration (default 5 seconds)
    const duration = toast.duration || 5000;
    setTimeout(() => {
      get().removeToast(id);
    }, duration);
  },

  removeToast: (id: string) => {
    set((state) => ({
      toasts: state.toasts.filter((toast) => toast.id !== id),
    }));
  },
}));