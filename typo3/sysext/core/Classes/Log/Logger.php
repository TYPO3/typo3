<?php
namespace TYPO3\CMS\Core\Log;

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
 * Logger to log events and data for different components.
 */
class Logger implements \Psr\Log\LoggerInterface
{
    /**
     * Logger name or component for which this logger is meant to be used for.
     * This should be a dot-separated name and should normally be based on
     * the class name or the name of a subsystem, such as
     * core.t3lib.cache.manager, core.backend.workspaces or extension.news
     *
     * @var string
     */
    protected $name = '';

    /**
     * Unique ID of the request
     *
     * @var string
     */
    protected $requestId = '';

    /**
     * Minimum log level, anything below this level will be ignored.
     *
     * @var int
     */
    protected $minimumLogLevel = LogLevel::EMERGENCY;

    /**
     * Writers used by this logger
     *
     * @var array
     */
    protected $writers = [];

    /**
     * Processors used by this logger
     *
     * @var array
     */
    protected $processors = [];

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
     * @param int $minimumLevel
     * @param \TYPO3\CMS\Core\Log\Writer\WriterInterface $writer Writer object
     * @return \TYPO3\CMS\Core\Log\Logger $this
     */
    public function addWriter($minimumLevel, Writer\WriterInterface $writer)
    {
        LogLevel::validateLevel($minimumLevel);
        // Cycle through all the log levels which are as severe as or higher
        // than $minimumLevel and add $writer to each severity level
        for ($logLevelWhichTriggersWriter = LogLevel::EMERGENCY; $logLevelWhichTriggersWriter <= $minimumLevel; $logLevelWhichTriggersWriter++) {
            if (!isset($this->writers[$logLevelWhichTriggersWriter])) {
                $this->writers[$logLevelWhichTriggersWriter] = [];
            }
            $this->writers[$logLevelWhichTriggersWriter][] = $writer;
        }
        if ($minimumLevel > $this->getMinimumLogLevel()) {
            $this->setMinimumLogLevel($minimumLevel);
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
     * @param int $minimumLevel
     * @param \TYPO3\CMS\Core\Log\Processor\ProcessorInterface $processor The processor to add.
     */
    public function addProcessor($minimumLevel, Processor\ProcessorInterface $processor)
    {
        LogLevel::validateLevel($minimumLevel);
        // Cycle through all the log levels which are as severe as or higher
        // than $minimumLevel and add $processor to each severity level
        for ($logLevelWhichTriggersProcessor = LogLevel::EMERGENCY; $logLevelWhichTriggersProcessor <= $minimumLevel; $logLevelWhichTriggersProcessor++) {
            if (!isset($this->processors[$logLevelWhichTriggersProcessor])) {
                $this->processors[$logLevelWhichTriggersProcessor] = [];
            }
            $this->processors[$logLevelWhichTriggersProcessor][] = $processor;
        }
        if ($minimumLevel > $this->getMinimumLogLevel()) {
            $this->setMinimumLogLevel($minimumLevel);
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
        /** @var \TYPO3\CMS\Core\Log\LogRecord $record */
        $record = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(LogRecord::class, $this->name, $level, $message, $data, $this->requestId);
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
        if (!empty($this->processors[$record->getLevel()])) {
            foreach ($this->processors[$record->getLevel()] as $processor) {
                $processedRecord = $processor->processLogRecord($record);
                if (!$processedRecord instanceof LogRecord) {
                    throw new \RuntimeException('Processor ' . get_class($processor) . ' returned invalid data. Instance of TYPO3\\CMS\\Core\\Log\\LogRecord expected', 1343593398);
                }
                $record = $processedRecord;
            }
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
        if (!empty($this->writers[$record->getLevel()])) {
            foreach ($this->writers[$record->getLevel()] as $writer) {
                $writer->writeLog($record);
            }
        }
    }

    /**
     * Shortcut to log an EMERGENCY record.
     *
     * @param string $message Log message.
     * @param array $data Additional data to log
     * @return \TYPO3\CMS\Core\Log\Logger $this
     */
    public function emergency($message, array $data = [])
    {
        return $this->log(LogLevel::EMERGENCY, $message, $data);
    }

    /**
     * Shortcut to log an ALERT record.
     *
     * @param string $message Log message.
     * @param array $data Additional data to log
     * @return \TYPO3\CMS\Core\Log\Logger $this
     */
    public function alert($message, array $data = [])
    {
        return $this->log(LogLevel::ALERT, $message, $data);
    }

    /**
     * Shortcut to log a CRITICAL record.
     *
     * @param string $message Log message.
     * @param array $data Additional data to log
     * @return \TYPO3\CMS\Core\Log\Logger $this
     */
    public function critical($message, array $data = [])
    {
        return $this->log(LogLevel::CRITICAL, $message, $data);
    }

    /**
     * Shortcut to log an ERROR record.
     *
     * @param string $message Log message.
     * @param array $data Additional data to log
     * @return \TYPO3\CMS\Core\Log\Logger $this
     */
    public function error($message, array $data = [])
    {
        return $this->log(LogLevel::ERROR, $message, $data);
    }

    /**
     * Shortcut to log a WARNING record.
     *
     * @param string $message Log message.
     * @param array $data Additional data to log
     * @return \TYPO3\CMS\Core\Log\Logger $this
     */
    public function warning($message, array $data = [])
    {
        return $this->log(LogLevel::WARNING, $message, $data);
    }

    /**
     * Shortcut to log a NOTICE record.
     *
     * @param string $message Log message.
     * @param array $data Additional data to log
     * @return \TYPO3\CMS\Core\Log\Logger $this
     */
    public function notice($message, array $data = [])
    {
        return $this->log(LogLevel::NOTICE, $message, $data);
    }

    /**
     * Shortcut to log an INFORMATION record.
     *
     * @param string $message Log message.
     * @param array $data Additional data to log
     * @return \TYPO3\CMS\Core\Log\Logger $this
     */
    public function info($message, array $data = [])
    {
        return $this->log(LogLevel::INFO, $message, $data);
    }

    /**
     * Shortcut to log a DEBUG record.
     *
     * @param string $message Log message.
     * @param array $data Additional data to log
     * @return \TYPO3\CMS\Core\Log\Logger $this
     */
    public function debug($message, array $data = [])
    {
        return $this->log(LogLevel::DEBUG, $message, $data);
    }
}
