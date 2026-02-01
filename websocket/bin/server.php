#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use App\WebSocketServer;
use App\ConnectionManager;
use App\JwtAuthenticator;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Clue\React\Redis\Factory as RedisFactory;

$port = (int) ($_ENV['WS_PORT'] ?? 8081);
$redisHost = $_ENV['REDIS_HOST'] ?? 'redis';
$redisPort = (int) ($_ENV['REDIS_PORT'] ?? 6379);
$jwtPublicKey = $_ENV['JWT_PUBLIC_KEY'] ?? '/app/jwt/public.pem';
$channel = 'findings';

echo "Starting WebSocket server...\n";
echo "Port: {$port}\n";
echo "Redis: {$redisHost}:{$redisPort}\n";
echo "JWT Public Key: {$jwtPublicKey}\n";

// Create shared components
$connectionManager = new ConnectionManager();
$authenticator = new JwtAuthenticator($jwtPublicKey);
$wsServer = new WebSocketServer($connectionManager, $authenticator);

// Create React event loop
$loop = Loop::get();

// Start WebSocket server on the event loop
$socket = new SocketServer("0.0.0.0:{$port}", [], $loop);
$server = new IoServer(
    new HttpServer(
        new WsServer($wsServer)
    ),
    $socket,
    $loop
);

echo "WebSocket server running on port {$port}\n";

// Create async Redis client for pub/sub
$redisFactory = new RedisFactory($loop);

$redisFactory->createClient("redis://{$redisHost}:{$redisPort}")->then(
    function ($redis) use ($channel, $connectionManager) {
        echo "Connected to Redis\n";

        // Subscribe to the findings channel
        $redis->subscribe($channel)->then(function () use ($channel) {
            echo "Subscribed to Redis channel: {$channel}\n";
        });

        // Handle incoming messages
        $redis->on('message', function ($channel, $payload) use ($connectionManager) {
            echo "Received Redis message on {$channel}: {$payload}\n";

            // Broadcast to all authenticated WebSocket connections
            $connectionManager->broadcast($payload);

            $connectedCount = $connectionManager->getAuthenticatedCount();
            echo "Broadcasted to {$connectedCount} authenticated connections\n";
        });

        $redis->on('close', function () {
            echo "Redis connection closed\n";
        });

        $redis->on('error', function (\Exception $e) {
            echo "Redis error: " . $e->getMessage() . "\n";
        });
    },
    function (\Exception $e) use ($loop, $redisHost, $redisPort) {
        echo "Failed to connect to Redis: " . $e->getMessage() . "\n";
        echo "Will retry in 5 seconds...\n";

        // Retry connection after delay
        $loop->addTimer(5, function () {
            echo "Retrying Redis connection...\n";
            // The process should be restarted by Docker to retry
        });
    }
);

// Handle graceful shutdown
if (function_exists('pcntl_signal')) {
    pcntl_async_signals(true);

    pcntl_signal(SIGTERM, function () use ($loop) {
        echo "Received SIGTERM, shutting down...\n";
        $loop->stop();
    });

    pcntl_signal(SIGINT, function () use ($loop) {
        echo "Received SIGINT, shutting down...\n";
        $loop->stop();
    });
}

// Run the event loop
$loop->run();
