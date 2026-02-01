import { inject } from 'vue';
import { useWebSocket } from './useWebSocket';
import { useFindingsStore } from '@/stores/findings';
import type { FindingWebSocketMessage } from '@/types/WebSocket';

export function useRealtimeNotifications() {
  const { onMessage, offMessage, currentUserId } = useWebSocket();
  const findingsStore = useFindingsStore();
  const showSnackbar = inject<(message: string, color?: string) => void>('showSnackbar');

  function handleMessage(message: FindingWebSocketMessage): void {
    // Ignore messages from current user
    if (message.userId === currentUserId.value) {
      return;
    }

    const findingId = message.data.id;

    switch (message.type) {
      case 'finding.created':
        showSnackbar?.(`New finding ${findingId} added`, 'info');
        break;

      case 'finding.updated':
        showSnackbar?.(`Finding ${findingId} was updated`, 'info');
        break;

      case 'finding.deleted':
        showSnackbar?.(`Finding ${findingId} was deleted`, 'warning');
        break;
    }

    // Delegate to store for state updates
    findingsStore.handleWebSocketMessage(message);
  }

  function subscribe(): void {
    onMessage(handleMessage);
  }

  function unsubscribe(): void {
    offMessage(handleMessage);
  }

  return {
    subscribe,
    unsubscribe,
  };
}
