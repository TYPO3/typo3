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
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * CLI command for the 'scheduler' extension which executes
 */
class SchedulerCommand extends Command
{
    /**
     * @var bool
     */
    protected $hasTask = true;

    /**
     * @var Scheduler
     */
    protected $scheduler;

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

    public function __construct(Scheduler $scheduler)
    {
        $this->scheduler = $scheduler;
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $overwrittenTaskList = $input->getOption('task');
        $overwrittenTaskList = is_array($overwrittenTaskList) ? $overwrittenTaskList : [];
        $overwrittenTaskList = array_filter($overwrittenTaskList, static fn ($value) => MathUtility::canBeInterpretedAsInteger($value));
        $overwrittenTaskList = array_map('intval', $overwrittenTaskList);
        if ($overwrittenTaskList !== []) {
            $this->overwrittenTaskList = $overwrittenTaskList;
        }

        $this->forceExecution = (bool)$input->getOption('force');
        $this->stopTasks = $this->shouldStopTasks((bool)$input->getOption('stop'));
        $this->loopTasks();
        return 0;
    }

    /**
     * Checks if the tasks should be stopped instead of being executed.
     *
     * Stopping is only performed when the --stop option is provided together with the --task option.
     *
     * @param bool $stopOption
     * @return bool
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
     *
     * @param AbstractTask $task
     */
    protected function stopTask(AbstractTask $task)
    {
        $task->unmarkAllExecutions();

        if ($this->io->isVeryVerbose()) {
            $this->io->writeln(sprintf('Task #%d was stopped', $task->getTaskUid()));
        }
    }

    /**
     * Return task a task for a given UID
     *
     * @param int $taskUid
     * @return AbstractTask
     */
    protected function getTask(int $taskUid)
    {
        $force = $this->stopTasks || $this->forceExecution;
        if ($force) {
            return $this->scheduler->fetchTask($taskUid);
        }

        $whereClause = 'uid = ' . (int)$taskUid . ' AND nextexecution != 0 AND nextexecution <= ' . $GLOBALS['EXEC_TIME'];
        [$task] = $this->scheduler->fetchTasksWithCondition($whereClause);
        return $task;
    }

    /**
     * Execute tasks in loop that are ready to execute
     */
    protected function loopTasks()
    {
        do {
            // Try getting the next task and execute it
            // If there are no more tasks to execute, an exception is thrown by \TYPO3\CMS\Scheduler\Scheduler::fetchTask()
            try {
                $task = $this->fetchNextTask();
                try {
                    $this->executeOrStopTask($task);
                } catch (\Exception $e) {
                    if ($this->io->isVerbose()) {
                        $this->io->warning($e->getMessage());
                    }
                    // We ignore any exception that may have been thrown during execution,
                    // as this is a background process.
                    // The exception message has been recorded to the database anyway
                    continue;
                }
            } catch (\OutOfBoundsException $e) {
                if ($this->io->isVeryVerbose()) {
                    $this->io->writeln($e->getMessage());
                }
                $this->hasTask = !empty($this->overwrittenTaskList);
            } catch (\UnexpectedValueException $e) {
                if ($this->io->isVerbose()) {
                    $this->io->warning($e->getMessage());
                }
                continue;
            }
        } while ($this->hasTask);
        // Record the run in the system registry
        $this->scheduler->recordLastRun();
    }

    /**
     * When the --task option is provided, the next task is fetched from the provided task UIDs. Depending
     * on the --force option the task is fetched even if it is not marked for execution.
     *
     * Without the --task option we ask the scheduler for the next task with pending execution.
     *
     * @return AbstractTask
     * @throws \OutOfBoundsException When there are no more tasks to execute.
     * @throws \UnexpectedValueException When no task is found by the provided UID or the task is not marked for execution.
     */
    protected function fetchNextTask(): AbstractTask
    {
        if ($this->overwrittenTaskList === null) {
            return $this->scheduler->fetchTask();
        }

        if (count($this->overwrittenTaskList) === 0) {
            throw new \OutOfBoundsException('No more tasks to execute', 1547675594);
        }

        $taskUid = (int)array_shift($this->overwrittenTaskList);
        $task = $this->getTask($taskUid);
        if (!$this->scheduler->isValidTaskObject($task)) {
            throw new \UnexpectedValueException(
                sprintf('The task #%d is not scheduled for execution or does not exist.', $taskUid),
                1547675557
            );
        }
        return $task;
    }

    /**
     * When in stop mode the given task is stopped. Otherwise the task is executed.
     *
     * @param AbstractTask $task
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
