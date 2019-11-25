<?php

declare(strict_types=1);

namespace Ansien\SimpleJobQueueBundle\Service;

use Ansien\SimpleJobQueueBundle\Entity\Job;
use Doctrine\ORM\EntityManagerInterface;

class SimpleJobService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createJob(string $command, ?array $args = []): void
    {
        $job = new Job($command, $args);

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }
}