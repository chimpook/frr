import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { findingsApi } from '@/services/api';
import type { Finding, FindingCreate, FindingUpdate, PaginationMeta } from '@/types/Finding';

export const useFindingsStore = defineStore('findings', () => {
  const findings = ref<Finding[]>([]);
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

  async function fetchFindings(page: number = 1, limit: number = 10) {
    loading.value = true;
    error.value = null;
    try {
      const response = await findingsApi.getAll(page, limit);
      findings.value = response.data;
      meta.value = response.meta;
    } catch (e: any) {
      error.value = e.response?.data?.error || e.message || 'Failed to fetch findings';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function createFinding(data: FindingCreate): Promise<Finding> {
    loading.value = true;
    error.value = null;
    try {
      const finding = await findingsApi.create(data);
      await fetchFindings(meta.value.page, meta.value.limit);
      return finding;
    } catch (e: any) {
      error.value = e.response?.data?.error || e.message || 'Failed to create finding';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function updateFinding(id: string, data: FindingUpdate): Promise<Finding> {
    loading.value = true;
    error.value = null;
    try {
      const finding = await findingsApi.update(id, data);
      const index = findings.value.findIndex((f) => f.id === id);
      if (index !== -1) {
        findings.value[index] = finding;
      }
      return finding;
    } catch (e: any) {
      error.value = e.response?.data?.error || e.message || 'Failed to update finding';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function deleteFinding(id: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await findingsApi.delete(id);
      await fetchFindings(meta.value.page, meta.value.limit);
    } catch (e: any) {
      error.value = e.response?.data?.error || e.message || 'Failed to delete finding';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function toggleResolved(id: string): Promise<Finding> {
    const finding = findings.value.find((f) => f.id === id);
    if (!finding) {
      throw new Error('Finding not found');
    }
    return updateFinding(id, { resolved: !finding.resolved });
  }

  return {
    findings,
    meta,
    loading,
    error,
    totalItems,
    currentPage,
    itemsPerPage,
    totalPages,
    fetchFindings,
    createFinding,
    updateFinding,
    deleteFinding,
    toggleResolved,
  };
});
