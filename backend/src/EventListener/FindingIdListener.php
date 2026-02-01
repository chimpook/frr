<?php

namespace App\EventListener;

use App\Entity\Finding;
use App\Service\FindingIdGenerator;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class FindingIdListener
{
    public function __construct(
        private FindingIdGenerator $idGenerator
    ) {}

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Finding) {
            return;
        }

        if ($entity->getId() === null) {
            $entity->setId($this->idGenerator->generateNextId());
        }
    }
}
