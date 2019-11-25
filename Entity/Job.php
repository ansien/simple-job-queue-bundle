<?php

declare(strict_types=1);

namespace Ansien\SimpleJobQueueBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="sjqb_jobs")
 */
class Job
{
    // region Constants

    /**
     * @var string
     */
    public const STATE_NEW = 'new';

    /**
     * @var string
     */
    public const STATE_RUNNING = 'running';

    /**
     * @var string
     */
    public const STATE_FINISHED = 'finished';

    /**
     * @var string
     */
    public const STATE_FAILED = 'failed';

    /**
     * @var string
     */
    public const STATE_TERMINATED = 'terminated';

    // endregion

    // region Properties

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false)
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=15, nullable=false)
     */
    private $state;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $command;

    /**
     * @var array
     * @ORM\Column(type="json_array", nullable=false)
     */
    private $args;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $output;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $createdAt;

    // endregion

    public function __construct(string $command, ?array $args = [])
    {
        $this->state = self::STATE_NEW;
        $this->command = $command;
        $this->args = $args;
        $this->createdAt = new DateTime();
    }

    // region Getters

    public function getId(): int
    {
        return $this->id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    // endregion

    // region Setters

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function setOutput(string $output): self
    {
        $this->output = $output;

        return $this;
    }

    // endregion
}