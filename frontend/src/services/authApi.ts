import apiClient from './api';
import type { User, LoginCredentials, LoginResponse, UserCreate, UserUpdate, UsersResponse } from '@/types/Auth';

export const authApi = {
  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/login', credentials);
    return response.data;
  },

  async me(): Promise<User> {
    const response = await apiClient.get<User>('/me');
    return response.data;
  },
};

export const usersApi = {
  async getAll(page: number = 1, limit: number = 10): Promise<UsersResponse> {
    const response = await apiClient.get<UsersResponse>('/users', {
      params: { page, limit },
    });
    return response.data;
  },

  async getOne(id: number): Promise<User> {
    const response = await apiClient.get<User>(`/users/${id}`);
    return response.data;
  },

  async create(data: UserCreate): Promise<User> {
    const response = await apiClient.post<User>('/users', data);
    return response.data;
  },

  async update(id: number, data: UserUpdate): Promise<User> {
    const response = await apiClient.put<User>(`/users/${id}`, data);
    return response.data;
  },

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/users/${id}`);
  },
};
