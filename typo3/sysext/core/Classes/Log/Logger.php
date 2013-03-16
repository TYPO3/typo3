<?php
namespace TYPO3\CMS\Core\Log;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Ingo Renner (ingo@typo3.org)
 * (c) 2011-2013 Steffen Müller (typo3@t3node.com)
 * (c) 2011-2013 Steffen Gebert (steffen.gebert@typo3.org)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Logger to log events and data for different components.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Steffen Müller <typo3@t3node.com>
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class Logger {

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
	 * Minimum log level, anything below this level will be ignored.
	 *
	 * @var integer
	 */
	protected $minimumLogLevel = \TYPO3\CMS\Core\Log\LogLevel::EMERGENCY;

	/**
	 * Writers used by this logger
	 *
	 * @var array
	 */
	protected $writers = array();

	/**
	 * Processors used by this logger
	 *
	 * @var array
	 */
	protected $processors = array();

	/**
	 * Constructor.
	 *
	 * @param string $name A name for the logger.
	 * @return \TYPO3\CMS\Core\Log\Logger
	 */
	public function __construct($name) {
		$this->name = $name;
	}

	/**
	 * Sets the minimum log level for which log records are written.
	 *
	 * @param integer $level Minimum log level
	 * @return \TYPO3\CMS\Core\Log\Logger $this
	 */
	protected function setMinimumLogLevel($level) {
		\TYPO3\CMS\Core\Log\LogLevel::validateLevel($level);
		$this->minimumLogLevel = $level;
		return $this;
	}

	/**
	 * Gets the minimum log level for which log records are written.
	 *
	 * @return integer Minimum log level
	 */
	protected function getMinimumLogLevel() {
		return $this->minimumLogLevel;
	}

	/**
	 * Gets the logger's name.
	 *
	 * @return string Logger name.
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Adds a writer to this logger
	 *
	 * @param integer $minimumLevel
	 * @param \TYPO3\CMS\Core\Log\Writer\WriterInterface $writer Writer object
	 * @return \TYPO3\CMS\Core\Log\Logger $this
	 */
	public function addWriter($minimumLevel, \TYPO3\CMS\Core\Log\Writer\WriterInterface $writer) {
		\TYPO3\CMS\Core\Log\LogLevel::validateLevel($minimumLevel);
		// Cycle through all the log levels which are as severe as or higher
		// than $minimumLevel and add $writer to each severity level
		for ($logLevelWhichTriggersWriter = \TYPO3\CMS\Core\Log\LogLevel::EMERGENCY; $logLevelWhichTriggersWriter <= $minimumLevel; $logLevelWhichTriggersWriter++) {
			if (!isset($this->writers[$logLevelWhichTriggersWriter])) {
				$this->writers[$logLevelWhichTriggersWriter] = array();
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
	public function getWriters() {
		return $this->writers;
	}

	/**
	 * Adds a processor to the logger.
	 *
	 * @param integer $minimumLevel
	 * @param \TYPO3\CMS\Core\Log\Processor\ProcessorInterface $processor The processor to add.
	 * @return void
	 */
	public function addProcessor($minimumLevel, \TYPO3\CMS\Core\Log\Processor\ProcessorInterface $processor) {
		\TYPO3\CMS\Core\Log\LogLevel::validateLevel($minimumLevel);
		// Cycle through all the log levels which are as severe as or higher
		// than $minimumLevel and add $processor to each severity level
		for ($logLevelWhichTriggersProcessor = \TYPO3\CMS\Core\Log\LogLevel::EMERGENCY; $logLevelWhichTriggersProcessor <= $minimumLevel; $logLevelWhichTriggersProcessor++) {
			if (!isset($this->processors[$logLevelWhichTriggersProcessor])) {
				$this->processors[$logLevelWhichTriggersProcessor] = array();
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
	public function getProcessors() {
		return $this->processors;
	}

	/**
	 * Adds a log record.
	 *
	 * @param integer $level Log level.
	 * @param string $message Log message.
	 * @param array $data Additional data to log
	 * @return mixed
	 */
	public function log($level, $message, array $data = array()) {
		\TYPO3\CMS\Core\Log\LogLevel::validateLevel($level);
		if ($level > $this->minimumLogLevel) {
			return $this;
		}
		/** @var $record \TYPO3\CMS\Core\Log\LogRecord */
		$record = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogRecord', $this->name, $level, $message, $data);
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
	protected function callProcessors(\TYPO3\CMS\Core\Log\LogRecord $record) {
		if (!empty($this->processors[$record->getLevel()])) {
			foreach ($this->processors[$record->getLevel()] as $processor) {
				$processedRecord = $processor->processLogRecord($record);
				if (!$processedRecord instanceof \TYPO3\CMS\Core\Log\LogRecord) {
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
	 * @return void
	 */
	protected function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record) {
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
	public function emergency($message, array $data = array()) {
		return $this->log(\TYPO3\CMS\Core\Log\LogLevel::EMERGENCY, $message, $data);
	}

	/**
	 * Shortcut to log an ALERT record.
	 *
	 * @param string $message Log message.
	 * @param array $data Additional data to log
	 * @return \TYPO3\CMS\Core\Log\Logger $this
	 */
	public function alert($message, array $data = array()) {
		return $this->log(\TYPO3\CMS\Core\Log\LogLevel::ALERT, $message, $data);
	}

	/**
	 * Shortcut to log a CRITICAL record.
	 *
	 * @param string $message Log message.
	 * @param array $data Additional data to log
	 * @return \TYPO3\CMS\Core\Log\Logger $this
	 */
	public function critical($message, array $data = array()) {
		return $this->log(\TYPO3\CMS\Core\Log\LogLevel::CRITICAL, $message, $data);
	}

	/**
	 * Shortcut to log an ERROR record.
	 *
	 * @param string $message Log message.
	 * @param array $data Additional data to log
	 * @return \TYPO3\CMS\Core\Log\Logger $this
	 */
	public function error($message, array $data = array()) {
		return $this->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $message, $data);
	}

	/**
	 * Shortcut to log a WARNING record.
	 *
	 * @param string $message Log message.
	 * @param array $data Additional data to log
	 * @return \TYPO3\CMS\Core\Log\Logger $this
	 */
	public function warning($message, array $data = array()) {
		return $this->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, $message, $data);
	}

	/**
	 * Shortcut to log a NOTICE record.
	 *
	 * @param string $message Log message.
	 * @param array $data Additional data to log
	 * @return \TYPO3\CMS\Core\Log\Logger $this
	 */
	public function notice($message, array $data = array()) {
		return $this->log(\TYPO3\CMS\Core\Log\LogLevel::NOTICE, $message, $data);
	}

	/**
	 * Shortcut to log an INFORMATION record.
	 *
	 * @param string $message Log message.
	 * @param array $data Additional data to log
	 * @return \TYPO3\CMS\Core\Log\Logger $this
	 */
	public function info($message, array $data = array()) {
		return $this->log(\TYPO3\CMS\Core\Log\LogLevel::INFO, $message, $data);
	}

	/**
	 * Shortcut to log a DEBUG record.
	 *
	 * @param string $message Log message.
	 * @param array $data Additional data to log
	 * @return \TYPO3\CMS\Core\Log\Logger $this
	 */
	public function debug($message, array $data = array()) {
		return $this->log(\TYPO3\CMS\Core\Log\LogLevel::DEBUG, $message, $data);
	}

}


?>