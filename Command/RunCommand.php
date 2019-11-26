<?php

declare(strict_types=1);

namespace Ansien\SimpleJobQueueBundle\Command;

use Ansien\SimpleJobQueueBundle\Entity\Job;
use Ansien\SimpleJobQueueBundle\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

class RunCommand extends Command
{
    protected static $defaultName = 'simple-job-queue:run';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        JobRepository $jobRepository,
        KernelInterface $kernel,
        string $name = null
    ) {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->jobRepository = $jobRepository;
        $this->kernel = $kernel;

        $store = new FlockStore();
        $this->lockFactory = new LockFactory($store);
    }

    protected function configure()
    {
        $this
            ->setDescription('Execute all runnable jobs.');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        while (1) {
            $this->runMainLoop();
            sleep(5);
        }

        return 0;
    }

    private function runMainLoop()
    {
        $runnableJobs = $this->jobRepository->getRunnableJobs();

        foreach ($runnableJobs as $job) {
            $lock = $this->lockFactory->createLock("sjqb-job-{$job->getId()}");

            if ($lock->acquire()) {
                $this->runJob($job);
                $lock->release();
            }
        }
    }

    private function runJob(Job $job)
    {
        echo 'Running job: ' . $job->getId();

        $job->setState(Job::STATE_RUNNING);
        $this->entityManager->flush();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array_merge([
            'command' => $job->getCommand(),
            '--job-id' => $job->getId()
        ], $job->getArgs()));

        $output = new BufferedOutput();

        try {
            $application->run($input, $output);
        } catch (Exception $e) {
            $this->setJobOutput($job, $e->getMessage(), Job::STATE_FAILED);
            return;
        }

        $this->setJobOutput($job, (string) $output->fetch(), Job::STATE_FINISHED);
    }

    private function setJobOutput(Job $job, string $output, string $state)
    {
        $job
            ->setOutput($output)
            ->setState($state);

        $this->entityManager->flush();
    }
}