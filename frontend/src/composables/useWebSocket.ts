import { ref, computed, readonly } from 'vue';
import type { WebSocketStatus, WebSocketIncomingMessage, FindingWebSocketMessage } from '@/types/WebSocket';

const WS_URL = process.env.VITE_WS_URL || 'ws://localhost:8081';

// Singleton state
let ws: WebSocket | null = null;
const status = ref<WebSocketStatus>('disconnected');
const currentUserId = ref<number | null>(null);
const messageHandlers = new Set<(msg: FindingWebSocketMessage) => void>();

// Reconnection state
let reconnectAttempts = 0;
let reconnectTimeout: ReturnType<typeof setTimeout> | null = null;
const MAX_RECONNECT_DELAY = 30000;
const BASE_RECONNECT_DELAY = 1000;

function getReconnectDelay(): number {
  const delay = Math.min(BASE_RECONNECT_DELAY * Math.pow(2, reconnectAttempts), MAX_RECONNECT_DELAY);
  return delay;
}

function clearReconnectTimeout(): void {
  if (reconnectTimeout) {
    clearTimeout(reconnectTimeout);
    reconnectTimeout = null;
  }
}

function scheduleReconnect(token: string): void {
  clearReconnectTimeout();
  const delay = getReconnectDelay();
  console.log(`[WebSocket] Scheduling reconnect in ${delay}ms (attempt ${reconnectAttempts + 1})`);
  reconnectTimeout = setTimeout(() => {
    reconnectAttempts++;
    connect(token);
  }, delay);
}

function connect(token: string): void {
  if (ws && (ws.readyState === WebSocket.CONNECTING || ws.readyState === WebSocket.OPEN)) {
    console.log('[WebSocket] Already connected or connecting');
    return;
  }

  clearReconnectTimeout();
  status.value = 'connecting';

  try {
    ws = new WebSocket(WS_URL);

    ws.onopen = () => {
      console.log('[WebSocket] Connected, authenticating...');
      ws?.send(JSON.stringify({ type: 'auth', token }));
    };

    ws.onmessage = (event) => {
      try {
        const message: WebSocketIncomingMessage = JSON.parse(event.data);
        handleMessage(message, token);
      } catch (e) {
        console.error('[WebSocket] Failed to parse message:', e);
      }
    };

    ws.onclose = (event) => {
      console.log(`[WebSocket] Closed: code=${event.code}, reason=${event.reason}`);
      ws = null;

      if (status.value === 'authenticated') {
        status.value = 'disconnected';
        scheduleReconnect(token);
      } else {
        status.value = 'disconnected';
      }
    };

    ws.onerror = (error) => {
      console.error('[WebSocket] Error:', error);
      status.value = 'error';
    };
  } catch (e) {
    console.error('[WebSocket] Failed to create connection:', e);
    status.value = 'error';
    scheduleReconnect(token);
  }
}

function handleMessage(message: WebSocketIncomingMessage, token: string): void {
  switch (message.type) {
    case 'auth_success':
      console.log('[WebSocket] Authenticated as user', message.userId);
      status.value = 'authenticated';
      currentUserId.value = message.userId;
      reconnectAttempts = 0;
      break;

    case 'auth_error':
      console.error('[WebSocket] Authentication failed:', message.message);
      status.value = 'error';
      disconnect();
      break;

    case 'error':
      console.error('[WebSocket] Server error:', message.message);
      break;

    case 'pong':
      // Heartbeat response
      break;

    case 'finding.created':
    case 'finding.updated':
    case 'finding.deleted':
      messageHandlers.forEach((handler) => handler(message));
      break;
  }
}

function disconnect(): void {
  clearReconnectTimeout();
  reconnectAttempts = 0;

  if (ws) {
    ws.close();
    ws = null;
  }

  status.value = 'disconnected';
  currentUserId.value = null;
}

function onMessage(handler: (msg: FindingWebSocketMessage) => void): void {
  messageHandlers.add(handler);
}

function offMessage(handler: (msg: FindingWebSocketMessage) => void): void {
  messageHandlers.delete(handler);
}

export function useWebSocket() {
  const isConnected = computed(() => status.value === 'authenticated');

  return {
    status: readonly(status),
    isConnected,
    currentUserId: readonly(currentUserId),
    connect,
    disconnect,
    onMessage,
    offMessage,
  };
}
