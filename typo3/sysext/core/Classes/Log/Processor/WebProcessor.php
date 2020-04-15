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

namespace TYPO3\CMS\Core\Log\Processor;

use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Web log processor to automatically add web request related data to a log
 * record.
 */
class WebProcessor extends AbstractProcessor
{
    /**
     * Processes a log record and adds webserver environment data.
     * We use the usual "Debug System Information"
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $logRecord The log record to process
     * @return \TYPO3\CMS\Core\Log\LogRecord The processed log record with additional data
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv()
     */
    public function processLogRecord(LogRecord $logRecord)
    {
        $logRecord->addData(GeneralUtility::getIndpEnv('_ARRAY'));
        return $logRecord;
    }
}
