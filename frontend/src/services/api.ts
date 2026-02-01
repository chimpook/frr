import axios, { AxiosInstance } from 'axios';
import type { Finding, FindingCreate, FindingUpdate, FindingsResponse } from '@/types/Finding';

const API_URL = process.env.VITE_API_URL || 'http://localhost:8080';

const apiClient: AxiosInstance = axios.create({
  baseURL: `${API_URL}/api`,
  headers: {
    'Content-Type': 'application/json',
  },
});

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

  getExportUrl(filters?: { resolved?: boolean; risk_range?: string }): string {
    const params = new URLSearchParams();
    if (filters?.resolved !== undefined) {
      params.append('resolved', filters.resolved.toString());
    }
    if (filters?.risk_range) {
      params.append('risk_range', filters.risk_range);
    }
    const queryString = params.toString();
    return `${API_URL}/api/findings/export${queryString ? '?' + queryString : ''}`;
  },

  exportToCsv(filters?: { resolved?: boolean; risk_range?: string }): void {
    const url = this.getExportUrl(filters);
    window.open(url, '_blank');
  },
};

export default apiClient;
