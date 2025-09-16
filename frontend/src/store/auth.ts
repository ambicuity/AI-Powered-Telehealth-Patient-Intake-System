import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { apiClient, handleApiError } from '@/lib/api';
import { AuthState, LoginRequest, RegisterRequest, User } from '@/types';

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,

      login: async (credentials: LoginRequest) => {
        try {
          const response = await apiClient.post('/auth/login', credentials);
          const { token, user } = response.data;

          localStorage.setItem('auth_token', token);
          
          set({
            user,
            token,
            isAuthenticated: true,
          });
        } catch (error) {
          const apiError = handleApiError(error);
          throw new Error(apiError.message);
        }
      },

      register: async (data: RegisterRequest) => {
        try {
          const response = await apiClient.post('/auth/register', data);
          const { token, user } = response.data;

          localStorage.setItem('auth_token', token);
          
          set({
            user,
            token,
            isAuthenticated: true,
          });
        } catch (error) {
          const apiError = handleApiError(error);
          throw new Error(apiError.message);
        }
      },

      logout: () => {
        localStorage.removeItem('auth_token');
        set({
          user: null,
          token: null,
          isAuthenticated: false,
        });
      },

      updateUser: (user: User) => {
        set({ user });
      },
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({ 
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
);