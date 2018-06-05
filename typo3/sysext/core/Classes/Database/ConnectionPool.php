<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Database;

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

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Schema\EventListener\SchemaAlterTableListener;
use TYPO3\CMS\Core\Database\Schema\EventListener\SchemaColumnDefinitionListener;
use TYPO3\CMS\Core\Database\Schema\EventListener\SchemaIndexDefinitionListener;
use TYPO3\CMS\Core\Database\Schema\Types\EnumType;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
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
    const DEFAULT_CONNECTION_NAME = 'Default';

    /**
     * @var Connection[]
     */
    protected static $connections = [];

    /**
     * @var array
     */
    protected $customDoctrineTypes = [
        EnumType::TYPE => EnumType::class,
        SetType::TYPE => SetType::class,
    ];

    /**
     * Creates a connection object based on the specified table name.
     *
     * This is the official entry point to get a database connection to ensure
     * that the mapping of table names to database connections is honored.
     *
     * @param string $tableName
     * @return Connection
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
     * @return Connection
     * @throws \Doctrine\DBAL\DBALException
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

        static::$connections[$connectionName] = $this->getDatabaseConnection($connectionParams);

        return static::$connections[$connectionName];
    }

    /**
     * Creates a connection object based on the specified parameters
     *
     * @param array $connectionParams
     * @return Connection
     */
    protected function getDatabaseConnection(array $connectionParams): Connection
    {
        // Default to UTF-8 connection charset
        if (empty($connectionParams['charset'])) {
            $connectionParams['charset'] = 'utf8';
        }

        // Force consistent handling of binary objects across datbase platforms
        // MySQL returns strings by default, PostgreSQL streams.
        if (strpos($connectionParams['driver'], 'pdo_') === 0) {
            $connectionParams['driverOptions'][\PDO::ATTR_STRINGIFY_FETCHES] = true;
        }

        /** @var Connection $conn */
        $conn = DriverManager::getConnection($connectionParams);
        $conn->setFetchMode(\PDO::FETCH_ASSOC);
        $conn->prepareConnection($connectionParams['initCommands'] ?? '');

        // Register custom data types
        foreach ($this->customDoctrineTypes as $type => $className) {
            if (!Type::hasType($type)) {
                Type::addType($type, $className);
            }
        }

        // Register all custom data types in the type mapping
        foreach ($this->customDoctrineTypes as $type => $className) {
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
     *
     * @param string $tableName
     * @return QueryBuilder
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
     * @return array
     */
    public function getConnectionNames(): array
    {
        return array_keys($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']);
    }

    /**
     * Returns the list of custom Doctrine data types implemented by TYPO3.
     * This method is needed by the Schema parser to register the types as it
     * does not require a database connection and thus the types don't get
     * registered automatically.
     *
     * @internal
     * @return array
     */
    public function getCustomDoctrineTypes(): array
    {
        return $this->customDoctrineTypes;
    }

    /**
     * Reset internal list of connections. This is an internal method (for now)
     * currently used in functional tests only to close connections and start
     * new ones in between single tests.
     *
     * @internal May be changed or removed any point in time
     */
    public function resetConnections(): void
    {
        static::$connections = [];
    }
}
