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
use TYPO3\CMS\Scheduler\Execution;

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

    public function __construct()
    {
        $this->execution = new Execution();
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
     * Unused since TYPO3 v14.0, can be deprecated and removed once we migrate task registration away from TYPO3_CONF_VARS.
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
     * Sets the internal execution object
     *
     * @param Execution $execution The execution to add
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function setExecution(Execution $execution): void
    {
        $this->execution = $execution;
    }

    /**
     * Returns the execution object
     *
     * @return Execution|object|null The internal execution object - when an invalid task is being unserialized, the Execution object might not be available
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
     * Stops the task, by replacing the execution object by an empty one
     * NOTE: the task still needs to be saved after that
     * @internal since TYPO3 v12.3, not part of TYPO3 Public API anymore.
     */
    public function stop()
    {
        $this->execution = new Execution();
    }

    /**
     * Guess recurring type from the existing information
     * If an interval or a cron command is defined, it's a recurring task
     */
    public function getType(): int
    {
        if ($this->execution->isRecurring()) {
            return self::TYPE_RECURRING;
        }
        return self::TYPE_SINGLE;
    }

    protected function logException(\Exception $e)
    {
        $this->logger?->error('A Task Exception was captured.', ['exception' => $e]);
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }

    public function getTaskType(): string
    {
        return static::class;
    }

    /**
     * It is recommended to implement this method in the respective task class.
     */
    public function getTaskParameters(): array
    {
        $vars = get_object_vars($this);
        $parameters = [];
        foreach ($vars as $key => $value) {
            $key = trim($key);
            $key = trim($key, "*\0");
            $key = trim($key);
            $parameters[$key] = $value;
        }
        unset(
            // Needs to be kept until TYPO3 v16.0 until the upgrade wizard was run through
            $parameters['scheduler'],
            $parameters['logger'],
            $parameters['taskUid'],
            $parameters['disabled'],
            $parameters['runOnNextCronJob'],
            $parameters['execution'],
            $parameters['executionTime'],
            $parameters['description'],
            $parameters['taskGroup'],
        );
        return $parameters;
    }

    public function setTaskParameters(array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            // Ensure a member property exists; Task objects might have old configuration data changed with
            // attributes that were removed meanwhile. This would otherwise trigger a PHP notice like
            // "PHP Runtime Deprecation Notice: Creation of dynamic property TYPO3\CMS\Linkvalidator\Task\ValidatorTask::$fileConfiguration is deprecated"
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
