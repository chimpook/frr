<template>
  <v-container fluid>
    <v-card>
      <v-card-title class="d-flex align-center">
        <span>User Management</span>
        <v-spacer />
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
          Add User
        </v-btn>
      </v-card-title>

      <v-data-table-server
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="usersStore.users"
        :items-length="usersStore.totalItems"
        :loading="usersStore.loading"
        :page="page"
        @update:page="onPageChange"
        @update:items-per-page="onItemsPerPageChange"
      >
        <template #item.roles="{ item }">
          <v-chip
            v-if="item.roles.includes('ROLE_ADMIN')"
            color="primary"
            size="small"
          >
            Admin
          </v-chip>
          <v-chip v-else color="grey" size="small">
            User
          </v-chip>
        </template>

        <template #item.active="{ item }">
          <v-chip
            :color="item.active ? 'success' : 'error'"
            size="small"
          >
            {{ item.active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>

        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template #item.actions="{ item }">
          <v-btn
            icon="mdi-pencil"
            size="small"
            variant="text"
            @click="openEditDialog(item)"
          />
          <v-btn
            icon="mdi-delete"
            size="small"
            variant="text"
            color="error"
            :disabled="isCurrentUser(item)"
            @click="openDeleteDialog(item)"
          />
        </template>
      </v-data-table-server>
    </v-card>

    <UserDialog
      v-model="showUserDialog"
      :user="selectedUser"
      :loading="saving"
      @save="handleSave"
    />

    <DeleteUserConfirmDialog
      v-model="showDeleteDialog"
      :user="userToDelete"
      :loading="deleting"
      @confirm="handleDelete"
    />

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
      {{ snackbar.message }}
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useUsersStore } from '@/stores/users';
import { useAuthStore } from '@/stores/auth';
import UserDialog from '@/components/UserDialog.vue';
import DeleteUserConfirmDialog from '@/components/DeleteUserConfirmDialog.vue';
import type { User, UserCreate, UserUpdate } from '@/types/Auth';

const usersStore = useUsersStore();
const authStore = useAuthStore();

const page = ref(1);
const itemsPerPage = ref(10);
const showUserDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedUser = ref<User | null>(null);
const userToDelete = ref<User | null>(null);
const saving = ref(false);
const deleting = ref(false);

const snackbar = ref({
  show: false,
  message: '',
  color: 'success',
});

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Role', key: 'roles', sortable: false },
  { title: 'Status', key: 'active' },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
];

onMounted(async () => {
  await fetchUsers();
});

async function fetchUsers() {
  try {
    await usersStore.fetchUsers(page.value, itemsPerPage.value);
  } catch {
    showSnackbar('Failed to load users', 'error');
  }
}

function onPageChange(newPage: number) {
  page.value = newPage;
  fetchUsers();
}

function onItemsPerPageChange(newLimit: number) {
  itemsPerPage.value = newLimit;
  page.value = 1;
  fetchUsers();
}

function openCreateDialog() {
  selectedUser.value = null;
  showUserDialog.value = true;
}

function openEditDialog(user: User) {
  selectedUser.value = { ...user };
  showUserDialog.value = true;
}

function openDeleteDialog(user: User) {
  userToDelete.value = user;
  showDeleteDialog.value = true;
}

async function handleSave(data: UserCreate | UserUpdate) {
  saving.value = true;
  try {
    if (selectedUser.value?.id) {
      await usersStore.updateUser(selectedUser.value.id, data as UserUpdate);
      showSnackbar('User updated successfully', 'success');
    } else {
      await usersStore.createUser(data as UserCreate);
      showSnackbar('User created successfully', 'success');
    }
    showUserDialog.value = false;
  } catch (e: any) {
    const errorMsg = e.response?.data?.errors
      ? Object.values(e.response.data.errors).join(', ')
      : e.response?.data?.error || 'Failed to save user';
    showSnackbar(errorMsg, 'error');
  } finally {
    saving.value = false;
  }
}

async function handleDelete() {
  if (!userToDelete.value) return;

  deleting.value = true;
  try {
    await usersStore.deleteUser(userToDelete.value.id);
    showSnackbar('User deleted successfully', 'success');
    showDeleteDialog.value = false;
  } catch (e: any) {
    const errorMsg = e.response?.data?.error || 'Failed to delete user';
    showSnackbar(errorMsg, 'error');
  } finally {
    deleting.value = false;
  }
}

function isCurrentUser(user: User): boolean {
  return authStore.user?.id === user.id;
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function showSnackbar(message: string, color: string) {
  snackbar.value = { show: true, message, color };
}
</script>
