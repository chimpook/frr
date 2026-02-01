<template>
  <v-card>
    <v-card-title class="d-flex align-center flex-wrap ga-2">
      <span>Findings</span>
      <v-spacer></v-spacer>
      <v-menu>
        <template #activator="{ props }">
          <v-btn
            v-bind="props"
            color="success"
            prepend-icon="mdi-download"
            variant="outlined"
            class="mr-2"
          >
            Export CSV
          </v-btn>
        </template>
        <v-list density="compact">
          <v-list-item @click="exportCsv()">
            <v-list-item-title>All Findings</v-list-item-title>
          </v-list-item>
          <v-divider></v-divider>
          <v-list-subheader>By Status</v-list-subheader>
          <v-list-item @click="exportCsv({ resolved: false })">
            <v-list-item-title>Unresolved Only</v-list-item-title>
          </v-list-item>
          <v-list-item @click="exportCsv({ resolved: true })">
            <v-list-item-title>Resolved Only</v-list-item-title>
          </v-list-item>
          <v-divider></v-divider>
          <v-list-subheader>By Risk Level</v-list-subheader>
          <v-list-item @click="exportCsv({ risk_range: 'High' })">
            <template #prepend>
              <v-icon color="error" size="small">mdi-circle</v-icon>
            </template>
            <v-list-item-title>High Risk</v-list-item-title>
          </v-list-item>
          <v-list-item @click="exportCsv({ risk_range: 'Medium' })">
            <template #prepend>
              <v-icon color="warning" size="small">mdi-circle</v-icon>
            </template>
            <v-list-item-title>Medium Risk</v-list-item-title>
          </v-list-item>
          <v-list-item @click="exportCsv({ risk_range: 'Low' })">
            <template #prepend>
              <v-icon color="success" size="small">mdi-circle</v-icon>
            </template>
            <v-list-item-title>Low Risk</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
        Add Finding
      </v-btn>
    </v-card-title>

    <v-data-table-server
      v-model:items-per-page="itemsPerPage"
      v-model:page="page"
      :headers="headers"
      :items="store.findings"
      :items-length="store.totalItems"
      :loading="store.loading"
      class="elevation-1"
      @update:options="loadItems"
    >
      <template #item.risk_range="{ item }">
        <v-chip
          :color="getRiskColor(item.risk_range)"
          size="small"
          label
        >
          {{ item.risk_range }}
        </v-chip>
      </template>

      <template #item.resolved="{ item }">
        <v-switch
          :model-value="item.resolved"
          color="success"
          hide-details
          density="compact"
          @update:model-value="toggleResolved(item)"
        ></v-switch>
      </template>

      <template #item.created_at="{ item }">
        {{ formatDate(item.created_at) }}
      </template>

      <template #item.actions="{ item }">
        <v-btn
          icon="mdi-pencil"
          size="small"
          variant="text"
          color="primary"
          @click="openEditDialog(item)"
        ></v-btn>
        <v-btn
          icon="mdi-delete"
          size="small"
          variant="text"
          color="error"
          @click="openDeleteDialog(item)"
        ></v-btn>
      </template>

      <template #no-data>
        <v-alert type="info" class="ma-4">
          No findings found. Click "Add Finding" to create one.
        </v-alert>
      </template>
    </v-data-table-server>
  </v-card>

  <FindingDialog
    v-model="findingDialog.show"
    :finding="findingDialog.finding"
    :mode="findingDialog.mode"
    @save="handleSave"
    @update:model-value="handleDialogClose"
  />

  <DeleteConfirmDialog
    v-model="deleteDialog.show"
    :finding="deleteDialog.finding"
    @confirm="handleDelete"
  />

  <EditConflictDialog
    v-model="conflictDialog.show"
    :conflict-data="conflictDialog.data"
    :conflict-type="conflictDialog.type"
    @discard="handleConflictDiscard"
    @reload="handleConflictReload"
    @overwrite="handleConflictOverwrite"
  />
</template>

<script setup lang="ts">
import { ref, inject, onMounted, watch } from 'vue';
import { useFindingsStore } from '@/stores/findings';
import { findingsApi } from '@/services/api';
import type { Finding } from '@/types/Finding';
import FindingDialog from './FindingDialog.vue';
import DeleteConfirmDialog from './DeleteConfirmDialog.vue';
import EditConflictDialog from './EditConflictDialog.vue';

const store = useFindingsStore();
const showSnackbar = inject<(message: string, color?: string) => void>('showSnackbar')!;

const page = ref(1);
const itemsPerPage = ref(10);

const headers = [
  { title: 'ID', key: 'id', sortable: false, width: '80px' },
  { title: 'Location', key: 'location', sortable: false },
  { title: 'Risk', key: 'risk_range', sortable: false, width: '100px' },
  { title: 'Comment', key: 'comment', sortable: false },
  { title: 'Resolved', key: 'resolved', sortable: false, width: '100px' },
  { title: 'Created', key: 'created_at', sortable: false, width: '150px' },
  { title: 'Actions', key: 'actions', sortable: false, width: '120px', align: 'center' as const },
];

const findingDialog = ref({
  show: false,
  finding: null as Finding | null,
  mode: 'create' as 'create' | 'edit',
});

const deleteDialog = ref({
  show: false,
  finding: null as Finding | null,
});

const conflictDialog = ref({
  show: false,
  data: null as Finding | null,
  type: null as 'updated' | 'deleted' | null,
});

// Watch for edit conflicts from WebSocket updates
watch(
  () => store.editingConflict,
  (conflict) => {
    if (conflict && findingDialog.value.show) {
      conflictDialog.value = {
        show: true,
        data: conflict.data,
        type: conflict.type,
      };
    }
  }
);

function getRiskColor(risk: string): string {
  switch (risk) {
    case 'High':
      return 'error';
    case 'Medium':
      return 'warning';
    case 'Low':
      return 'success';
    default:
      return 'grey';
  }
}

function formatDate(dateString: string): string {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

async function loadItems({ page: p, itemsPerPage: limit }: { page: number; itemsPerPage: number }) {
  try {
    await store.fetchFindings(p, limit);
  } catch (e) {
    showSnackbar('Failed to load findings', 'error');
  }
}

function openCreateDialog() {
  findingDialog.value = {
    show: true,
    finding: null,
    mode: 'create',
  };
}

function openEditDialog(finding: Finding) {
  findingDialog.value = {
    show: true,
    finding: { ...finding },
    mode: 'edit',
  };
  store.setEditingFinding(finding.id);
}

function handleDialogClose(isOpen: boolean) {
  if (!isOpen) {
    store.setEditingFinding(null);
  }
}

function openDeleteDialog(finding: Finding) {
  deleteDialog.value = {
    show: true,
    finding,
  };
}

async function handleSave(data: any) {
  try {
    if (findingDialog.value.mode === 'create') {
      await store.createFinding(data);
      showSnackbar('Finding created successfully');
    } else if (findingDialog.value.finding) {
      await store.updateFinding(findingDialog.value.finding.id, data);
      showSnackbar('Finding updated successfully');
    }
    findingDialog.value.show = false;
  } catch (e: any) {
    const message = e.response?.data?.errors
      ? Object.values(e.response.data.errors).join(', ')
      : 'Failed to save finding';
    showSnackbar(message, 'error');
  }
}

async function handleDelete() {
  if (!deleteDialog.value.finding) return;
  try {
    await store.deleteFinding(deleteDialog.value.finding.id);
    showSnackbar('Finding deleted successfully');
    deleteDialog.value.show = false;
  } catch (e) {
    showSnackbar('Failed to delete finding', 'error');
  }
}

async function toggleResolved(finding: Finding) {
  try {
    await store.toggleResolved(finding.id);
    showSnackbar(`Finding marked as ${finding.resolved ? 'unresolved' : 'resolved'}`);
  } catch (e) {
    showSnackbar('Failed to update finding', 'error');
  }
}

function exportCsv(filters?: { resolved?: boolean; risk_range?: string }) {
  findingsApi.exportToCsv(filters);
}

// Conflict resolution handlers
function handleConflictDiscard() {
  findingDialog.value.show = false;
  store.setEditingFinding(null);
  store.clearEditingConflict();
}

function handleConflictReload(data: Finding) {
  // Update the dialog with the latest data from server
  findingDialog.value.finding = { ...data };
  store.clearEditingConflict();
}

function handleConflictOverwrite() {
  // User chose to keep their changes - just close the conflict dialog
  // The save will proceed with user's current data
  store.clearEditingConflict();
}

onMounted(() => {
  loadItems({ page: 1, itemsPerPage: 10 });
});
</script>
