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
 * Memory peak usage processor methods.
 */
class MemoryPeakUsageProcessor extends AbstractMemoryProcessor
{
    /**
     * Processes a log record and adds memory peak usage information.
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $logRecord The log record to process
     * @return \TYPO3\CMS\Core\Log\LogRecord The processed log record with additional data
     * @see memory_get_peak_usage()
     */
    public function processLogRecord(LogRecord $logRecord)
    {
        $bytes = memory_get_peak_usage($this->getRealMemoryUsage());
        if ($this->formatSize) {
            $size = GeneralUtility::formatSize($bytes);
        } else {
            $size = $bytes;
        }
        $logRecord->addData([
            'memoryPeakUsage' => $size,
        ]);
        return $logRecord;
    }
}
