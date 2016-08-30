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
 * Log levels according to RFC 3164
 */
class LogLevel
{
    /**
     * Emergency: system is unusable
     *
     * You'd likely not be able to reach the system. You better have an SLA in
     * place when this happens.
     *
     * @var int
     */
    const EMERGENCY = 0;

    /**
     * Alert: action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     *
     * @var int
     */
    const ALERT = 1;

    /**
     * Critical: critical conditions
     *
     * Example: unexpected exception.
     *
     * @var int
     */
    const CRITICAL = 2;

    /**
     * Error: error conditions
     *
     * Example: Runtime error.
     *
     * @var int
     */
    const ERROR = 3;

    /**
     * Warning: warning conditions
     *
     * Examples: Use of deprecated APIs, undesirable things that are not
     * necessarily wrong.
     *
     * @var int
     */
    const WARNING = 4;

    /**
     * Notice: normal but significant condition
     *
     * Example: things you should have a look at, nothing to worry about though.
     *
     * @var int
     */
    const NOTICE = 5;

    /**
     * Informational: informational messages
     *
     * Examples: User logs in, SQL logs.
     *
     * @var int
     */
    const INFO = 6;

    /**
     * Debug: debug-level messages
     *
     * Example: Detailed status information.
     *
     * @var int
     */
    const DEBUG = 7;

    /**
     * Reverse look up of log level to level name.
     *
     * @var array
     */
    protected static $levels = [
        self::EMERGENCY => 'EMERGENCY',
        self::ALERT => 'ALERT',
        self::CRITICAL => 'CRITICAL',
        self::ERROR => 'ERROR',
        self::WARNING => 'WARNING',
        self::NOTICE => 'NOTICE',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG'
    ];

    /**
     * Resolves the name of a log level.
     *
     * @param int $level Log level.
     * @return string Log level name.
     */
    public static function getName($level)
    {
        self::validateLevel($level);
        return self::$levels[$level];
    }

    /**
     * Checks a level for validity,
     * whether it is an integer and in the range of 0-7.
     *
     * @param int $level log level to validate
     * @return bool TRUE if the given log level is valid, FALSE otherwise
     */
    public static function isValidLevel($level)
    {
        return \TYPO3\CMS\Core\Utility\MathUtility::isIntegerInRange($level, self::EMERGENCY, self::DEBUG);
    }

    /**
     * Validates a log level.
     *
     * @param int $level log level to validate
     * @return void
     * @throws \Psr\Log\InvalidArgumentException if the given log level is invalid
     */
    public static function validateLevel($level)
    {
        if (!self::isValidLevel($level)) {
            throw new \Psr\Log\InvalidArgumentException('Invalid Log Level.', 1321637121);
        }
    }

    /**
     * Normalizes level by converting it from string to integer
     *
     * @param string $level
     * @return int|string
     */
    public static function normalizeLevel($level)
    {
        if (is_string($level) && defined(__CLASS__ . '::' . strtoupper($level))) {
            $level = constant(__CLASS__ . '::' . strtoupper($level));
        }

        return $level;
    }
}
