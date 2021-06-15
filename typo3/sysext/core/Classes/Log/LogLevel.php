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

use Psr\Log\InvalidArgumentException;

/**
 * Log levels according to RFC 3164
 */
class LogLevel extends \Psr\Log\LogLevel
{
    /**
     * Reverse look up of log level to level name.
     *
     * @var array
     */
    protected static $levels = [
        self::EMERGENCY,
        self::ALERT,
        self::CRITICAL,
        self::ERROR,
        self::WARNING,
        self::NOTICE,
        self::INFO,
        self::DEBUG,
    ];

    /**
     * Resolves the name of a log level and returns it in upper case letters
     *
     * @param int $level Log level.
     * @return string Log level name.
     */
    public static function getName(int $level): string
    {
        return strtoupper(static::getInternalName($level));
    }

    /**
     * Resolves the name of the log level and returns its internal string representation
     *
     * @param int $level
     * @return string
     */
    public static function getInternalName(int $level): string
    {
        self::validateLevel($level);
        return static::$levels[$level];
    }

    /**
     * Checks a level for validity,
     * whether it is an integer and in the range of 0-7.
     *
     * @param int $level log level to validate
     * @return bool TRUE if the given log level is valid, FALSE otherwise
     */
    public static function isValidLevel(int $level): bool
    {
        return isset(static::$levels[$level]);
    }

    /**
     * Validates a log level.
     *
     * @param int $level log level to validate
     * @throws InvalidArgumentException if the given log level is invalid
     */
    public static function validateLevel(int $level): void
    {
        if (!self::isValidLevel($level)) {
            throw new InvalidArgumentException('Invalid Log Level ' . $level, 1321637121);
        }
    }

    /**
     * Normalizes level by converting it from string to integer
     *
     * @param string|int $level
     * @return int
     */
    public static function normalizeLevel($level): int
    {
        if (is_string($level)) {
            if (!defined(__CLASS__ . '::' . strtoupper($level))) {
                throw new InvalidArgumentException('Invalid Log Level ' . $level, 1550247164);
            }
            return array_search(strtolower($level), self::$levels, true);
        }

        return (int)$level;
    }

    /**
     * Returns a list of all log levels at least as severe as the specified level.
     *
     * @param int|string $level
     * @return array<string>
     */
    public static function atLeast($level): array
    {
        $level = self::normalizeLevel($level);
        return array_filter(self::$levels, static fn ($intLevel) => $intLevel <= $level, ARRAY_FILTER_USE_KEY);
    }
}
