<?php

namespace App\Event;

use App\Entity\Finding;
use Symfony\Contracts\EventDispatcher\Event;

class FindingEvent extends Event
{
    public const CREATED = 'finding.created';
    public const UPDATED = 'finding.updated';
    public const DELETED = 'finding.deleted';

    public function __construct(
        private string $type,
        private array $data,
        private int|string $userId
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getUserId(): int|string
    {
        return $this->userId;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'userId' => $this->userId,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function created(Finding $finding, int|string $userId): self
    {
        return new self(self::CREATED, $finding->toArray(), $userId);
    }

    public static function updated(Finding $finding, int|string $userId): self
    {
        return new self(self::UPDATED, $finding->toArray(), $userId);
    }

    public static function deleted(array $findingData, int|string $userId): self
    {
        return new self(self::DELETED, $findingData, $userId);
    }
}
