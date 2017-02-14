<?php
namespace TYPO3\CMS\Core\TimeTracker;

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
 * A fake time tracker that does nothing but providing the methods of the real time tracker.
 * This is done to save some performance over the real time tracker.
 * @deprecated since TYPO3 v8, will be removed in v9
 */
class NullTimeTracker
{
    /**
     * "Constructor"
     * Sets the starting time
     *
     * does nothing
     *
     * @deprecated since TYPO3 v8, will be removed in v9, use the regular time tracking
     */
    public function start()
    {
        GeneralUtility::logDeprecatedFunction();
    }

    /**
     * Pushes an element to the TypoScript tracking array
     *
     * does nothing
     *
     * @param string $tslabel Label string for the entry, eg. TypoScript property name
     * @param string $value Additional value(?)
     */
    public function push($tslabel, $value = '')
    {
    }

    /**
     * Pulls an element from the TypoScript tracking array
     *
     * does nothing
     *
     * @param string $content The content string generated within the push/pull part.
     */
    public function pull($content = '')
    {
    }

    /**
     * Set TSselectQuery - for messages in TypoScript debugger.
     *
     * does nothing
     *
     * @param array $data Query array
     * @param string $msg Message/Label to attach
     */
    public function setTSselectQuery(array $data, $msg = '')
    {
    }

    /**
     * Logs the TypoScript entry
     *
     * does nothing
     *
     * @param string $content The message string
     * @param int $num Message type: 0: information, 1: message, 2: warning, 3: error
     */
    public function setTSlogMessage($content, $num = 0)
    {
    }

    /**
     * Print TypoScript parsing log
     *
     * does nothing
     *
     * @return string HTML table with the information about parsing times.
     */
    public function printTSlog()
    {
    }

    /**
     * Increases the stack pointer
     *
     * does nothing
     */
    public function incStackPointer()
    {
    }

    /**
     * Decreases the stack pointer
     *
     * does nothing
     */
    public function decStackPointer()
    {
    }

    /**
     * Gets a microtime value as milliseconds value.
     *
     * @param float $microtime The microtime value - if not set the current time is used
     * @return int The microtime value as milliseconds value
     */
    public function getMilliseconds($microtime = null)
    {
    }
}
