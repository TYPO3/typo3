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

namespace TYPO3\CMS\Core\Log;

use Psr\Log\AbstractLogger;
use TYPO3\CMS\Core\Log\Processor\ProcessorInterface;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Logger to log events and data for different components.
 */
class Logger extends AbstractLogger
{
    /**
     * Logger name or component for which this logger is meant to be used for.
     *
     * This should be a dot-separated name and should normally be based on
     * the class name or the name of a subsystem, such as
     * core.t3lib.cache.manager, core.backend.workspaces or extension.news
     */
    protected string $name = '';

    /**
     * Unique ID of the request
     */
    protected string $requestId = '';

    /**
     * Minimum log level, anything below this level will be ignored.
     */
    protected int $minimumLogLevel;

    /**
     * Writers used by this logger
     */
    protected array $writers = [];

    /**
     * Processors used by this logger
     */
    protected array $processors = [];

    /**
     * Constructor.
     *
     * @param string $name A name for the logger.
     * @param string $requestId Unique ID of the request
     */
    public function __construct(string $name, string $requestId = '')
    {
        $this->name = $name;
        $this->requestId = $requestId;
        $this->minimumLogLevel = LogLevel::normalizeLevel(LogLevel::EMERGENCY);
    }

    /**
     * Re-initialize instance with creating a new instance with up to date information
     */
    public function __wakeup()
    {
        $newLogger = GeneralUtility::makeInstance(LogManager::class)->getLogger($this->name);
        $this->requestId = $newLogger->requestId;
        $this->minimumLogLevel = $newLogger->minimumLogLevel;
        $this->writers = $newLogger->writers;
        $this->processors = $newLogger->processors;
    }

    /**
     * Remove everything except the name, to be able to restore it on wakeup
     *
     * @return array
     */
    public function __sleep(): array
    {
        return ['name'];
    }

    /**
     * Sets the minimum log level for which log records are written.
     *
     * @param int $level Minimum log level
     * @return \TYPO3\CMS\Core\Log\Logger $this
     */
    protected function setMinimumLogLevel($level)
    {
        LogLevel::validateLevel($level);
        $this->minimumLogLevel = $level;
        return $this;
    }

    /**
     * Gets the minimum log level for which log records are written.
     *
     * @return int Minimum log level
     */
    protected function getMinimumLogLevel()
    {
        return $this->minimumLogLevel;
    }

    /**
     * Gets the logger's name.
     *
     * @return string Logger name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds a writer to this logger
     *
     * @param string $minimumLevel
     * @param \TYPO3\CMS\Core\Log\Writer\WriterInterface $writer Writer object
     * @return \TYPO3\CMS\Core\Log\Logger $this
     */
    public function addWriter(string $minimumLevel, WriterInterface $writer)
    {
        $minLevelAsNumber = LogLevel::normalizeLevel($minimumLevel);
        // Cycle through all the log levels which are as severe as or higher
        // than $minimumLevel and add $writer to each severity level
        foreach (LogLevel::atLeast($minLevelAsNumber) as $levelName) {
            $this->writers[$levelName] ??= [];
            $this->writers[$levelName][] = $writer;
        }
        if ($minLevelAsNumber > $this->getMinimumLogLevel()) {
            $this->setMinimumLogLevel($minLevelAsNumber);
        }
        return $this;
    }

    /**
     * Returns all configured writers indexed by log level
     *
     * @return array
     */
    public function getWriters()
    {
        return $this->writers;
    }

    /**
     * Adds a processor to the logger.
     *
     * @param string $minimumLevel
     * @param \TYPO3\CMS\Core\Log\Processor\ProcessorInterface $processor The processor to add.
     */
    public function addProcessor(string $minimumLevel, ProcessorInterface $processor)
    {
        $minLevelAsNumber = LogLevel::normalizeLevel($minimumLevel);
        LogLevel::validateLevel($minLevelAsNumber);
        // Cycle through all the log levels which are as severe as or higher
        // than $minimumLevel and add $processor to each severity level
        for ($logLevelWhichTriggersProcessor = LogLevel::normalizeLevel(LogLevel::EMERGENCY); $logLevelWhichTriggersProcessor <= $minLevelAsNumber; $logLevelWhichTriggersProcessor++) {
            $logLevelName = LogLevel::getInternalName($logLevelWhichTriggersProcessor);
            $this->processors[$logLevelName] ??= [];
            $this->processors[$logLevelName][] = $processor;
        }
        if ($minLevelAsNumber > $this->getMinimumLogLevel()) {
            $this->setMinimumLogLevel($minLevelAsNumber);
        }
    }

    /**
     * Returns all added processors indexed by log level
     *
     * @return array
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * Adds a log record.
     *
     * @param int|string $level Log level. Value according to \TYPO3\CMS\Core\Log\LogLevel. Alternatively accepts a string.
     * @param string $message Log message.
     * @param array $data Additional data to log
     * @return mixed
     */
    public function log($level, $message, array $data = [])
    {
        $level = LogLevel::normalizeLevel($level);
        LogLevel::validateLevel($level);
        if ($level > $this->minimumLogLevel) {
            return $this;
        }
        $record = GeneralUtility::makeInstance(LogRecord::class, $this->name, LogLevel::getInternalName($level), $message, $data, $this->requestId);
        $record = $this->callProcessors($record);
        $this->writeLog($record);
        return $this;
    }

    /**
     * Calls all processors and returns log record
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $record Record to process
     * @throws \RuntimeException
     * @return \TYPO3\CMS\Core\Log\LogRecord Processed log record
     */
    protected function callProcessors(LogRecord $record)
    {
        /** @var ProcessorInterface $processor */
        foreach ($this->processors[$record->getLevel()] ?? [] as $processor) {
            $processedRecord = $processor->processLogRecord($record);
            if (!$processedRecord instanceof LogRecord) {
                throw new \RuntimeException('Processor ' . get_class($processor) . ' returned invalid data. Instance of TYPO3\\CMS\\Core\\Log\\LogRecord expected', 1343593398);
            }
            $record = $processedRecord;
        }
        return $record;
    }

    /**
     * Passes the \TYPO3\CMS\Core\Log\LogRecord to all registered writers.
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $record
     */
    protected function writeLog(LogRecord $record)
    {
        /** @var WriterInterface $writer */
        foreach ($this->writers[$record->getLevel()] ?? [] as $writer) {
            $writer->writeLog($record);
        }
    }
}
