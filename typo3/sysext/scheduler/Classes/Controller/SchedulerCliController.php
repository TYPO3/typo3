<?php
namespace TYPO3\CMS\Scheduler\Controller;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * CLI controller for the 'scheduler' extension.
 */
class SchedulerCliController
{
    /**
     * @var \TYPO3\CMS\Core\Controller\CommandLineController
     */
    protected $cli;

    /**
     * @var bool
     */
    protected $hasTask = true;

    /**
     * @var \TYPO3\CMS\Scheduler\Scheduler
     */
    protected $scheduler;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cli = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Controller\CommandLineController::class);
        $this->scheduler = GeneralUtility::makeInstance(\TYPO3\CMS\Scheduler\Scheduler::class);

        // Empty options array here because Scheduler uses "-s" as argument too
        $this->cli->cli_options = [];
        $this->cli->cli_options[] = ['-h', 'Show this output'];
        $this->cli->cli_options[] = ['--help', 'Same as -h'];
        $this->cli->cli_options[] = ['-s', 'Stop the task which is passed with -i option'];
        $this->cli->cli_options[] = ['-i', 'UID of an task'];
        $this->cli->cli_options[] = ['-f', 'Force execution of the task which is passed with -i option'];

        // Setting help texts:
        $this->cli->cli_help['name'] = 'scheduler -- Start the TYPO3 Scheduler from the command line';
        $this->cli->cli_help['synopsis'] = '###OPTIONS###';
        $this->cli->cli_help['description'] = 'This command line starts any task';
        $this->cli->cli_help['examples'] = 'typo3/cli_dispatch.phpsh scheduler';
        unset($this->cli->cli_help['author']);
    }

    /**
     * Check cli is help
     *
     * @return bool
     */
    protected function isHelp()
    {
        return ($this->cli->cli_isArg('--help') && $this->cli->cli_isArg('--help') > 0)
            || ($this->cli->cli_isArg('-h') && $this->cli->cli_isArg('-h') > 0);
    }

    /**
     * Execute scheduler tasks
     */
    public function run()
    {
        if ($this->isHelp()) {
            $this->cli->cli_help();
            return;
        }

        if ($this->cli->cli_isArg('-i') && $this->cli->cli_isArg('-i') > 0) {
            /** @var $task \TYPO3\CMS\Scheduler\Task\AbstractTask */
            $task = $this->getTask();
            if ($this->scheduler->isValidTaskObject($task)) {
                if ($this->cli->cli_isArg('-s')) {
                    $this->stopTask($task);
                } else {
                    $this->scheduler->executeTask($task);
                }

                // Record the run in the system registry
                $this->scheduler->recordLastRun('cli-by-id');
            }
            return;
        }
        $this->loopTasks();
    }

    /**
     * Stop task
     *
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task
     */
    protected function stopTask($task)
    {
        if ($this->scheduler->isValidTaskObject($task)) {
            $result = $task->unmarkAllExecutions();
        }
    }

    /**
     * Return task
     *
     * @return \TYPO3\CMS\Scheduler\Task\AbstractTask
     */
    protected function getTask()
    {
        $taskId = (int)$this->cli->cli_argValue('-i');

        if ($this->cli->cli_isArg('-f') || $this->cli->cli_isArg('-s')) {
            $task = $this->scheduler->fetchTask($taskId);
        } else {
            $whereClause = 'uid = ' . $taskId . ' AND nextexecution != 0 AND nextexecution <= ' . $GLOBALS['EXEC_TIME'];
            list($task) = $this->scheduler->fetchTasksWithCondition($whereClause);
        }

        return $task;
    }

    /**
     * Execute tasks in loop
     */
    protected function loopTasks()
    {
        do {
            // Try getting the next task and execute it
            // If there are no more tasks to execute, an exception is thrown by \TYPO3\CMS\Scheduler\Scheduler::fetchTask()
            try {
                /** @var $task \TYPO3\CMS\Scheduler\Task\AbstractTask */
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
