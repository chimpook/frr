import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { usersApi } from '@/services/authApi';
import type { User, UserCreate, UserUpdate } from '@/types/Auth';

interface PaginationMeta {
  page: number;
  limit: number;
  total: number;
  pages: number;
}

export const useUsersStore = defineStore('users', () => {
  const users = ref<User[]>([]);
  const meta = ref<PaginationMeta>({
    page: 1,
    limit: 10,
    total: 0,
    pages: 0,
  });
  const loading = ref(false);
  const error = ref<string | null>(null);

  const totalItems = computed(() => meta.value.total);
  const currentPage = computed(() => meta.value.page);
  const itemsPerPage = computed(() => meta.value.limit);
  const totalPages = computed(() => meta.value.pages);

  async function fetchUsers(page: number = 1, limit: number = 10): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await usersApi.getAll(page, limit);
      users.value = response.data;
      meta.value = response.meta;
    } catch (e: any) {
      error.value = e.response?.data?.error || e.message || 'Failed to fetch users';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function createUser(data: UserCreate): Promise<User> {
    loading.value = true;
    error.value = null;
    try {
      const user = await usersApi.create(data);
      await fetchUsers(meta.value.page, meta.value.limit);
      return user;
    } catch (e: any) {
      error.value = e.response?.data?.error || e.message || 'Failed to create user';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function updateUser(id: number, data: UserUpdate): Promise<User> {
    loading.value = true;
    error.value = null;
    try {
      const user = await usersApi.update(id, data);
      const index = users.value.findIndex((u) => u.id === id);
      if (index !== -1) {
        users.value[index] = user;
      }
      return user;
    } catch (e: any) {
      error.value = e.response?.data?.error || e.message || 'Failed to update user';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function deleteUser(id: number): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await usersApi.delete(id);
      await fetchUsers(meta.value.page, meta.value.limit);
    } catch (e: any) {
      error.value = e.response?.data?.error || e.message || 'Failed to delete user';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function toggleActive(id: number): Promise<User> {
    const user = users.value.find((u) => u.id === id);
    if (!user) {
      throw new Error('User not found');
    }
    return updateUser(id, { active: !user.active });
  }

  return {
    users,
    meta,
    loading,
    error,
    totalItems,
    currentPage,
    itemsPerPage,
    totalPages,
    fetchUsers,
    createUser,
    updateUser,
    deleteUser,
    toggleActive,
  };
});
