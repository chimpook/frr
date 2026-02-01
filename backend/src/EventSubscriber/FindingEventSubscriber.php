<?php

namespace App\EventSubscriber;

use App\Event\FindingEvent;
use App\Service\RedisPublisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FindingEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private RedisPublisher $redisPublisher)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FindingEvent::CREATED => 'onFindingEvent',
            FindingEvent::UPDATED => 'onFindingEvent',
            FindingEvent::DELETED => 'onFindingEvent',
        ];
    }

    public function onFindingEvent(FindingEvent $event): void
    {
        $this->redisPublisher->publish($event->toArray());
    }
}
