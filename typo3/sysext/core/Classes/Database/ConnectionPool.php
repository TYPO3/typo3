<?php
declare (strict_types = 1);
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

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Manager that handles opening/retrieving database connections.
 *
 * It's a facade to the actual Doctrine DBAL DriverManager that implements TYPO3
 * specific functionality like mapping individual tables to different database
 * connections.
 *
 * getConnectionFotTable() is the only supported way to get a connection that
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

        $connectionName = ConnectionPool::DEFAULT_CONNECTION_NAME;
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
     * @internal
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

        if (empty($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName])
            || !is_array($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName])
        ) {
            throw new \RuntimeException(
                'The requested database connection named "' . $connectionName . '" has not been configured.',
                1459422492
            );
        }

        $connectionParams = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName];
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
            $connectionParams['charset'] = 'utf-8';
        }
        /** @var Connection $conn */
        $conn = DriverManager::getConnection($connectionParams);
        $conn->setFetchMode(\PDO::FETCH_ASSOC);
        $conn->prepareConnection($connectionParams['initCommands'] ?? '');

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
}
