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

namespace TYPO3\CMS\Core\Database\Platform;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform as PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;

/**
 * Helper to handle platform specific details
 *
 * @internal
 */
class PlatformInformation
{
    /**
     * @var array
     */
    protected static $identifierLimits = [
        'mysql' => 63,
        'postgresql' => 63,
        'sqlite' => 1024, // arbitrary limit, SQLite is only limited by the total statement length
    ];

    /**
     * @var array
     */
    protected static $bindParameterLimits = [
        'mysql' => 65535,
        'postgresql' => 34464,
        'sqlite' => 999,
    ];

    /**
     * @var string[]
     */
    protected static $charSetMap = [
        'mysql' => 'utf8mb4',
        'postgresql' => 'UTF8',
        'sqlite' => 'utf8',
    ];

    /**
     * @var string[]
     */
    protected static $databaseCreateWithCharsetMap = [
        'mysql' => 'CHARACTER SET %s',
        'postgresql' => "ENCODING '%s'",
    ];

    /**
     * Return the encoding of the given platform
     */
    public static function getCharset(AbstractPlatform $platform): string
    {
        $platformName = static::getPlatformIdentifier($platform);

        return static::$charSetMap[$platformName];
    }

    /**
     * Return the statement to create a database with the desired encoding for the given platform
     */
    public static function getDatabaseCreateStatementWithCharset(AbstractPlatform $platform, string $databaseName): string
    {
        try {
            $createStatement = $platform->getCreateDatabaseSQL($databaseName);
        } catch (DBALException $exception) {
            // just silently ignore that error as the selected database does not support any creation of a database
            return '';
        }

        $platformName = static::getPlatformIdentifier($platform);
        $charset = static::getCharset($platform);

        return $createStatement . ' ' . sprintf(static::$databaseCreateWithCharsetMap[$platformName], $charset);
    }

    /**
     * Return information about the maximum supported length for a SQL identifier.
     *
     * @internal
     */
    public static function getMaxIdentifierLength(AbstractPlatform $platform): int
    {
        $platformName = static::getPlatformIdentifier($platform);

        return self::$identifierLimits[$platformName];
    }

    /**
     * Return information about the maximum number of bound parameters supported on this platform
     *
     * @internal
     */
    public static function getMaxBindParameters(AbstractPlatform $platform): int
    {
        $platformName = static::getPlatformIdentifier($platform);

        return self::$bindParameterLimits[$platformName];
    }

    /**
     * Return the platform shortname to use as a lookup key
     *
     * @throws \RuntimeException
     * @internal
     */
    protected static function getPlatformIdentifier(AbstractPlatform $platform): string
    {
        if ($platform instanceof MySQLPlatform) {
            return 'mysql';
        }
        if ($platform instanceof PostgreSqlPlatform) {
            return 'postgresql';
        }
        if ($platform instanceof SqlitePlatform) {
            return 'sqlite';
        }
        throw new \RuntimeException(
            'Unsupported Databaseplatform "' . get_class($platform) . '" detected in PlatformInformation',
            1500958070
        );
    }
}
