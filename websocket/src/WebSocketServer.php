<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketServer implements MessageComponentInterface
{
    private ConnectionManager $connectionManager;
    private JwtAuthenticator $authenticator;

    public function __construct(ConnectionManager $connectionManager, JwtAuthenticator $authenticator)
    {
        $this->connectionManager = $connectionManager;
        $this->authenticator = $authenticator;
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->connectionManager->add($conn);
        echo "New connection: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $data = json_decode($msg, true);

        if ($data === null) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Invalid JSON']));
            return;
        }

        $type = $data['type'] ?? '';

        switch ($type) {
            case 'auth':
                $this->handleAuth($from, $data);
                break;

            case 'ping':
                $from->send(json_encode(['type' => 'pong']));
                break;

            default:
                if (!$this->connectionManager->isAuthenticated($from)) {
                    $from->send(json_encode(['type' => 'error', 'message' => 'Not authenticated']));
                    return;
                }
                // Handle other message types if needed
                break;
        }
    }

    private function handleAuth(ConnectionInterface $conn, array $data): void
    {
        $token = $data['token'] ?? '';

        if (empty($token)) {
            $conn->send(json_encode(['type' => 'auth_error', 'message' => 'Token required']));
            return;
        }

        $userData = $this->authenticator->validate($token);

        if ($userData === null) {
            $conn->send(json_encode(['type' => 'auth_error', 'message' => 'Invalid token']));
            return;
        }

        $this->connectionManager->authenticate($conn, $userData['userId'], $userData['email']);

        $conn->send(json_encode([
            'type' => 'auth_success',
            'userId' => $userData['userId'],
        ]));

        echo "Connection {$conn->resourceId} authenticated as user {$userData['userId']}\n";
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->connectionManager->remove($conn);
        echo "Connection {$conn->resourceId} disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "Error on connection {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }

    public function getConnectionManager(): ConnectionManager
    {
        return $this->connectionManager;
    }
}
