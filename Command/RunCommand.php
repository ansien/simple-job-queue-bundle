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

    /**
     * @var string
     */
    private $env;

    /**
     * @var bool
     */
    private $verbose;

    /**
     * @var OutputInterface
     */
    private $output;

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
        $this->env = $input->getOption('env');
        $this->verbose = $input->getOption('verbose');
        $this->output = $output;

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
        $this->output->writeln("Running job: {$job->getCommand()} (ID: {$job->getId()})");

        $job->setState(Job::STATE_RUNNING);
        $this->entityManager->flush();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->setCatchExceptions(true);

        $input = new ArrayInput(array_merge([
            'command' => $job->getCommand(),
            '--job-id' => $job->getId(),
        ], $job->getArgs()));

        $output = new BufferedOutput();

        try {
            ob_start();
            $application->run($input, $output);
        } catch (Exception $e) {
            $this->setJobOutput($job,Job::STATE_FAILED, $e->getMessage(), ob_get_clean());
            return;
        }

        $this->setJobOutput($job,Job::STATE_FINISHED, $output->fetch(), ob_get_clean());
    }

    private function setJobOutput(Job $job, string $state, string $bufferedOutput, string $otherOutput)
    {
        $job
            ->setState($state)
            ->setBufferedOutput($bufferedOutput)
            ->setOtherOutput($otherOutput);

        $this->entityManager->flush();
    }
}