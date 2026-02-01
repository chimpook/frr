import type { Finding } from './Finding';

export type WebSocketStatus = 'disconnected' | 'connecting' | 'authenticated' | 'error';

export interface FindingWebSocketMessage {
  type: 'finding.created' | 'finding.updated' | 'finding.deleted';
  data: Finding;
  userId: number;
  timestamp: string;
}

export interface WebSocketAuthMessage {
  type: 'auth';
  token: string;
}

export interface WebSocketAuthSuccessMessage {
  type: 'auth_success';
  userId: number;
}

export interface WebSocketAuthErrorMessage {
  type: 'auth_error';
  message: string;
}

export interface WebSocketErrorMessage {
  type: 'error';
  message: string;
}

export interface WebSocketPongMessage {
  type: 'pong';
}

export type WebSocketIncomingMessage =
  | FindingWebSocketMessage
  | WebSocketAuthSuccessMessage
  | WebSocketAuthErrorMessage
  | WebSocketErrorMessage
  | WebSocketPongMessage;
