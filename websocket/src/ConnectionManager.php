<?php

namespace App;

use Ratchet\ConnectionInterface;

class ConnectionManager
{
    /** @var array<int, ConnectionInterface> */
    private array $connections = [];

    /** @var array<int, array{userId: int|string, email: string}> */
    private array $connectionData = [];

    public function add(ConnectionInterface $conn): void
    {
        $this->connections[$conn->resourceId] = $conn;
    }

    public function remove(ConnectionInterface $conn): void
    {
        unset($this->connections[$conn->resourceId]);
        unset($this->connectionData[$conn->resourceId]);
    }

    public function authenticate(ConnectionInterface $conn, int|string $userId, string $email): void
    {
        $this->connectionData[$conn->resourceId] = [
            'userId' => $userId,
            'email' => $email,
        ];
    }

    public function isAuthenticated(ConnectionInterface $conn): bool
    {
        return isset($this->connectionData[$conn->resourceId]);
    }

    public function getUserId(ConnectionInterface $conn): int|string|null
    {
        return $this->connectionData[$conn->resourceId]['userId'] ?? null;
    }

    /**
     * Get all authenticated connections
     * @return array<int, ConnectionInterface>
     */
    public function getAuthenticatedConnections(): array
    {
        return array_filter(
            $this->connections,
            fn(ConnectionInterface $conn) => $this->isAuthenticated($conn)
        );
    }

    /**
     * Broadcast message to all authenticated connections
     */
    public function broadcast(string $message): void
    {
        foreach ($this->getAuthenticatedConnections() as $conn) {
            $conn->send($message);
        }
    }

    public function getConnectionCount(): int
    {
        return count($this->connections);
    }

    public function getAuthenticatedCount(): int
    {
        return count($this->connectionData);
    }
}
