<template>
  <v-app>
    <v-app-bar v-if="authStore.isAuthenticated" color="primary" density="comfortable">
      <v-app-bar-title>
        <v-icon class="mr-2">mdi-fire</v-icon>
        Fire Risk Findings
      </v-app-bar-title>

      <template #append>
        <WebSocketStatus />

        <v-btn
          :to="{ name: 'findings' }"
          variant="text"
          :class="{ 'v-btn--active': route.name === 'findings' }"
        >
          Findings
        </v-btn>
        <v-btn
          v-if="authStore.isAdmin"
          :to="{ name: 'users' }"
          variant="text"
          :class="{ 'v-btn--active': route.name === 'users' }"
        >
          Users
        </v-btn>

        <v-menu>
          <template #activator="{ props }">
            <v-btn icon v-bind="props" class="ml-2">
              <v-icon>mdi-account-circle</v-icon>
            </v-btn>
          </template>
          <v-list>
            <v-list-item>
              <v-list-item-title class="font-weight-bold">
                {{ authStore.userName }}
              </v-list-item-title>
              <v-list-item-subtitle>
                {{ authStore.userEmail }}
              </v-list-item-subtitle>
            </v-list-item>
            <v-divider />
            <v-list-item @click="handleLogout">
              <template #prepend>
                <v-icon>mdi-logout</v-icon>
              </template>
              <v-list-item-title>Logout</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </template>
    </v-app-bar>

    <v-main>
      <v-container fluid class="pa-4">
        <router-view />
      </v-container>
    </v-main>

    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="3000"
      location="bottom right"
    >
      {{ snackbar.message }}
      <template #actions>
        <v-btn variant="text" @click="snackbar.show = false">
          Close
        </v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>

<script setup lang="ts">
import { reactive, provide, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useWebSocket } from '@/composables/useWebSocket';
import { useRealtimeNotifications } from '@/composables/useRealtimeNotifications';
import WebSocketStatus from '@/components/WebSocketStatus.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { connect, disconnect } = useWebSocket();

interface Snackbar {
  show: boolean;
  message: string;
  color: string;
}

const snackbar = reactive<Snackbar>({
  show: false,
  message: '',
  color: 'success',
});

const showSnackbar = (message: string, color: string = 'success') => {
  snackbar.message = message;
  snackbar.color = color;
  snackbar.show = true;
};

provide('showSnackbar', showSnackbar);

// Initialize realtime notifications after snackbar is available
const { subscribe: subscribeNotifications, unsubscribe: unsubscribeNotifications } = useRealtimeNotifications();

// Connect WebSocket when authenticated
watch(
  () => authStore.token,
  (token) => {
    if (token) {
      connect(token);
      subscribeNotifications();
    } else {
      unsubscribeNotifications();
      disconnect();
    }
  },
  { immediate: true }
);

// Also connect on mount if already authenticated
onMounted(() => {
  if (authStore.token) {
    connect(authStore.token);
    subscribeNotifications();
  }
});

function handleLogout() {
  unsubscribeNotifications();
  disconnect();
  authStore.logout();
  router.push({ name: 'login' });
}
</script>

<style>
html {
  overflow-y: auto !important;
}
</style>
