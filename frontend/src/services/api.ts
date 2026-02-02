import axios, { AxiosInstance, InternalAxiosRequestConfig, AxiosResponse, AxiosError } from 'axios';
import type { Finding, FindingCreate, FindingUpdate, FindingsResponse } from '@/types/Finding';

// Dynamically detect API URL based on current browser location
function getApiUrl(): string {
  // If explicitly configured, use that
  if (process.env.VITE_API_URL) {
    return process.env.VITE_API_URL;
  }
  // Otherwise, use the same host as the frontend with API port
  const protocol = window.location.protocol;
  const hostname = window.location.hostname;
  return `${protocol}//${hostname}:8080`;
}

const API_URL = getApiUrl();

const apiClient: AxiosInstance = axios.create({
  baseURL: `${API_URL}/api`,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor to add JWT token
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error: AxiosError) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle 401 errors
apiClient.interceptors.response.use(
  (response: AxiosResponse) => {
    return response;
  },
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      // Clear token and redirect to login
      localStorage.removeItem('token');
      // Only redirect if not already on login page
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

export const findingsApi = {
  async getAll(page: number = 1, limit: number = 10): Promise<FindingsResponse> {
    const response = await apiClient.get<FindingsResponse>('/findings', {
      params: { page, limit },
    });
    return response.data;
  },

  async getOne(id: string): Promise<Finding> {
    const response = await apiClient.get<Finding>(`/findings/${id}`);
    return response.data;
  },

  async create(data: FindingCreate): Promise<Finding> {
    const response = await apiClient.post<Finding>('/findings', data);
    return response.data;
  },

  async update(id: string, data: FindingUpdate): Promise<Finding> {
    const response = await apiClient.put<Finding>(`/findings/${id}`, data);
    return response.data;
  },

  async delete(id: string): Promise<void> {
    await apiClient.delete(`/findings/${id}`);
  },

  async healthCheck(): Promise<{ status: string }> {
    const response = await apiClient.get('/health');
    return response.data;
  },

  async exportToCsv(filters?: { resolved?: boolean; risk_range?: string }): Promise<void> {
    const params = new URLSearchParams();
    if (filters?.resolved !== undefined) {
      params.append('resolved', filters.resolved.toString());
    }
    if (filters?.risk_range) {
      params.append('risk_range', filters.risk_range);
    }
    const queryString = params.toString();
    const url = `/findings/export${queryString ? '?' + queryString : ''}`;

    const response = await apiClient.get(url, {
      responseType: 'blob',
    });

    // Create a blob URL and trigger download
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8' });
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.setAttribute('download', `fire_risk_findings_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(downloadUrl);
  },
};

export default apiClient;
