<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Log\Writer;

use TYPO3\CMS\Core\Locking\Exception\LockAcquireException;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\Enum\Interval;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Write logs into files while providing basic rotation capabilities. This is a very basic approach, suitable for
 * environments where established tools like logrotate are not available.
 */
class RotatingFileWriter extends FileWriter
{
    private const ROTATION_DATE_FORMAT = 'YmdHis';

    private Interval $interval = Interval::DAILY;
    private int $maxFiles = 5;
    private \DateTimeImmutable $lastRotation;
    private \DateTimeImmutable $nextRotation;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    public function setLogFile(string $relativeLogFile): self
    {
        parent::setLogFile($relativeLogFile);

        $this->updateRuntimeRotationState($this->getLastRotation());

        return $this;
    }

    /**
     * Internal setter called by FileWriter constructor
     */
    protected function setInterval(string|Interval $interval): void
    {
        if (is_string($interval)) {
            // String support is required for use in system/settings.php
            $this->interval = Interval::tryFrom($interval) ?? Interval::DAILY;
        } else {
            $this->interval = $interval;
        }
    }

    /**
     * Internal setter called by FileWriter constructor
     */
    protected function setMaxFiles(int $maxFiles): void
    {
        $this->maxFiles = max(0, $maxFiles);
    }

    public function writeLog(LogRecord $record)
    {
        if ($this->needsRotation()) {
            $lockFactory = GeneralUtility::makeInstance(LockFactory::class);
            try {
                $lock = $lockFactory->createLocker('rotate-' . $this->logFile);
                if ($lock->acquire()) {
                    $this->updateRuntimeRotationState($this->getLastRotation());
                    // check again if rotation is still needed (could have happened in the meantime)
                    if ($this->needsRotation()) {
                        $this->rotate();
                    }
                }
                $lock->release();
            } catch (LockCreateException|LockAcquireException|LockAcquireWouldBlockException) {
            }
        }
        return parent::writeLog($record);
    }

    /**
     * This method rotates all log files found by using `glob()` to take all already rotated logs into account, even
     * after a configuration change.
     *
     * Log files are rotated using the "copytruncate" approach: the current open log file is copied as-is to a new
     * location, the current log file gets flushed afterward. This way, file handles don't need to get re-created.
     */
    protected function rotate(): void
    {
        $rotationSuffix = date(self::ROTATION_DATE_FORMAT);

        // copytruncate: Rotate the currently used log file
        copy($this->logFile, $this->logFile . '.' . $rotationSuffix);
        ftruncate(self::$logFileHandles[$this->logFile], 0);

        $rotatedLogFiles = glob($this->logFile . '.*');
        rsort($rotatedLogFiles, SORT_NATURAL);

        // Remove any excess files
        $excessFiles = array_slice($rotatedLogFiles, $this->maxFiles);
        foreach ($excessFiles as $excessFile) {
            unlink($excessFile);
        }

        $this->updateRuntimeRotationState(new \DateTimeImmutable());
    }

    protected function getLastRotation(): \DateTimeImmutable
    {
        // Rotate already rotated files again
        $rotatedLogFiles = glob($this->logFile . '.*');

        if ($rotatedLogFiles !== []) {
            // Sort rotated files to handle the newest one first
            rsort($rotatedLogFiles, SORT_NATURAL);
            $newestLog = current($rotatedLogFiles);

            $rotationDelimiterPosition = strrpos($newestLog, '.');
            $timestamp = substr($newestLog, $rotationDelimiterPosition + 1);

            $latestRotationDateTime = \DateTimeImmutable::createFromFormat(self::ROTATION_DATE_FORMAT, $timestamp);
            if ($latestRotationDateTime instanceof \DateTimeImmutable) {
                return $latestRotationDateTime;
            }
        }

        return new \DateTimeImmutable('@0');
    }

    protected function determineNextRotation(): \DateTimeImmutable
    {
        return $this->lastRotation->add(new \DateInterval($this->interval->getDateInterval()));
    }

    /**
     * Check if log files need to be rotated under following conditions:
     *
     * 1.
     *    a) either the next rotation is due
     *    b) logs were never rotated before
     * 2. the log file is not empty - FileWriter::setLogFile() creates one if missing
     */
    protected function needsRotation(): bool
    {
        return ($this->nextRotation <= new \DateTimeImmutable() || $this->lastRotation->getTimestamp() === 0) && filesize($this->logFile) > 0;
    }

    protected function updateRuntimeRotationState(\DateTimeImmutable $lastRotation): void
    {
        $this->lastRotation = $lastRotation;
        $this->nextRotation = $this->determineNextRotation();
    }
}
