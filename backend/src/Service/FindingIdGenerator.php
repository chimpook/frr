<?php

namespace App\Service;

use App\Repository\FindingRepository;

class FindingIdGenerator
{
    public function __construct(
        private FindingRepository $findingRepository
    ) {}

    public function generateNextId(): string
    {
        $nextNumber = $this->findingRepository->getNextSequenceNumber();
        return sprintf('SF%d', $nextNumber);
    }
}
