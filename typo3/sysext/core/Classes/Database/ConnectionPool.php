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

namespace TYPO3\CMS\Core\Database;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\Middleware as DriverMiddleware;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use TYPO3\CMS\Core\Database\Driver\PDOMySql\Driver as PDOMySqlDriver;
use TYPO3\CMS\Core\Database\Driver\PDOPgSql\Driver as PDOPgSqlDriver;
use TYPO3\CMS\Core\Database\Driver\PDOSqlite\Driver as PDOSqliteDriver;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Schema\EventListener\SchemaAlterTableListener;
use TYPO3\CMS\Core\Database\Schema\EventListener\SchemaColumnDefinitionListener;
use TYPO3\CMS\Core\Database\Schema\EventListener\SchemaIndexDefinitionListener;
use TYPO3\CMS\Core\Database\Schema\Types\DateTimeType;
use TYPO3\CMS\Core\Database\Schema\Types\DateType;
use TYPO3\CMS\Core\Database\Schema\Types\EnumType;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
use TYPO3\CMS\Core\Database\Schema\Types\TimeType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Manager that handles opening/retrieving database connections.
 *
 * It's a facade to the actual Doctrine DBAL DriverManager that implements TYPO3
 * specific functionality like mapping individual tables to different database
 * connections.
 *
 * getConnectionForTable() is the only supported way to get a connection that
 * honors the table mapping configuration.
 */
class ConnectionPool
{
    /**
     * @var string
     */
    public const DEFAULT_CONNECTION_NAME = 'Default';

    /**
     * @var Connection[]
     */
    protected static $connections = [];

    /**
     * @var array<non-empty-string,class-string>
     */
    protected array $customDoctrineTypes = [
        EnumType::TYPE => EnumType::class,
        SetType::TYPE => SetType::class,
    ];

    /**
     * @var array<non-empty-string,class-string>
     */
    protected array $overrideDoctrineTypes = [
        Types::DATE_MUTABLE => DateType::class,
        Types::DATETIME_MUTABLE => DateTimeType::class,
        Types::DATETIME_IMMUTABLE => DateTimeType::class,
        Types::TIME_MUTABLE => TimeType::class,
    ];

    /**
     * List of custom drivers and their mappings to the driver classes.
     *
     * @var string[]
     */
    protected static $driverMap = [
        'pdo_mysql' => PDOMySqlDriver::class,
        'pdo_sqlite' => PDOSqliteDriver::class,
        'pdo_pgsql' => PDOPgSqlDriver::class,
        // TODO: not supported yet, need to be checked later
        //        'pdo_oci' => PDOOCIDriver::class,
        //        'drizzle_pdo_mysql' => DrizzlePDOMySQLDriver::class,
    ];

    /**
     * Creates a connection object based on the specified table name.
     *
     * This is the official entry point to get a database connection to ensure
     * that the mapping of table names to database connections is honored.
     *
     * @param string $tableName
     */
    public function getConnectionForTable(string $tableName): Connection
    {
        if (empty($tableName)) {
            throw new \UnexpectedValueException(
                'ConnectionPool->getConnectionForTable() requires a table name to be provided.',
                1459421719
            );
        }

        $connectionName = self::DEFAULT_CONNECTION_NAME;
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$tableName])) {
            $connectionName = (string)$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$tableName];
        }

        return $this->getConnectionByName($connectionName);
    }

    /**
     * Creates a connection object based on the specified identifier.
     *
     * This method should only be used in edge cases. Use getConnectionForTable() so
     * that the tablename<>databaseConnection mapping will be taken into account.
     *
     * @param string $connectionName
     * @throws \Doctrine\DBAL\Exception
     */
    public function getConnectionByName(string $connectionName): Connection
    {
        if (empty($connectionName)) {
            throw new \UnexpectedValueException(
                'ConnectionPool->getConnectionByName() requires a connection name to be provided.',
                1459422125
            );
        }

        if (isset(static::$connections[$connectionName])) {
            return static::$connections[$connectionName];
        }

        $connectionParams = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName] ?? [];
        if (empty($connectionParams)) {
            throw new \RuntimeException(
                'The requested database connection named "' . $connectionName . '" has not been configured.',
                1459422492
            );
        }

        if (empty($connectionParams['wrapperClass'])) {
            $connectionParams['wrapperClass'] = Connection::class;
        }

        if (!is_a($connectionParams['wrapperClass'], Connection::class, true)) {
            throw new \UnexpectedValueException(
                'The "wrapperClass" for the connection name "' . $connectionName .
                '" needs to be a subclass of "' . Connection::class . '".',
                1459422968
            );
        }

        // Transform TYPO3 `tableoptions` to valid `doctrine/dbal` connection param option `defaultTableOptions`
        // @todo TYPO3 database configuration should be changed to directly write defaultTableOptions instead,
        //       with proper upgrade migration. Along with that, default table options for MySQL in
        //       testing-framework and core should be adjusted.
        if (isset($connectionParams['tableoptions'])) {
            $connectionParams['defaultTableOptions'] = array_replace(
                $connectionParams['defaultTableOptions'] ?? [],
                $connectionParams['tableoptions']
            );
            unset($connectionParams['tableoptions']);
        }

        static::$connections[$connectionName] = $this->getDatabaseConnection($connectionParams);

        return static::$connections[$connectionName];
    }

    /**
     * Map custom driver class for certain driver
     *
     * @internal
     */
    protected function mapCustomDriver(array $connectionParams): array
    {
        // if no custom driver is provided, map TYPO3 specific drivers
        if (!isset($connectionParams['driverClass']) && isset(static::$driverMap[$connectionParams['driver']])) {
            $connectionParams['driverClass'] = static::$driverMap[$connectionParams['driver']];
        }

        return $connectionParams;
    }

    /**
     * Return any doctrine driver middlewares, that may have been set up in:
     * $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driverMiddlewares']
     */
    protected function getDriverMiddlewares(array $connectionParams): array
    {
        $middlewares = [];

        foreach ($connectionParams['driverMiddlewares'] ?? [] as $className) {
            if (!in_array(DriverMiddleware::class, class_implements($className) ?: [], true)) {
                throw new \UnexpectedValueException('Doctrine Driver Middleware must implement \Doctrine\DBAL\Driver\Middleware', 1677958727);
            }
            $middlewares[] = GeneralUtility::makeInstance($className);
        }

        return $middlewares;
    }

    /**
     * Creates a connection object based on the specified parameters
     */
    protected function getDatabaseConnection(array $connectionParams): Connection
    {
        $this->registerDoctrineTypes();

        // Default to UTF-8 connection charset
        if (empty($connectionParams['charset'])) {
            $connectionParams['charset'] = 'utf8';
        }

        $connectionParams = $this->mapCustomDriver($connectionParams);
        $middlewares = $this->getDriverMiddlewares($connectionParams);
        $configuration = $middlewares ? (new Configuration())->setMiddlewares($middlewares) : null;

        /** @var Connection $conn */
        $conn = DriverManager::getConnection($connectionParams, $configuration);
        $conn->prepareConnection($connectionParams['initCommands'] ?? '');

        // Register all custom data types in the type mapping
        foreach ($this->customDoctrineTypes as $type => $className) {
            $conn->getDatabasePlatform()->registerDoctrineTypeMapping($type, $type);
        }

        // Register all override data types in the type mapping
        foreach ($this->overrideDoctrineTypes as $type => $className) {
            $conn->getDatabasePlatform()->registerDoctrineTypeMapping($type, $type);
        }

        // Handler for building custom data type column definitions
        // in the SchemaManager
        $conn->getDatabasePlatform()->getEventManager()->addEventListener(
            Events::onSchemaColumnDefinition,
            GeneralUtility::makeInstance(SchemaColumnDefinitionListener::class)
        );

        // Handler for enhanced index definitions in the SchemaManager
        $conn->getDatabasePlatform()->getEventManager()->addEventListener(
            Events::onSchemaIndexDefinition,
            GeneralUtility::makeInstance(SchemaIndexDefinitionListener::class)
        );

        // Handler for adding custom database platform options to ALTER TABLE
        // requests in the SchemaManager
        $conn->getDatabasePlatform()->getEventManager()->addEventListener(
            Events::onSchemaAlterTable,
            GeneralUtility::makeInstance(SchemaAlterTableListener::class)
        );

        return $conn;
    }

    /**
     * Returns the connection specific query builder object that can be used to build
     * complex SQL queries using and object oriented approach.
     */
    public function getQueryBuilderForTable(string $tableName): QueryBuilder
    {
        if (empty($tableName)) {
            throw new \UnexpectedValueException(
                'ConnectionPool->getQueryBuilderForTable() requires a connection name to be provided.',
                1459423448
            );
        }

        return $this->getConnectionForTable($tableName)->createQueryBuilder();
    }

    /**
     * Returns an array containing the names of all currently configured connections.
     *
     * This method should only be used in edge cases. Use getConnectionForTable() so
     * that the tablename<>databaseConnection mapping will be taken into account.
     *
     * @internal
     */
    public function getConnectionNames(): array
    {
        return array_keys($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']);
    }

    /**
     * Register custom and override Doctrine data types implemented by TYPO3.
     * This method is needed by Schema parser to register the types as it does
     * not require a database connection and thus the types don't get registered
     * automatically.
     *
     * @internal
     */
    public function registerDoctrineTypes(): void
    {
        // Register custom data types
        foreach ($this->customDoctrineTypes as $type => $className) {
            if (!Type::hasType($type)) {
                Type::addType($type, $className);
            }
        }
        // Override data types
        foreach ($this->overrideDoctrineTypes as $type => $className) {
            if (!Type::hasType($type)) {
                Type::addType($type, $className);
                continue;
            }
            Type::overrideType($type, $className);
        }
    }

    /**
     * Reset internal list of connections.
     * Currently, primarily used in functional tests to close connections and start
     * new ones in between single tests.
     */
    public function resetConnections(): void
    {
        static::$connections = [];
    }
}
