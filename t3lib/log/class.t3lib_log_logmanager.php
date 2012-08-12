<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2012 Ingo Renner (ingo@typo3.org)
 * (c) 2011-2012 Steffen Müller (typo3@t3node.com)
 * (c) 2011-2012 Steffen Gebert (steffen.gebert@typo3.org))
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
 * Global LogManager that keeps track of global logging information.
 *
 * Inspired by java.util.logging
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Steffen Müller <typo3@t3node.com>
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_log_LogManager implements t3lib_Singleton {

	/**
	 * @var string
	 */
	const CONFIGURATION_TYPE_WRITER = 'writer';

	/**
	 * @var string
	 */
	const CONFIGURATION_TYPE_PROCESSOR = 'processor';

	/**
	 * Loggers to retrieve them for repeated use.
	 *
	 * @var array
	 */
	protected $loggers = array();

	/**
	 * Default / global / root logger.
	 *
	 * @var t3lib_log_Logger
	 */
	protected $rootLogger = NULL;


	/**
	 * Constructor
	 */
	public function __construct() {
		$this->rootLogger  = t3lib_div::makeInstance('t3lib_log_Logger', '');
		$this->loggers[''] = $this->rootLogger;
	}

	/**
	 * For use in unit test context only. Resets the internal logger registry.
	 *
	 * @return void
	 */
	public function reset() {
		$this->loggers = array();
	}

	/**
	 * Gets a logger instance for the given name.
	 *
	 * t3lib_div::makeInstance('t3lib_log_LogManager')->getLogger('main.sub.subsub');
	 *
	 * $name can also be submitted as a underscore-separated string, which will
	 * be converted to dots. This is useful to call this method with __CLASS__
	 * as parameter.
	 *
	 * @param string $name Logger name, empty to get the global "root" logger.
	 * @return t3lib_log_Logger Logger with name $name
	 */
	public function getLogger($name = '') {

		/** @var $logger t3lib_log_Logger */
		$logger = NULL;

			// Transform class names to the dot-name style
		$name = str_replace('_', '.', $name);

		if (isset($this->loggers[$name])) {
			$logger = $this->loggers[$name];
		} else {

				// Lazy instantiation
			/** @var $logger t3lib_log_Logger */
			$logger = t3lib_div::makeInstance('t3lib_log_Logger', $name);
			$this->loggers[$name] = $logger;

			$this->setWritersForLogger($logger);
			$this->setProcessorsForLogger($logger);
		}

		return $logger;
	}

	/**
	 * For use in unit test context only.
	 *
	 * @param string $name
	 * @return void
	 */
	public function registerLogger($name) {
		$this->loggers[$name] = NULL;
	}

	/**
	 * For use in unit test context only.
	 *
	 * @return array
	 */
	public function getLoggerNames() {
		return array_keys($this->loggers);
	}

	/**
	 * Appends the writers to the given logger as configured.
	 *
	 * @param t3lib_log_Logger $logger Logger to configure
	 * @return void
	 * @throws RangeException
	 */
	protected function setWritersForLogger(t3lib_log_Logger $logger) {
		$configuration = $this->getConfigurationForLogger(self::CONFIGURATION_TYPE_WRITER, $logger->getName());

		foreach ($configuration as $severityLevel => $writer) {
			foreach ($writer as $logWriterClassName => $logWriterOptions) {

				/** @var $logWriter t3lib_log_writer_Writer */
				$logWriter = NULL;
				try {
					$logWriter = t3lib_div::makeInstance($logWriterClassName, $logWriterOptions);
					$logger->addWriter($severityLevel, $logWriter);
				} catch (RangeException $e) {
					$logger->warning('Instantiation of LogWriter "' . $logWriterClassName . '" failed for logger ' . $logger->getName() . ' (' . $e->getMessage() . ')');
				}
			}
		}
	}

	/**
	 * Appends the processors to the given logger as configured.
	 *
	 * @param t3lib_log_Logger $logger Logger to configure
	 * @return void
	 * @throws RangeException
	 */
	protected function setProcessorsForLogger(t3lib_log_Logger $logger) {
		$configuration = $this->getConfigurationForLogger(self::CONFIGURATION_TYPE_PROCESSOR, $logger->getName());

		foreach ($configuration as $severityLevel => $processor) {
			foreach ($processor as $logProcessorClassName => $logProcessorOptions) {

				/** @var $logProcessor t3lib_log_processor_Processor */
				$logProcessor = NULL;
				try {
					$logProcessor = t3lib_div::makeInstance($logProcessorClassName, $logProcessorOptions);
					$logger->addProcessor($severityLevel, $logProcessor);
				} catch (RangeException $e) {
					$logger->warning('Instantiation of LogProcessor "' . $logProcessorClassName . '" failed for logger ' . $logger->getName() . ' (' . $e->getMessage() . ')');
				}
			}
		}
	}

	/**
	 * Returns the configuration from $TYPO3_CONF_VARS['LOG'] as
	 * hierarchical array for different components of the class hierarchy.
	 *
	 * @param string $configurationType Type of config to return (writer, processor)
	 * @param string $loggerName Logger name
	 * @throws RangeException
	 * @return array
	 */
	protected function getConfigurationForLogger($configurationType, $loggerName) {

			// Split up the logger name (dot-separated) into its parts
		$explodedName = explode('.', $loggerName);

			// Search in the $TYPO3_CONF_VARS['LOG'] array
			// for these keys, for example "writerConfiguration"
		$configurationKey = $configurationType . 'Configuration';

		$configuration = $GLOBALS['TYPO3_CONF_VARS']['LOG'];

		$result = (!empty($configuration[$configurationKey]) ? $configuration[$configurationKey] : array());

			// Walk from general to special (t3lib, t3lib.db, t3lib.db.foo)
			// and search for the most specific configuration
		foreach ($explodedName as $partOfClassName) {
			if (!empty($configuration[$partOfClassName][$configurationKey])) {
				$result = $configuration[$partOfClassName][$configurationKey];
			}
			$configuration = $configuration[$partOfClassName];
		}

			// Validate the config
		foreach ($result as $level => $unused) {
			try {
				t3lib_log_Level::validateLevel($level);
			} catch (RangeException $e) {
				throw new RangeException(
					'The given severity level "' . htmlspecialchars($level) . '" for ' . $configurationKey . ' of logger "' .
						$loggerName . '" is not valid.',
					1326406447
				);
			}
		}

		return $result;
	}
}

?>