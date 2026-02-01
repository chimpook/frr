import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authApi } from '@/services/authApi';
import type { User, LoginCredentials } from '@/types/Auth';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem('token'));
  const loading = ref(false);
  const error = ref<string | null>(null);

  const isAuthenticated = computed(() => !!token.value);
  const isAdmin = computed(() => user.value?.roles?.includes('ROLE_ADMIN') ?? false);
  const userName = computed(() => user.value?.name ?? '');
  const userEmail = computed(() => user.value?.email ?? '');

  async function login(credentials: LoginCredentials): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await authApi.login(credentials);
      token.value = response.token;
      localStorage.setItem('token', response.token);
      await fetchUser();
    } catch (e: any) {
      error.value = e.response?.data?.message || e.response?.data?.detail || 'Invalid email or password';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function fetchUser(): Promise<void> {
    if (!token.value) {
      user.value = null;
      return;
    }

    loading.value = true;
    error.value = null;
    try {
      user.value = await authApi.me();
    } catch (e: any) {
      // If token is invalid, clear it
      if (e.response?.status === 401) {
        logout();
      }
      error.value = e.response?.data?.error || e.message || 'Failed to fetch user';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  function logout(): void {
    user.value = null;
    token.value = null;
    localStorage.removeItem('token');
  }

  async function initialize(): Promise<void> {
    if (token.value) {
      try {
        await fetchUser();
      } catch {
        // Token invalid, already handled
      }
    }
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    userName,
    userEmail,
    login,
    fetchUser,
    logout,
    initialize,
  };
});
