<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Scheduler\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Validation\Validator\TaskValidator;

/**
 * CLI command for the 'scheduler' extension which executes
 */
class SchedulerCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * Array of tasks UIDs that should be executed. Null if task option is not provided.
     *
     * @var int[]|null
     */
    protected $overwrittenTaskList;

    /**
     * This is true when the tasks should be marked as stopped instead of being executed.
     *
     * @var bool
     */
    protected $stopTasks = false;

    /**
     * @var bool
     */
    protected $forceExecution;

    public function __construct(
        protected readonly Scheduler $scheduler,
        protected readonly SchedulerTaskRepository $taskRepository
    ) {
        parent::__construct();
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setHelp('If no parameter is given, the scheduler executes any tasks that are overdue to run.
Call it like this: typo3/sysext/core/bin/typo3 scheduler:run --task=13 -f')
            ->addOption(
                'task',
                'i',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'UID of a specific task. Can be provided multiple times to execute multiple tasks sequentially.'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force execution of the task which is passed with --task option'
            )
            ->addOption(
                'stop',
                's',
                InputOption::VALUE_NONE,
                'Stop the task which is passed with --task option'
            );
    }

    /**
     * Execute scheduler tasks
     *
     * @todo: this should at some point become a protected method
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $overwrittenTaskList = $input->getOption('task');
        $overwrittenTaskList = is_array($overwrittenTaskList) ? $overwrittenTaskList : [];
        $overwrittenTaskList = array_filter($overwrittenTaskList, static fn($value) => MathUtility::canBeInterpretedAsInteger($value));
        $overwrittenTaskList = array_map('intval', $overwrittenTaskList);
        if ($overwrittenTaskList !== []) {
            $this->overwrittenTaskList = $overwrittenTaskList;
        }

        $this->forceExecution = (bool)$input->getOption('force');
        $this->stopTasks = $this->shouldStopTasks((bool)$input->getOption('stop'));
        return $this->loopTasks() ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Checks if the tasks should be stopped instead of being executed.
     *
     * Stopping is only performed when the --stop option is provided together with the --task option.
     *
     * @param bool $stopOption
     */
    protected function shouldStopTasks(bool $stopOption): bool
    {
        if (!$stopOption) {
            return false;
        }

        if ($this->overwrittenTaskList !== []) {
            return true;
        }

        if ($this->io->isVerbose()) {
            $this->io->warning('Stopping tasks is only possible when the --task option is provided.');
        }
        return false;
    }

    /**
     * Stop task
     */
    protected function stopTask(AbstractTask $task)
    {
        $this->taskRepository->removeAllRegisteredExecutionsForTask($task);
        if ($this->io->isVeryVerbose()) {
            $this->io->writeln(sprintf('Task #%d was stopped', $task->getTaskUid()));
        }
    }

    /**
     * Return task a task for a given UID
     */
    protected function getTask(int $taskUid): ?AbstractTask
    {
        $force = $this->stopTasks || $this->forceExecution;
        if ($force) {
            return $this->taskRepository->findByUid($taskUid);
        }

        return $this->taskRepository->findNextExecutableTaskForUid($taskUid);
    }

    /**
     * Execute tasks in loop that are ready to execute
     */
    protected function loopTasks(): bool
    {
        $hasError = false;
        do {
            $task = null;
            // Try getting the next task and execute it
            // If there are no more tasks to execute, an exception is thrown by \TYPO3\CMS\Scheduler\Scheduler::fetchTask()
            try {
                $task = $this->fetchNextTask();
                if ($task === null) {
                    break;
                }
                try {
                    $this->executeOrStopTask($task);
                } catch (\Exception $e) {
                    $this->io->getErrorStyle()->error($e->getMessage());
                    $hasError = true;
                    // We ignore any exception that may have been thrown during execution,
                    // as this is a background process.
                    // The exception message has been recorded to the database anyway
                    continue;
                }
            } catch (\UnexpectedValueException $e) {
                $this->io->getErrorStyle()->error($e->getMessage());
                $hasError = true;
                continue;
            }
        } while ($task !== null);
        // Record the run in the system registry
        $this->scheduler->recordLastRun();
        return !$hasError;
    }

    /**
     * When the --task option is provided, the next task is fetched from the provided task UIDs. Depending
     * on the --force option the task is fetched even if it is not marked for execution.
     *
     * Without the --task option we ask the scheduler for the next task with pending execution.
     *
     * @throws \UnexpectedValueException When no task is found by the provided UID or the task is not marked for execution.
     */
    protected function fetchNextTask(): ?AbstractTask
    {
        if ($this->overwrittenTaskList === null) {
            return $this->taskRepository->findNextExecutableTask();
        }

        if (count($this->overwrittenTaskList) === 0) {
            return null;
        }

        $taskUid = (int)array_shift($this->overwrittenTaskList);
        $task = $this->getTask($taskUid);
        if (!(new TaskValidator())->isValid($task)) {
            throw new \UnexpectedValueException(
                sprintf('The task #%d is not scheduled for execution or does not exist.', $taskUid),
                1547675557
            );
        }
        return $task;
    }

    /**
     * When in stop mode the given task is stopped. Otherwise the task is executed.
     */
    protected function executeOrStopTask(AbstractTask $task): void
    {
        if ($this->stopTasks) {
            $this->stopTask($task);
            return;
        }

        $this->scheduler->executeTask($task);
        if ($this->io->isVeryVerbose()) {
            $this->io->writeln(sprintf('Task #%d was executed', $task->getTaskUid()));
        }
    }
}
