<?php
namespace TYPO3\CMS\Scheduler\Command;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Start the TYPO3 Scheduler from the command line.')
            ->setHelp('If no parameter is given, the scheduler executes any tasks that are overdue to run.
Call it like this: typo3/sysext/core/bin/typo3 scheduler:run --task=13 -f')
            ->addOption(
                'task',
                'i',
                InputOption::VALUE_REQUIRED,
                'UID of a specific task'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force execution of the task which is passed with -i option'
            )
            ->addOption(
                'stop',
                's',
                InputOption::VALUE_NONE,
                'Stop the task which is passed with -i option'
            );
    }

    /**
     * Execute scheduler tasks
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $this->scheduler = GeneralUtility::makeInstance(Scheduler::class);

        if ((int)$input->getOption('task') > 0) {
            $taskUid = (int)$input->getOption('task');
            $stopTask = (bool)($input->hasOption('stop') && $input->getOption('stop'));
            $force = (bool)($input->hasOption('force') && $input->getOption('force'));
            $task = $this->getTask($taskUid, $stopTask || $force);

            if ($this->scheduler->isValidTaskObject($task)) {
                if ($stopTask) {
                    $this->stopTask($task);
                } else {
                    $this->scheduler->executeTask($task);
                }
            }
            // Record the run in the system registry
            $this->scheduler->recordLastRun('cli-by-id');
        } else {
            $this->loopTasks();
        }
    }

    /**
     * Stop task
     *
     * @param AbstractTask $task
     */
    protected function stopTask($task)
    {
        if ($this->scheduler->isValidTaskObject($task)) {
            $task->unmarkAllExecutions();
        }
    }

    /**
     * Return task a task for a given UID
     *
     * @param int $taskUid
     * @param bool $force fetch the task regardless if it is queued for execution
     * @return AbstractTask
     */
    protected function getTask(int $taskUid, bool $force)
    {
        if ($force) {
            $task = $this->scheduler->fetchTask($taskUid);
        } else {
            $whereClause = 'uid = ' . (int)$taskUid . ' AND nextexecution != 0 AND nextexecution <= ' . $GLOBALS['EXEC_TIME'];
            list($task) = $this->scheduler->fetchTasksWithCondition($whereClause);
        }

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
                $task = $this->scheduler->fetchTask();
                try {
                    $this->scheduler->executeTask($task);
                } catch (\Exception $e) {
                    // We ignore any exception that may have been thrown during execution,
                    // as this is a background process.
                    // The exception message has been recorded to the database anyway
                    continue;
                }
            } catch (\OutOfBoundsException $e) {
                $this->hasTask = false;
            } catch (\UnexpectedValueException $e) {
                continue;
            }
        } while ($this->hasTask);
        // Record the run in the system registry
        $this->scheduler->recordLastRun();
    }
}
