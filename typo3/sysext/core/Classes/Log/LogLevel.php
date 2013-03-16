<?php
namespace TYPO3\CMS\Core\Log;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Ingo Renner (ingo@typo3.org)
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
 * Log levels according to RFC 3164
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class LogLevel {

	/**
	 * Emergency: system is unusable
	 *
	 * You'd likely not be able to reach the system. You better have an SLA in
	 * place when this happens.
	 *
	 * @var integer
	 */
	const EMERGENCY = 0;
	/**
	 * Alert: action must be taken immediately
	 *
	 * Example: Entire website down, database unavailable, etc.
	 *
	 * @var integer
	 */
	const ALERT = 1;
	/**
	 * Critical: critical conditions
	 *
	 * Example: unexpected exception.
	 *
	 * @var integer
	 */
	const CRITICAL = 2;
	/**
	 * Error: error conditions
	 *
	 * Example: Runtime error.
	 *
	 * @var integer
	 */
	const ERROR = 3;
	/**
	 * Warning: warning conditions
	 *
	 * Examples: Use of deprecated APIs, undesirable things that are not
	 * necessarily wrong.
	 *
	 * @var integer
	 */
	const WARNING = 4;
	/**
	 * Notice: normal but significant condition
	 *
	 * Example: things you should have a look at, nothing to worry about though.
	 *
	 * @var integer
	 */
	const NOTICE = 5;
	/**
	 * Informational: informational messages
	 *
	 * Examples: User logs in, SQL logs.
	 *
	 * @var integer
	 */
	const INFO = 6;
	/**
	 * Debug: debug-level messages
	 *
	 * Example: Detailed status information.
	 *
	 * @var integer
	 */
	const DEBUG = 7;
	/**
	 * Reverse look up of log level to level name.
	 *
	 * @var array
	 */
	static protected $levels = array(
		self::EMERGENCY => 'EMERGENCY',
		self::ALERT => 'ALERT',
		self::CRITICAL => 'CRITICAL',
		self::ERROR => 'ERROR',
		self::WARNING => 'WARNING',
		self::NOTICE => 'NOTICE',
		self::INFO => 'INFO',
		self::DEBUG => 'DEBUG'
	);

	/**
	 * Resolves the name of a log level.
	 *
	 * @param integer $level Log level.
	 * @return string Log level name.
	 */
	static public function getName($level) {
		self::validateLevel($level);
		return self::$levels[$level];
	}

	/**
	 * Checks a level for validity,
	 * whether it is an integer and in the range of 0-7.
	 *
	 * @param integer $level log level to validate
	 * @return boolean TRUE if the given log level is valid, FALSE otherwise
	 */
	static public function isValidLevel($level) {
		return \TYPO3\CMS\Core\Utility\MathUtility::isIntegerInRange($level, self::EMERGENCY, self::DEBUG);
	}

	/**
	 * Validates a log level.
	 *
	 * @param integer $level log level to validate
	 * @return void
	 * @throws \RangeException if the given log level is invalid
	 */
	static public function validateLevel($level) {
		if (!self::isValidLevel($level)) {
			throw new \RangeException('Invalid Log Level.', 1321637121);
		}
	}

}


?>