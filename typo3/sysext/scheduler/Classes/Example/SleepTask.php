<?php
namespace TYPO3\CMS\Scheduler\Example;

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
 * Provides a task that sleeps for some time
 * This is useful for testing parallel executions
 * @internal This class is an example is not considered part of the Public TYPO3 API.
 */
class SleepTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * Number of seconds the task should be sleeping for
     *
     * @var int
     */
    public $sleepTime = 10;

    /**
     * Function executed from the Scheduler.
     * Goes to sleep ;-)
     *
     * @return bool
     */
    public function execute()
    {
        $time = 10;
        if (!empty($this->sleepTime)) {
            $time = $this->sleepTime;
        }
        sleep($time);
        return true;
    }

    /**
     * This method returns the sleep duration as additional information
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        return $GLOBALS['LANG']->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.sleepTime') . ': ' . $this->sleepTime;
    }
}
