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

/**
 * A log processor that does nothing. Used in unit tests.
 */
class NullProcessor extends AbstractProcessor
{
    /**
     * Processes a log record and returns the same.
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $logRecord The log record to process
     * @return \TYPO3\CMS\Core\Log\LogRecord The processed log record with additional data
     */
    public function processLogRecord(LogRecord $logRecord)
    {
        return $logRecord;
    }
}
