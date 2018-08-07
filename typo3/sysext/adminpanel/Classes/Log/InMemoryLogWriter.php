<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Log;

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

use TYPO3\CMS\Adminpanel\Utility\MemoryUtility;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Log writer that writes the log records into a static public class variable
 * for InMemory processing
 */
class InMemoryLogWriter extends AbstractWriter
{
    public static $log = [];

    private static $memoryLock = false;

    /**
     * Writes the log record
     *
     * @param LogRecord $record Log record
     * @return self
     * @throws \RuntimeException
     */
    public function writeLog(LogRecord $record): self
    {
        // Guard: Locked Writer
        if (self::$memoryLock === true) {
            return $this;
        }

        // Guard: Memory Usage
        if (!self::$memoryLock && MemoryUtility::isMemoryConsumptionTooHigh()) {
            $this->lockWriter();
            return $this;
        }

        self::$log[] = $record;

        return $this;
    }

    /**
     * Lock writer and add a info message that there may potentially be more entries.
     */
    protected function lockWriter(): void
    {
        self::$memoryLock = true;
        /** @var LogRecord $record */
        $record = GeneralUtility::makeInstance(
            LogRecord::class,
            'TYPO3.CMS.AdminPanel.Log.InMemoryLogWriter',
            LogLevel::INFO,
            '... Further log entries omitted, memory usage too high.'
        );
        self::$log[] = $record;
    }
}
