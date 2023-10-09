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

namespace TYPO3\CMS\Scheduler\Task;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Scheduler;

/**
 * This is the base class for all Scheduler tasks
 * It's an abstract class, not designed to be instantiated directly
 * All Scheduler tasks should inherit from this class
 */
abstract class AbstractTask implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const TYPE_SINGLE = 1;
    public const TYPE_RECURRING = 2;

    /**
     * Reference to a scheduler object
     *
     * @var \TYPO3\CMS\Scheduler\Scheduler|null
     */
    protected $scheduler;

    /**
     * The unique id of the task used to identify it in the database.
     */
    protected int $taskUid = 0;

    /**
     * Disable flag, TRUE if task is disabled, FALSE otherwise
     *
     * @var bool
     */
    protected $disabled = false;

    /**
     * Run on next cron job flag, TRUE if task should run on next cronjob, FALSE otherwise
     *
     * @var bool
     */
    protected $runOnNextCronJob = false;

    /**
     * The execution object related to the task
     *
     * @var Execution
     */
    protected $execution;

    /**
     * This variable contains the time of next execution of the task
     *
     * @var int
     */
    protected $executionTime = 0;

    /**
     * Description for the task
     */
    protected string $description = '';

    /**
     * Task group for this task
     *
     * @var int|null
     */
    protected $taskGroup = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Using makeInstance instead of setScheduler() here as the logger is injected due to LoggerAwareTrait
        $this->scheduler = GeneralUtility::makeInstance(Scheduler::class);
        $this->execution = GeneralUtility::makeInstance(Execution::class);
    }

    /**
     * This is the main method that is called when a task is executed
     * It MUST be implemented by all classes inheriting from this one
     * Note that there is no error handling, errors and failures are expected
     * to be handled and logged by the client implementations.
     * Should return TRUE on successful execution, FALSE on error.
     *
     * @return bool Returns TRUE on successful execution, FALSE on error
     */
    abstract public function execute();

    /**
     * This method is designed to return some additional information about the task,
     * that may help to set it apart from other tasks from the same class
     * This additional information is used - for example - in the Scheduler's BE module
     * This method should be implemented in most task classes
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        return '';
    }

    /**
     * This method is used to set the unique id of the task
     *
     * @param int $id Primary key (from the database record) of the scheduled task
     */
    public function setTaskUid($id): void
    {
        $this->taskUid = (int)$id;
    }

    /**
     * This method returns the unique id of the task
     *
     * @return int The id of the task
     */
    public function getTaskUid(): int
    {
        return $this->taskUid;
    }

    /**
     * This method returns the title of the scheduler task
     *
     * @return string
     */
    public function getTaskTitle()
    {
        return $this->getLanguageService()->sL($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][static::class]['title'] ?? '');
    }

    /**
     * This method returns the description of the scheduler task
     *
     * @return string
     */
    public function getTaskDescription()
    {
        return $this->getLanguageService()->sL($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][static::class]['description'] ?? '');
    }

    /**
     * This method returns the class name of the scheduler task
     *
     * @return string
     */
    public function getTaskClassName()
    {
        return static::class;
    }

    /**
     * This method returns the disabled status of the task
     *
     * @return bool TRUE if task is disabled, FALSE otherwise
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * This method is used to set the disabled status of the task
     *
     * @param bool $flag TRUE if task should be disabled, FALSE otherwise
     */
    public function setDisabled($flag)
    {
        if ($flag) {
            $this->disabled = true;
        } else {
            $this->disabled = false;
        }
    }

    /**
     * This method set the flag for next cron job execution
     *
     * @param bool $flag TRUE if task should run with the next cron job, FALSE otherwise
     */
    public function setRunOnNextCronJob($flag)
    {
        $this->runOnNextCronJob = $flag;
    }

    /**
     * This method returns the run on next cron job status of the task
     *
     * @return bool TRUE if task should run on next cron job, FALSE otherwise
     */
    public function getRunOnNextCronJob()
    {
        return $this->runOnNextCronJob;
    }

    /**
     * This method is used to set the timestamp corresponding to the next execution time of the task
     *
     * @param int $timestamp Timestamp of next execution
     */
    public function setExecutionTime($timestamp)
    {
        $this->executionTime = (int)$timestamp;
    }

    /**
     * This method returns the task group (uid) of the task
     *
     * @return int|null Uid of task group or null if it came back from the DB without the task group set.
     */
    public function getTaskGroup()
    {
        return $this->taskGroup;
    }

    /**
     * This method is used to set the task group (uid) of the task
     *
     * @param int $taskGroup Uid of task group
     */
    public function setTaskGroup($taskGroup)
    {
        $this->taskGroup = (int)$taskGroup;
    }

    /**
     * This method returns the timestamp corresponding to the next execution time of the task
     *
     * @return int Timestamp of next execution
     */
    public function getExecutionTime()
    {
        return $this->executionTime;
    }

    /**
     * This method is used to set the description of the task
     *
     * @param string $description Description
     */
    public function setDescription($description): void
    {
        $this->description = (string)$description;
    }

    /**
     * This method returns the description of the task
     *
     * @return string Description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the internal reference to the singleton instance of the Scheduler
     * and the logger instance in case it was unserialized
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function setScheduler()
    {
        $this->scheduler = GeneralUtility::makeInstance(Scheduler::class);
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Unsets the internal reference to the singleton instance of the Scheduler
     * and the logger instance.
     * This is done before a task is serialized, so that the scheduler instance
     * and the logger instance are not saved to the database
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function unsetScheduler()
    {
        $this->scheduler = null;
        unset($this->logger);
    }

    /**
     * Registers a single execution of the task
     *
     * @param int $timestamp Timestamp of the next execution
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function registerSingleExecution($timestamp)
    {
        $execution = GeneralUtility::makeInstance(Execution::class);
        $execution->setStart($timestamp);
        $execution->setInterval(0);
        $execution->setEnd($timestamp);
        $execution->setCronCmd('');
        $execution->setMultiple(false);
        $execution->setIsNewSingleExecution(true);
        // Replace existing execution object
        $this->execution = $execution;
    }

    /**
     * Registers a recurring execution of the task
     *
     * @param int $start The first date/time where this execution should occur (timestamp)
     * @param int $interval Execution interval in seconds
     * @param int $end The last date/time where this execution should occur (timestamp)
     * @param bool $multiple Set to FALSE if multiple executions of this task are not permitted in parallel
     * @param string $cron_cmd Used like in crontab (minute hour day month weekday)
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function registerRecurringExecution($start, $interval, $end = 0, $multiple = false, $cron_cmd = '')
    {
        $execution = GeneralUtility::makeInstance(Execution::class);
        // Set general values
        $execution->setStart($start);
        $execution->setEnd($end);
        $execution->setMultiple($multiple);
        if (empty($cron_cmd)) {
            // Use interval
            $execution->setInterval($interval);
            $execution->setCronCmd('');
        } else {
            // Use cron syntax
            $execution->setInterval(0);
            $execution->setCronCmd($cron_cmd);
        }
        // Replace existing execution object
        $this->execution = $execution;
    }

    /**
     * Sets the internal execution object
     *
     * @param Execution $execution The execution to add
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function setExecution(Execution $execution)
    {
        $this->execution = $execution;
    }

    /**
     * Returns the execution object
     *
     * @return Execution The internal execution object
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function getExecution()
    {
        return $this->execution;
    }

    /**
     * Returns the timestamp for next due execution of the task
     *
     * @return int Date and time of the next execution as a timestamp
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function getNextDueExecution()
    {
        // NOTE: this call may throw an exception, but we let it bubble up
        return $this->execution->getNextExecution();
    }

    /**
     * Returns TRUE if several runs of the task are allowed concurrently
     *
     * @return bool TRUE if concurrent executions are allowed, FALSE otherwise
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function areMultipleExecutionsAllowed()
    {
        return $this->execution->getMultiple();
    }

    /**
     * Returns TRUE if an instance of the task is already running
     *
     * @return bool TRUE if an instance is already running, FALSE otherwise
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function isExecutionRunning()
    {
        trigger_error('AbstractTask->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        return GeneralUtility::makeInstance(SchedulerTaskRepository::class)->isTaskMarkedAsRunning($this);
    }

    /**
     * This method adds current execution to the execution list
     * It also logs the execution time and mode
     *
     * @return int Execution id
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function markExecution()
    {
        trigger_error('AbstractTask->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        return GeneralUtility::makeInstance(SchedulerTaskRepository::class)->addExecutionToTask($this);
    }

    /**
     * Removes given execution from list
     *
     * @param int $executionID Id of the execution to remove.
     * @param \Throwable|null $e An exception to signal a failed execution
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function unmarkExecution($executionID, \Throwable $e = null)
    {
        trigger_error('AbstractTask->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        if ($e != null) {
            // Do not serialize the complete exception or the trace, this can lead to huge strings > 50MB
            $failureString = serialize([
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'traceString' => $e->getTraceAsString(),
            ]);
        } else {
            $failureString = '';
        }
        GeneralUtility::makeInstance(SchedulerTaskRepository::class)->removeExecutionOfTask($this, $executionID, $failureString);
    }

    /**
     * Clears all marked executions
     *
     * @return bool TRUE if the clearing succeeded, FALSE otherwise
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function unmarkAllExecutions()
    {
        trigger_error('AbstractTask->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        return GeneralUtility::makeInstance(SchedulerTaskRepository::class)->removeAllRegisteredExecutionsForTask($this);
    }

    /**
     * Saves the details of the task to the database.
     *
     * @return bool
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function save()
    {
        return GeneralUtility::makeInstance(SchedulerTaskRepository::class)->update($this);
    }

    /**
     * Stops the task, by replacing the execution object by an empty one
     * NOTE: the task still needs to be saved after that
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function stop()
    {
        $this->execution = GeneralUtility::makeInstance(Execution::class);
    }

    /**
     * Removes the task totally from the system.
     * @deprecated will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.
     */
    public function remove()
    {
        trigger_error('AbstractTask->' . __METHOD__ . ' will be removed in TYPO3 v13.0. Use SchedulerTaskRepository instead.', E_USER_DEPRECATED);
        GeneralUtility::makeInstance(SchedulerTaskRepository::class)->remove($this);
    }

    /**
     * Guess task type from the existing information
     * If an interval or a cron command is defined, it's a recurring task
     *
     * @return int
     */
    public function getType()
    {
        if (!empty($this->getExecution()->getInterval()) || !empty($this->getExecution()->getCronCmd())) {
            return self::TYPE_RECURRING;
        }
        return self::TYPE_SINGLE;
    }

    protected function logException(\Exception $e)
    {
        $this->logger->error('A Task Exception was captured.', ['exception' => $e]);
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
