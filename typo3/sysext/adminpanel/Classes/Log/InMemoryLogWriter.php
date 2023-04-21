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

namespace TYPO3\CMS\Adminpanel\Log;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Adminpanel\Utility\MemoryUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Log writer that writes the log records into a static public class variable
 * for InMemory processing. Note this implements SingletonInterface so multiple
 * calls to this class can accumulate log records in the same instance.
 *
 * @internal
 */
final class InMemoryLogWriter extends AbstractWriter implements SingletonInterface
{
    /** @var LogRecord[] */
    private array $log = [];
    private bool $memoryLock = false;

    /**
     * Writes the log record
     */
    public function writeLog(LogRecord $record): self
    {
        // Do not log if CLI, if not frontend, or memory limit has been reached.
        if (Environment::isCli()
            || !(($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface)
            || !ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
            || $this->memoryLock === true
        ) {
            return $this;
        }

        // Guard: Memory Usage
        if (MemoryUtility::isMemoryConsumptionTooHigh()) {
            $this->lockWriter();
            return $this;
        }

        $this->log[] = (clone $record)->setMessage($this->interpolate($record->getMessage(), $record->getData()));

        return $this;
    }

    /**
     * @return LogRecord[]
     */
    public function getLogEntries(): array
    {
        return $this->log;
    }

    /**
     * Lock writer and add an info message that there may potentially be more entries.
     */
    private function lockWriter(): void
    {
        $this->memoryLock = true;
        $record = GeneralUtility::makeInstance(
            LogRecord::class,
            'TYPO3.CMS.AdminPanel.Log.InMemoryLogWriter',
            LogLevel::INFO,
            '... Further log entries omitted, memory usage too high.'
        );
        $this->log[] = $record;
    }
}
