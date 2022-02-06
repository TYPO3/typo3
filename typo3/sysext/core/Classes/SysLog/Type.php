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

namespace TYPO3\CMS\Core\SysLog;

use Psr\Log\LogLevel;

/**
 * A class defining possible logging types.
 *
 * @internal The logging type system is moving towards PSR-3-defined log levels and channels, this class might get removed without any further notice from TYPO3 v12.0. on.
 */
class Type
{
    public const DB = 1;
    public const FILE = 2;
    public const CACHE = 3;
    public const EXTENSION = 4;
    public const ERROR = 5;
    public const SITE = 6;
    public const SETTING = 254;
    public const LOGIN = 255;

    private static array $channelMap = [
        self::DB => 'content',
        self::FILE => 'file',
        self::CACHE => 'default',
        self::EXTENSION => 'default',
        self::ERROR => 'php',
        self::SITE => 'site',
        self::SETTING => 'default',
        self::LOGIN => 'user',
    ];

    private static array $levelMap = [
        self::DB => LogLevel::INFO,
        self::FILE => LogLevel::INFO,
        self::CACHE => LogLevel::INFO,
        self::EXTENSION => LogLevel::INFO,
        self::ERROR => LogLevel::ERROR,
        self::SITE => LogLevel::INFO,
        self::SETTING => LogLevel::INFO,
        self::LOGIN => LogLevel::INFO,
    ];

    /**
     * @internal
     */
    public static function levelMap(): array
    {
        return self::$levelMap;
    }

    /**
     * @internal
     */
    public static function channelMap(): array
    {
        return self::$channelMap;
    }

    public static function toChannel(int $type): string
    {
        return self::$channelMap[$type] ?? 'default';
    }

    public static function toLevel(int $type): string
    {
        return self::$levelMap[$type] ?? LogLevel::INFO;
    }
}
