<template>
  <v-tooltip :text="statusText" location="bottom">
    <template #activator="{ props }">
      <div v-bind="props" class="ws-status-indicator">
        <v-icon :color="statusColor" :class="{ 'pulse': isPulsing }" size="small">
          mdi-circle
        </v-icon>
      </div>
    </template>
  </v-tooltip>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useWebSocket } from '@/composables/useWebSocket';

const { status } = useWebSocket();

const statusColor = computed(() => {
  switch (status.value) {
    case 'authenticated':
      return 'success';
    case 'connecting':
      return 'warning';
    case 'error':
      return 'error';
    default:
      return 'grey';
  }
});

const statusText = computed(() => {
  switch (status.value) {
    case 'authenticated':
      return 'Real-time updates connected';
    case 'connecting':
      return 'Connecting to real-time updates...';
    case 'error':
      return 'Connection error - retrying...';
    default:
      return 'Real-time updates disconnected';
  }
});

const isPulsing = computed(() => status.value === 'connecting');
</script>

<style scoped>
.ws-status-indicator {
  display: flex;
  align-items: center;
  padding: 0 8px;
  cursor: default;
}

.pulse {
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.4;
  }
}
</style>
