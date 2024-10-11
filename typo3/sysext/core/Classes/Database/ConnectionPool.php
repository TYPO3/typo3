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
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use TYPO3\CMS\Core\Database\Middleware\UsableForConnectionInterface;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Schema\SchemaManager\CoreSchemaManagerFactory;
use TYPO3\CMS\Core\Database\Schema\Types\DateTimeType;
use TYPO3\CMS\Core\Database\Schema\Types\DateType;
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

        static::$connections[$connectionName] = $this->getDatabaseConnection(
            $connectionName,
            $this->getConnectionParams($connectionName),
        );

        return static::$connections[$connectionName];
    }

    protected function getConnectionParams(string $connectionName): array
    {
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

        // Ensure integer value for port.
        if (array_key_exists('port', $connectionParams)) {
            $connectionParams['port'] = (int)($connectionParams['port'] ?? 0);
        }

        return $connectionParams;
    }

    /**
     * Return any doctrine driver middlewares, that may have been set up in:
     * - for all configured connections
     * - $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driverMiddlewares'] for a specific connection
     */
    protected function getDriverMiddlewares(string $connectionName, array $connectionParams): array
    {
        $driverMiddlewares = $this->getOrderedConnectionDriverMiddlewareConfiguration($connectionName, $connectionParams);
        $middlewares = [];
        foreach ($driverMiddlewares as $middlewareConfiguration) {
            $className = $middlewareConfiguration['target'];
            $disabled = $middlewareConfiguration['disabled'];
            if ($disabled === true) {
                // Middleware disabled, skip to next middleware.
                continue;
            }

            $middlewares[] = GeneralUtility::makeInstance($className);
        }

        return $middlewares;
    }

    /**
     * @internal only for `ext:lowlevel` usage to retrieve configuration overview.     *
     * @return array
     */
    public function getConnectionMiddlewareConfigurationArrayForLowLevelConfiguration(): array
    {
        $configurationArray = [
            'Raw' => [
                'GlobalDriverMiddlewares' => $GLOBALS['TYPO3_CONF_VARS']['DB']['globalDriverMiddlewares'] ?? [],
                'Connections' => [],
            ],
            'Connections' => [],
        ];
        foreach (array_keys($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']) as $connectionName) {
            $connectionParams = $this->getConnectionParams($connectionName);
            $configurationArray['Raw']['Connections'][$connectionName] = $connectionParams;
            $configurationArray['Connections'][$connectionName] = $this->getOrderedConnectionDriverMiddlewareConfiguration($connectionName, $connectionParams);
        }
        return $configurationArray;
    }

    /**
     * @param array $connectionParams
     * @return array<non-empty-string, array{target: class-string, disabled: bool, after: string[], before: string[], type: string}>
     */
    protected function getOrderedConnectionDriverMiddlewareConfiguration(string $connectionName, array $connectionParams): array
    {
        /** @var DriverMiddlewareService $driverMiddlewareService */
        $driverMiddlewareService = GeneralUtility::makeInstance(DriverMiddlewareService::class);
        /** @var array<non-empty-string, array{target: class-string, disabled: bool, after: string[], before: string[], type: string}> $driverMiddlewares */
        $driverMiddlewares = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['DB']['globalDriverMiddlewares'] ?? [] as $identifier => $middleware) {
            $identifier = (string)$identifier;
            $driverMiddlewares[$identifier] = $driverMiddlewareService->ensureCompleteMiddlewareConfiguration(
                $driverMiddlewareService->normalizeMiddlewareConfiguration($identifier, $middleware)
            );
            $driverMiddlewares[$identifier]['type'] = 'global';
        }
        foreach ($connectionParams['driverMiddlewares'] ?? [] as $identifier => $middleware) {
            $identifier = (string)$identifier;
            $middleware = array_replace(
                $driverMiddlewares[$identifier] ?? [],
                $driverMiddlewareService->normalizeMiddlewareConfiguration($identifier, $middleware)
            );
            $middleware = $driverMiddlewareService->ensureCompleteMiddlewareConfiguration($middleware);
            $driverMiddlewares[$identifier] = $middleware;
            $driverMiddlewares[$identifier]['type'] = $driverMiddlewares[$identifier]['type']
                ? 'global-with-connection-override'
                : 'connection';
        }
        $driverMiddlewares = array_filter($driverMiddlewares, static function (array $middleware) use ($connectionName, $connectionParams): bool {
            $className = $middleware['target'];
            $classImplements = class_exists($className) ? (class_implements($className) ?: []) : [];
            if (!in_array(DriverMiddleware::class, $classImplements, true)) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'Doctrine Driver Middleware "%s" must implement \Doctrine\DBAL\Driver\Middleware',
                        $className
                    ),
                    1677958727
                );
            }
            if (in_array(UsableForConnectionInterface::class, $classImplements, true)) {
                return GeneralUtility::makeInstance($middleware['target'])->canBeUsedForConnection($connectionName, $connectionParams);
            }

            return true;
        });

        return $driverMiddlewareService->order($driverMiddlewares);
    }

    /**
     * Creates a connection object based on the specified parameters
     */
    protected function getDatabaseConnection(string $connectionName, array $connectionParams): Connection
    {
        $this->registerDoctrineTypes();

        // Default to UTF-8 connection charset
        if (empty($connectionParams['charset'])) {
            $connectionParams['charset'] = 'utf8';
        }

        $middlewares = $this->getDriverMiddlewares($connectionName, $connectionParams);
        $configuration = (new Configuration())
            ->setMiddlewares($middlewares)
            // @link https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-not-setting-a-schema-manager-factory
            ->setSchemaManagerFactory(GeneralUtility::makeInstance(CoreSchemaManagerFactory::class));

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

        return $conn;
    }

    /**
     * Returns the connection specific query builder object that can be used to build
     * complex SQL queries using and object-oriented approach.
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
