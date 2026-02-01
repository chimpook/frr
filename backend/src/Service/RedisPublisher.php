<?php

namespace App\Service;

use Predis\Client as RedisClient;

class RedisPublisher
{
    private RedisClient $redis;
    private string $channel;

    public function __construct(string $redisHost, int $redisPort, string $channel = 'findings')
    {
        $this->redis = new RedisClient([
            'scheme' => 'tcp',
            'host' => $redisHost,
            'port' => $redisPort,
        ]);
        $this->channel = $channel;
    }

    public function publish(array $message): void
    {
        $payload = json_encode($message);
        $this->redis->publish($this->channel, $payload);
    }
}
