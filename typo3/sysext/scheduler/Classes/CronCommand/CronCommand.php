<?php
namespace TYPO3\CMS\Scheduler\CronCommand;

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

/**
 * This class provides calculations for the cron command format.
 */
class CronCommand
{
    /**
     * Normalized sections of the cron command.
     * Allowed are comma separated lists of integers and the character '*'
     *
     * field          lower and upper bound
     * -----          --------------
     * minute         0-59
     * hour           0-23
     * day of month   1-31
     * month          1-12
     * day of week    1-7
     *
     * @var array $cronCommandSections
     */
    protected $cronCommandSections;

    /**
     * Timestamp of next execution date.
     * This value starts with 'now + 1 minute' if not set externally
     * by unit tests. After a call to calculateNextValue() it holds the timestamp of
     * the next execution date which matches the cron command restrictions.
     */
    protected $timestamp;

    /**
     * Constructor
     *
     * @param string $cronCommand The cron command can hold any combination documented as valid
     * @param bool|int $timestamp Optional start time, used in unit tests
     */
    public function __construct($cronCommand, $timestamp = false)
    {
        $cronCommand = NormalizeCommand::normalize($cronCommand);
        // Explode cron command to sections
        $this->cronCommandSections = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $cronCommand);
        // Initialize the values with the starting time
        // This takes care that the calculated time is always in the future
        if ($timestamp === false) {
            $timestamp = strtotime('+1 minute');
        } else {
            $timestamp += 60;
        }
        $this->timestamp = $this->roundTimestamp($timestamp);
    }

    /**
     * Calculates the date of the next execution.
     *
     * @throws \RuntimeException
     */
    public function calculateNextValue()
    {
        $newTimestamp = $this->getTimestamp();
        // Calculate next minute and hour field
        $loopCount = 0;
        while (true) {
            $loopCount++;
            // If there was no match within two days, cron command is invalid.
            // The second day is needed to catch the summertime leap in some countries.
            if ($loopCount > 2880) {
                throw new \RuntimeException('Unable to determine next execution timestamp: Hour and minute combination is invalid.', 1291494126);
            }
            if ($this->minuteAndHourMatchesCronCommand($newTimestamp)) {
                break;
            }
            $newTimestamp += 60;
        }
        $loopCount = 0;
        while (true) {
            $loopCount++;
            // A date must match within the next 4 years, this high number makes
            // sure leap year cron command configuration are caught.
            // If the loop runs longer than that, the cron command is invalid.
            if ($loopCount > 1464) {
                throw new \RuntimeException('Unable to determine next execution timestamp: Day of month, month and day of week combination is invalid.', 1291501280);
            }
            if ($this->dayMatchesCronCommand($newTimestamp)) {
                break;
            }
            $newTimestamp += $this->numberOfSecondsInDay($newTimestamp);
        }
        $this->timestamp = $newTimestamp;
    }

    /**
     * Get next timestamp
     *
     * @return int Unix timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get cron command sections. Array of strings, each containing either
     * a list of comma separated integers or *
     *
     * @return array command sections:
     * @internal
     */
    public function getCronCommandSections()
    {
        return $this->cronCommandSections;
    }

    /**
     * Determine if current timestamp matches minute and hour cron command restriction.
     *
     * @param int $timestamp to test
     * @return bool TRUE if cron command conditions are met
     */
    protected function minuteAndHourMatchesCronCommand($timestamp)
    {
        $minute = (int)date('i', $timestamp);
        $hour = (int)date('G', $timestamp);
        $commandMatch = false;
        if ($this->isInCommandList($this->cronCommandSections[0], $minute) && $this->isInCommandList($this->cronCommandSections[1], $hour)) {
            $commandMatch = true;
        }
        return $commandMatch;
    }

    /**
     * Determine if current timestamp matches day of month, month and day of week
     * cron command restriction
     *
     * @param int $timestamp to test
     * @return bool TRUE if cron command conditions are met
     */
    protected function dayMatchesCronCommand($timestamp)
    {
        $dayOfMonth = date('j', $timestamp);
        $month = date('n', $timestamp);
        $dayOfWeek = date('N', $timestamp);
        $isInDayOfMonth = $this->isInCommandList($this->cronCommandSections[2], $dayOfMonth);
        $isInMonth = $this->isInCommandList($this->cronCommandSections[3], $month);
        $isInDayOfWeek = $this->isInCommandList($this->cronCommandSections[4], $dayOfWeek);
        // Quote from vixiecron:
        // Note: The day of a command's execution can be specified by two fields — day of month, and day of week.
        // If both fields are restricted (i.e., aren't  *),  the  command will be run when either field
        // matches the current time.  For example, `30 4 1,15 * 5' would cause
        // a command to be run at 4:30 am on the 1st and 15th of each month, plus every Friday.
        $isDayOfMonthRestricted = (string)$this->cronCommandSections[2] !== '*';
        $isDayOfWeekRestricted = (string)$this->cronCommandSections[4] !== '*';
        $commandMatch = false;
        if ($isInMonth) {
            if ($isInDayOfMonth && $isDayOfMonthRestricted || $isInDayOfWeek && $isDayOfWeekRestricted || $isInDayOfMonth && !$isDayOfMonthRestricted && $isInDayOfWeek && !$isDayOfWeekRestricted) {
                $commandMatch = true;
            }
        }
        return $commandMatch;
    }

    /**
     * Determine if a given number validates a cron command section. The given cron
     * command must be a 'normalized' list with only comma separated integers or '*'
     *
     * @param string $commandExpression: cron command
     * @param int $numberToMatch: number to look up
     * @return bool TRUE if number is in list
     */
    protected function isInCommandList($commandExpression, $numberToMatch)
    {
        if ((string)$commandExpression === '*') {
            $inList = true;
        } else {
            $inList = \TYPO3\CMS\Core\Utility\GeneralUtility::inList($commandExpression, $numberToMatch);
        }
        return $inList;
    }

    /**
     * Helper method to calculate number of seconds in a day.
     *
     * This is not always 86400 (60*60*24) and depends on the timezone:
     * Some countries like Germany have a summertime / wintertime switch,
     * on every last sunday in march clocks are forwarded by one hour (set from 2:00 to 3:00),
     * and on last sunday of october they are set back one hour (from 3:00 to 2:00).
     * This shortens and lengthens the length of a day by one hour.
     *
     * @param int $timestamp Unix timestamp
     * @return int Number of seconds of day
     */
    protected function numberOfSecondsInDay($timestamp)
    {
        $now = mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp));
        // Make sure to be in next day, even if day has 25 hours
        $nextDay = $now + 60 * 60 * 25;
        $nextDay = mktime(0, 0, 0, date('n', $nextDay), date('j', $nextDay), date('Y', $nextDay));
        return $nextDay - $now;
    }

    /**
     * Round a timestamp down to full minute.
     *
     * @param int $timestamp Unix timestamp
     * @return int Rounded timestamp
     */
    protected function roundTimestamp($timestamp)
    {
        return mktime(date('H', $timestamp), date('i', $timestamp), 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp));
    }
}
