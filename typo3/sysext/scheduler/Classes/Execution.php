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

namespace TYPO3\CMS\Scheduler;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\CronCommand\CronCommand;

/**
 * This class manages the logic of a particular execution of a task
 * @internal
 */
class Execution
{
    /**
     * Start date of a task (timestamp)
     *
     * @var int
     */
    protected $start;

    /**
     * End date of a task (timestamp)
     *
     * @var int
     */
    protected $end;

    /**
     * Interval between executions (in seconds)
     *
     * @var int
     */
    protected $interval;

    /**
     * Flag for concurrent executions: TRUE if allowed, FALSE otherwise (default)
     *
     * @var bool
     */
    protected $multiple = false;

    /**
     * The cron command string of this task,
     *
     * @var string
     */
    protected $cronCmd;

    /**
     * This flag is used to mark a new single execution
     * See explanations in method setIsNewSingleExecution()
     *
     * @var bool
     * @see \TYPO3\CMS\Scheduler\Execution::setIsNewSingleExecution()
     */
    protected $isNewSingleExecution = false;

    public static function createFromDetails(array $details): self
    {
        $obj = new self();
        $obj->setStart((int)$details['start']);
        $obj->setEnd((int)$details['end']);
        $obj->setInterval((int)$details['interval']);
        $obj->setMultiple((bool)$details['multiple']);
        $obj->setCronCmd((string)$details['cronCmd']);
        $obj->setIsNewSingleExecution((bool)$details['isNewSingleExecution']);
        return $obj;
    }

    /**
     * Registers a single execution of the task
     *
     * @param int $timestamp Timestamp of the next execution
     */
    public static function createSingleExecution(int $timestamp): self
    {
        $obj = new self();
        $obj->setStart($timestamp);
        $obj->setInterval(0);
        $obj->setEnd($timestamp);
        $obj->setCronCmd('');
        $obj->setMultiple(false);
        $obj->setIsNewSingleExecution(true);
        return $obj;
    }

    /**
     * Registers a recurring execution of the task
     *
     * @param int $start The first date/time when this execution should occur (timestamp)
     * @param int $interval Execution interval in seconds
     * @param int $end The last date/time when this execution should occur (timestamp)
     * @param bool $multiple Set to FALSE if multiple executions of this task are not permitted in parallel
     * @param string $cronCmd Used like in crontab (minute hour day month weekday)
     */
    public static function createRecurringExecution(int $start, int $interval, int $end = 0, bool $multiple = false, string $cronCmd = ''): self
    {
        $obj = new self();
        // Set general values
        $obj->setStart($start);
        $obj->setEnd($end);
        $obj->setMultiple($multiple);
        if (empty($cronCmd)) {
            // Use interval
            $obj->setInterval($interval);
            $obj->setCronCmd('');
        } else {
            // Use cron syntax
            $obj->setInterval(0);
            $obj->setCronCmd($cronCmd);
        }
        return $obj;
    }

    /**********************************
     * Setters and getters
     **********************************/
    /**
     * This method is used to set the start date
     *
     * @param int $start Start date (timestamp)
     */
    public function setStart($start)
    {
        $this->start = (int)$start;
    }

    /**
     * This method is used to get the start date
     *
     * @return int Start date (timestamp)
     */
    public function getStart()
    {
        return (int)$this->start;
    }

    /**
     * This method is used to set the end date
     *
     * @param int $end End date (timestamp)
     */
    public function setEnd($end)
    {
        $this->end = (int)$end;
    }

    /**
     * This method is used to get the end date
     *
     * @return int End date (timestamp)
     */
    public function getEnd()
    {
        return (int)$this->end;
    }

    /**
     * This method is used to set the interval
     *
     * @param int $interval Interval (in seconds)
     */
    public function setInterval($interval)
    {
        $this->interval = (int)$interval;
    }

    /**
     * This method is used to get the interval
     *
     * @return int Interval (in seconds)
     */
    public function getInterval()
    {
        return (int)$this->interval;
    }

    /**
     * This method is used to set the multiple execution flag
     *
     * @param bool $multiple TRUE if concurrent executions are allowed, FALSE otherwise
     */
    public function setMultiple($multiple)
    {
        $this->multiple = (bool)$multiple;
    }

    /**
     * This method is used to get the multiple execution flag
     *
     * @return bool TRUE if concurrent executions are allowed, FALSE otherwise
     */
    public function getMultiple()
    {
        return (bool)$this->multiple;
    }

    /**
     * Set the value of the cron command
     *
     * @param string $cmd Cron command, using cron-like syntax
     */
    public function setCronCmd($cmd)
    {
        $this->cronCmd = $cmd;
    }

    /**
     * Get the value of the cron command
     *
     * @return string Cron command, using cron-like syntax
     */
    public function getCronCmd()
    {
        return $this->cronCmd;
    }

    /**
     * Set whether this is a newly created single execution.
     * This is necessary for the following reason: if a new single-running task
     * is created and its start date is in the past (even for only a few seconds),
     * the next run time calculation (which happens upon saving) will disable
     * that task, because it was meant to run only once and is in the past.
     * Setting this flag to TRUE preserves this task for a single run.
     * Upon next execution, this flag is set to FALSE.
     *
     * @param bool $isNewSingleExecution Is newly created single execution?
     * @see \TYPO3\CMS\Scheduler\Execution::getNextExecution()
     */
    public function setIsNewSingleExecution($isNewSingleExecution)
    {
        $this->isNewSingleExecution = (bool)$isNewSingleExecution;
    }

    /**
     * Get whether this is a newly created single execution
     *
     * @return bool Is newly created single execution?
     */
    public function getIsNewSingleExecution()
    {
        return (bool)$this->isNewSingleExecution;
    }

    /**********************************
     * Execution calculations and logic
     **********************************/
    /**
     * This method gets or calculates the next execution date
     *
     * @return int Timestamp of the next execution
     * @throws \OutOfBoundsException
     */
    public function getNextExecution()
    {
        if ($this->getIsNewSingleExecution()) {
            $this->setIsNewSingleExecution(false);
            return $this->getStart();
        }
        if (!$this->isEnded()) {
            // If the schedule has not yet run out, find out the next date
            if (!$this->isStarted()) {
                // If the schedule hasn't started yet, next date is start date
                $date = $this->getStart();
            } else {
                // If the schedule has already started, calculate next date
                if ($this->cronCmd) {
                    // If it uses cron-like syntax, calculate next date
                    $date = $this->getNextCronExecution();
                } elseif ($this->getInterval() == 0) {
                    // If not and there's no interval either, it's a singe execution: use start date
                    $date = $this->getStart();
                } else {
                    // Otherwise calculate date based on interval
                    $now = time();
                    $date = $now + $this->getInterval() - ($now - $this->getStart()) % $this->getInterval();
                }
                // If date is in the future, throw an exception
                if (!empty($this->getEnd()) && $date > $this->getEnd()) {
                    throw new \OutOfBoundsException('Next execution date is past end date.', 1250715528);
                }
            }
        } else {
            // The event has ended, throw an exception
            throw new \OutOfBoundsException('Task is past end date.', 1250715544);
        }
        return $date;
    }

    /**
     * Calculates the next execution from a cron command
     *
     * @return int Next execution (timestamp)
     */
    public function getNextCronExecution()
    {
        $cronCmd = GeneralUtility::makeInstance(CronCommand::class, $this->getCronCmd());
        $cronCmd->calculateNextValue();
        return (int)$cronCmd->getTimestamp();
    }

    /**
     * Checks if the schedule for a task is started or not
     *
     * @return bool TRUE if the schedule is already active, FALSE otherwise
     */
    public function isStarted()
    {
        return $this->getStart() < time();
    }

    /**
     * Checks if the schedule for a task is passed or not
     *
     * @return bool TRUE if the schedule is not active anymore, FALSE otherwise
     */
    public function isEnded()
    {
        if ($this->getEnd() === 0) {
            // If no end is defined, the schedule never ends
            $result = false;
        } else {
            // Otherwise check if end is in the past
            $result = $this->getEnd() < time();
        }
        return $result;
    }

    public function toArray(): array
    {
        // The type cast is necessary as long as the DB migration (upgrade wizard) exists,
        // Because this way, the serialization (from unserialize()) kicks in
        // and cleans the values right away.
        // @todo We can then strong-type-hint in TYPO3 v16.0.
        return [
            'start' => (int)$this->start,
            'end' => (int)$this->end,
            'interval' => (int)$this->interval,
            'multiple' => (bool)$this->multiple,
            'cronCmd' => (string)$this->cronCmd,
            'isNewSingleExecution' => (bool)$this->isNewSingleExecution,
        ];
    }
}
