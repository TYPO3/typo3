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

namespace TYPO3\CMS\Install\SystemEnvironment;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\DrizzlePDOMySql\Driver as DoctrineDrizzlePDOMySQLDriver;
use Doctrine\DBAL\Driver\IBMDB2\DB2Driver;
use Doctrine\DBAL\Driver\Mysqli\Driver as DoctrineMysqliDriver;
use Doctrine\DBAL\Driver\OCI8\Driver as DoctrineOCI8Driver;
use Doctrine\DBAL\Driver\PDOOracle\Driver as DoctrinePDOOCIDriver;
use Doctrine\DBAL\Driver\SQLAnywhere\Driver as DoctrineSQLAnywhereDriver;
use Doctrine\DBAL\Driver\SQLSrv\Driver as DoctrineSQLSrvDriver;
use TYPO3\CMS\Core\Database\Driver\PDOMySql\Driver as TYPO3PDOMySqlDriver;
use TYPO3\CMS\Core\Database\Driver\PDOPgSql\Driver as TYPO3PDOPgSqlDriver;
use TYPO3\CMS\Core\Database\Driver\PDOSqlite\Driver as TYPO3PDOSqliteDriver;
use TYPO3\CMS\Core\Database\Driver\PDOSqlsrv\Driver as TYPO3PDOSqlSrvDriver;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Install\Exception;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Driver\Mysqli as DatabaseCheckDriverMysqli;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Driver\PdoMysql as DatabaseCheckDriverPdoMysql;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Driver\PDOPgSql as DatabaseCheckDriverPDOPgSql;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Driver\PDOSqlite as DatabaseCheckDriverPDOSqlite;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Driver\PDOSqlsrv as DatabaseCheckDriverPDOSqlsrv;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Driver\SQLSrv as DatabaseCheckDriverSQLSrv;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Platform\MySql as DatabaseCheckPlatformMysql;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Platform\PostgreSql as DatabaseCheckPlatformPostgreSql;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Platform\Sqlite as DatabaseCheckPlatformSqlite;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Platform\SqlSrv as DatabaseCheckPlatformSqlSrv;

/**
 * Check database configuration status
 *
 * This class is a hardcoded requirement check for the database server.
 *
 * The status messages and title *must not* include HTML, use plain
 * text only. The return values of this class are not bound to HTML
 * and can be used in different scopes (eg. as json array).
 *
 * The database requirements checks are separated into driver specific and / or more general requirements
 * specific for each DBMS platform.
 *
 * Example:
 *
 * The driver pdo_mysql requires a different set of checks, then the mysqli
 * driver (it requires other extensions to be loaded by PHP, configuration of that extension, etc.).
 * Those specific checks could be covered in a driver specific check like follows:
 *
 * - Create a new class in typo3/sysext/install/Classes/SystemEnvironment/DatabaseCheck/Driver
 * - It must extend TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Driver\AbstractDriver and implement all methods
 * - Finally it has to be registered in TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck
 *
 * If the requirements are more general for the platform (e.g. MySQL, PostgreSQL, etc.),
 * they should be placed in the platform specific checks and fulfill those requirements:
 *
 * - Create a new class in typo3/sysext/install/Classes/SystemEnvironment/DatabaseCheck/Platform
 * - It must extend TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Platform\AbstractPlatform and implement all methods
 * - Finally it has to be registered in TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class DatabaseCheck implements CheckInterface
{
    /**
     * @var FlashMessageQueue
     */
    private $messageQueue;

    /**
     * List of database platforms to check
     *
     * @var string[]
     */
    private static $databaseDriverToPlatformMapping = [
        DoctrineMysqliDriver::class => DatabaseCheckPlatformMysql::class,
        TYPO3PDOMySqlDriver::class => DatabaseCheckPlatformMysql::class,
        TYPO3PDOPgSqlDriver::class => DatabaseCheckPlatformPostgreSql::class,
        DoctrineSQLSrvDriver::class => DatabaseCheckPlatformSqlSrv::class,
        TYPO3PDOSqlSrvDriver::class => DatabaseCheckPlatformSqlSrv::class,
        TYPO3PDOSqliteDriver::class => DatabaseCheckPlatformSqlite::class,
    ];

    /**
     * @var string[]
     */
    private static $driverMap = [
        'pdo_mysql' => TYPO3PDOMySqlDriver::class,
        'pdo_sqlite' => TYPO3PDOSqliteDriver::class,
        'pdo_pgsql' => TYPO3PDOPgSqlDriver::class,
        'pdo_oci' => DoctrinePDOOCIDriver::class,
        'oci8' => DoctrineOCI8Driver::class,
        'ibm_db2' => DB2Driver::class,
        'pdo_sqlsrv' => TYPO3PDOSqlSrvDriver::class,
        'mysqli' => DoctrineMysqliDriver::class,
        'drizzle_pdo_mysql' => DoctrineDrizzlePDOMySQLDriver::class,
        'sqlanywhere' => DoctrineSQLAnywhereDriver::class,
        'sqlsrv' => DoctrineSQLSrvDriver::class,
    ];

    /**
     * List of database driver to check
     *
     * @var string[]
     */
    private $databaseDriverCheckMap = [
        DoctrineMysqliDriver::class => DatabaseCheckDriverMysqli::class,
        TYPO3PDOMySqlDriver::class => DatabaseCheckDriverPdoMysql::class,
        TYPO3PDOPgSqlDriver::class => DatabaseCheckDriverPDOPgSql::class,
        DoctrineSQLSrvDriver::class => DatabaseCheckDriverSQLSrv::class,
        TYPO3PDOSqlSrvDriver::class => DatabaseCheckDriverPDOSqlsrv::class,
        TYPO3PDOSqliteDriver::class => DatabaseCheckDriverPDOSqlite::class,
    ];

    public function __construct()
    {
        $this->messageQueue = new FlashMessageQueue('install-database-check');
    }

    /**
     * Get status of each database platform identified to be installed on the system
     *
     * @return FlashMessageQueue
     */
    public function getStatus(): FlashMessageQueue
    {
        $installedDrivers = $this->identifyInstalledDatabaseDriver();

        // check requirements of database platform for installed driver
        foreach ($installedDrivers as $driver) {
            try {
                $this->checkDatabasePlatformRequirements($driver);
            } catch (Exception $exception) {
                $this->messageQueue->enqueue(
                    new FlashMessage(
                        '',
                        $exception->getMessage(),
                        FlashMessage::INFO
                    )
                );
            }
        }

        // check requirements of database driver for installed driver
        foreach ($installedDrivers as $driver) {
            try {
                $this->checkDatabaseDriverRequirements($driver);
            } catch (Exception $exception) {
                $this->messageQueue->enqueue(
                    new FlashMessage(
                        '',
                        $exception->getMessage(),
                        FlashMessage::INFO
                    )
                );
            }
        }

        return $this->messageQueue;
    }

    public function checkDatabaseDriverRequirements(string $databaseDriver): FlashMessageQueue
    {
        if (!empty($this->databaseDriverCheckMap[$databaseDriver])) {
            /** @var CheckInterface $databaseDriverCheck */
            $databaseDriverCheck = new $this->databaseDriverCheckMap[$databaseDriver]();
            foreach ($databaseDriverCheck->getStatus() as $message) {
                $this->messageQueue->addMessage($message);
            }

            return $this->messageQueue;
        }

        throw new Exception(
            sprintf(
                'There are no database driver checks available for the given database driver: %s',
                $databaseDriver
            ),
            1572521099
        );
    }

    /**
     * Get the status of a specific database platform
     *
     * @param string $databaseDriver
     * @return FlashMessageQueue
     *
     * @throws Exception
     */
    public function checkDatabasePlatformRequirements(string $databaseDriver): FlashMessageQueue
    {
        static $checkedPlatform = [];
        $databasePlatformClass = self::$databaseDriverToPlatformMapping[$databaseDriver];

        // execute platform checks only once
        if (in_array($databasePlatformClass, $checkedPlatform, true)) {
            return $this->messageQueue;
        }

        if (!empty(self::$databaseDriverToPlatformMapping[$databaseDriver])) {
            $platformMessageQueue = (new $databasePlatformClass())->getStatus();
            foreach ($platformMessageQueue as $message) {
                $this->messageQueue->enqueue($message);
            }
            $checkedPlatform[] = $databasePlatformClass;

            return $this->messageQueue;
        }

        throw new Exception(
            sprintf(
                'There are no database platform checks available for the given database driver: %s',
                $databaseDriver
            ),
            1573753070
        );
    }

    public function identifyInstalledDatabaseDriver(): array
    {
        $installedDrivers = [];

        if (static::isMysqli()) {
            $installedDrivers[] = DoctrineMysqliDriver::class;
        }

        if (static::isPdoMysql()) {
            $installedDrivers[] = TYPO3PDOMySqlDriver::class;
        }

        if (static::isPdoPgsql()) {
            $installedDrivers[] = TYPO3PDOPgSqlDriver::class;
        }

        if (static::isPdoSqlite()) {
            $installedDrivers[] = TYPO3PDOSqliteDriver::class;
        }

        if (static::isPdoSqlSrv()) {
            $installedDrivers[] = TYPO3PDOSqlSrvDriver::class;
        }

        if (static::isSqlSrv()) {
            $installedDrivers[] = Driver\SQLSrv\Driver::class;
        }

        return $installedDrivers;
    }

    /**
     * @param string $databaseDriverName
     * @return string
     *
     * @throws Exception
     */
    public static function retrieveDatabasePlatformByDriverName(string $databaseDriverName): string
    {
        $databaseDriverClassName = static::retrieveDatabaseDriverClassByDriverName($databaseDriverName);
        if (!empty(self::$databaseDriverToPlatformMapping[$databaseDriverClassName])) {
            return self::$databaseDriverToPlatformMapping[$databaseDriverClassName];
        }

        throw new Exception(
            sprintf('There is no database platform available for the given driver: %s', $databaseDriverName),
            1573753057
        );
    }

    /**
     * @param string $driverName
     * @return string
     * @throws Exception
     */
    public static function retrieveDatabaseDriverClassByDriverName(string $driverName): string
    {
        if (!empty(self::$driverMap[$driverName])) {
            return self::$driverMap[$driverName];
        }

        throw new Exception(
            sprintf('There is no database driver available for the given driver name: %s', $driverName),
            1573740447
        );
    }

    public function getMessageQueue(): FlashMessageQueue
    {
        return $this->messageQueue;
    }

    public static function isMysqli(): bool
    {
        return extension_loaded('mysqli');
    }

    public static function isPdoMysql(): bool
    {
        return extension_loaded('pdo_mysql');
    }

    public static function isPdoPgsql(): bool
    {
        return extension_loaded('pdo_pgsql');
    }

    public static function isPdoSqlite(): bool
    {
        return extension_loaded('pdo_sqlite');
    }

    public static function isPdoSqlSrv(): bool
    {
        return extension_loaded('pdo_sqlsrv');
    }

    public static function isSqlSrv(): bool
    {
        return extension_loaded('sqlsrv');
    }
}
