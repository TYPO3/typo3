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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\BulkInsertQuery;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Schema\SchemaInformation;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Connection extends \Doctrine\DBAL\Connection implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Represents a SQL NULL data type.
     */
    public const PARAM_NULL = ParameterType::NULL;

    /**
     * Represents a SQL INTEGER data type.
     */
    public const PARAM_INT = ParameterType::INTEGER;

    /**
     * Represents a SQL CHAR, VARCHAR data type.
     */
    public const PARAM_STR = ParameterType::STRING;

    /**
     * Represents a SQL large object data type.
     */
    public const PARAM_LOB = ParameterType::LARGE_OBJECT;

    /**
     * Represents a boolean data type.
     */
    public const PARAM_BOOL = ParameterType::BOOLEAN;

    /**
     * Represents an array of integer values.
     */
    public const PARAM_INT_ARRAY = ArrayParameterType::INTEGER;

    /**
     * Represents an array of string values.
     */
    public const PARAM_STR_ARRAY = ArrayParameterType::STRING;

    private ExpressionBuilder $expressionBuilder;
    private array $prepareConnectionCommands = [];
    public string $defaultRestrictionContainer = DefaultRestrictionContainer::class;

    /**
     * Initializes a new instance of the Connection class.
     *
     * @param array $params The connection parameters.
     * @param Driver $driver The driver to use.
     * @param Configuration|null $config The configuration, optional.
     */
    public function __construct(array $params, Driver $driver, ?Configuration $config = null)
    {
        parent::__construct($params, $driver, $config);
        $this->expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $this);
    }

    /**
     * Gets the DatabasePlatform for the connection and initializes custom types and event listeners.
     */
    protected function connect(): ConnectionInterface
    {
        if ($this->_conn !== null) {
            return $this->_conn;
        }
        // Early return if the connection is already open and custom setup has been done.
        $connection = parent::connect();
        foreach ($this->prepareConnectionCommands as $command) {
            $this->executeStatement($command);
        }
        return $connection;
    }

    /**
     * Creates a new instance of a SQL query builder.
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(QueryBuilder::class, $this, GeneralUtility::makeInstance($this->defaultRestrictionContainer));
    }

    /**
     * Quotes a string so it can be safely used as a table or column name, even if
     * it is a reserved name.
     * EXAMPLE: tableName.fieldName => "tableName"."fieldName"
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * @param string $identifier The name to be quoted.
     * @return string The quoted name.
     */
    public function quoteIdentifier(string $identifier): string
    {
        if ($identifier === '*') {
            return $identifier;
        }
        return parent::quoteIdentifier($identifier);
    }

    /**
     * Quotes an array of column names, so it can be safely used, even if the name is a reserved name.
     * Delimiting style depends on the underlying database platform that is being used.
     */
    public function quoteIdentifiers(array $input): array
    {
        return array_map($this->quoteIdentifier(...), $input);
    }

    /**
     * Quotes an associative array of column-value so the column names can be safely used, even
     * if the name is a reserved name.
     * Delimiting style depends on the underlying database platform that is being used.
     */
    public function quoteColumnValuePairs(array $input): array
    {
        return array_combine($this->quoteIdentifiers(array_keys($input)), array_values($input));
    }

    /**
     * Detect if the column types are specified by column name or using
     * positional information. In the first case quote the field names
     * accordingly.
     */
    protected function quoteColumnTypes(array $input): array
    {
        if (!is_string(key($input))) {
            return $input;
        }
        return $this->quoteColumnValuePairs($input);
    }

    /**
     * Quotes like wildcards for given string value.
     *
     * @param string $value The value to be quoted.
     * @return string The quoted value.
     */
    public function escapeLikeWildcards(string $value): string
    {
        return addcslashes($value, '_%');
    }

    /**
     * Inserts a table row with specified data.
     *
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     *
     * @param string $tableName The name of the table to insert data into.
     * @param array $data An associative array containing column-value pairs.
     * @param array $types Types of the inserted data.
     * @return int The number of affected rows.
     */
    public function insert(string $tableName, array $data, array $types = []): int
    {
        $this->ensureDatabaseValueTypes($tableName, $data, $types);
        return parent::insert(
            $this->quoteIdentifier($tableName),
            $this->quoteColumnValuePairs($data),
            $this->quoteColumnTypes($types)
        );
    }

    /**
     * Bulk inserts table rows with specified data.
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     *
     * @param string $tableName The name of the table to insert data into.
     * @param array $data An array containing associative arrays of column-value pairs or just the values to be inserted.
     * @param array $columns An array containing the column names of the data which should be inserted.
     * @param array $types Types of the inserted data.
     * @return int The number of affected rows.
     */
    public function bulkInsert(string $tableName, array $data, array $columns = [], array $types = []): int
    {
        $totalAffectedRows = 0;
        $columnLength = $columns !== [] ? count($columns) : 1000;
        $maxBindParameters = PlatformInformation::getMaxBindParameters($this->getDatabasePlatform());
        $maxChunkSize = (int)(($maxBindParameters / $columnLength) / 2);
        $chunks = array_chunk($data, $maxChunkSize);
        foreach ($chunks as $chunk) {
            $query = GeneralUtility::makeInstance(BulkInsertQuery::class, $this, $tableName, $columns);
            foreach ($chunk as $values) {
                $this->ensureDatabaseValueTypes($tableName, $values, $types);
                $query->addValues($values, $types);
            }
            $totalAffectedRows += $query->execute();
        }
        return $totalAffectedRows;
    }

    /**
     * Executes an SQL SELECT statement on a table.
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     *
     * @param string[] $columns The columns of the table which to select.
     * @param string $tableName The name of the table on which to select.
     * @param array $identifiers The selection criteria. An associative array containing column-value pairs.
     * @param string[] $groupBy The columns to group the results by.
     * @param array $orderBy Associative array of column name/sort directions pairs.
     * @param int $limit The maximum number of rows to return.
     * @param int $offset The first result row to select (when used with limit)
     * @return Result The executed statement.
     */
    public function select(
        array $columns,
        string $tableName,
        array $identifiers = [],
        array $groupBy = [],
        array $orderBy = [],
        int $limit = 0,
        int $offset = 0
    ) {
        $query = $this->createQueryBuilder();
        $query->select(...$columns)->from($tableName);
        foreach ($identifiers as $identifier => $value) {
            $query->andWhere($query->expr()->eq($identifier, $query->createNamedParameter($value)));
        }
        foreach ($orderBy as $fieldName => $order) {
            $query->addOrderBy($fieldName, $order);
        }
        if (!empty($groupBy)) {
            $query->groupBy(...$groupBy);
        }
        if ($limit > 0) {
            $query->setMaxResults($limit);
            $query->setFirstResult($offset);
        }
        return $query->executeQuery();
    }

    /**
     * Executes an SQL UPDATE statement on a table.
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     *
     * @param string $tableName The name of the table to update.
     * @param array $data An associative array containing column-value pairs.
     * @param array $identifier The update criteria. An associative array containing column-value pairs.
     * @param array $types Types of the merged $data and $identifier arrays in that order.
     * @return int The number of affected rows.
     */
    public function update(string $tableName, array $data, array $identifier = [], array $types = []): int
    {
        $this->ensureDatabaseValueTypes($tableName, $data, $types);
        return parent::update(
            $this->quoteIdentifier($tableName),
            $this->quoteColumnValuePairs($data),
            $this->quoteColumnValuePairs($identifier),
            $this->quoteColumnTypes($types)
        );
    }

    /**
     * Executes an SQL DELETE statement on a table.
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     *
     * @param string $tableName The name of the table on which to delete.
     * @param array $identifier The deletion criteria. An associative array containing column-value pairs.
     * @param array $types The types of identifiers.
     * @return int The number of affected rows.
     */
    public function delete(string $tableName, array $identifier = [], array $types = []): int
    {
        return parent::delete(
            $this->quoteIdentifier($tableName),
            $this->quoteColumnValuePairs($identifier),
            $this->quoteColumnTypes($types)
        );
    }

    /**
     * Executes an SQL TRUNCATE statement on a table.
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     *
     * @param string $tableName The name of the table to truncate.
     * @param bool $cascade Not supported on many platforms but would cascade the truncate by following foreign keys.
     * @return int The number of affected rows. For a truncate this is unreliable as there is no meaningful information.
     */
    public function truncate(string $tableName, bool $cascade = false): int
    {
        return $this->executeStatement(
            $this->getDatabasePlatform()->getTruncateTableSQL(
                $this->quoteIdentifier($tableName),
                $cascade
            )
        );
    }

    /**
     * Executes an SQL SELECT COUNT() statement on a table and returns the count result.
     *
     * @param string $item The column/expression of the table which to count
     * @param string $tableName The name of the table on which to count.
     * @param array $identifiers The selection criteria. An associative array containing column-value pairs.
     * @return int The number of rows counted
     */
    public function count(string $item, string $tableName, array $identifiers): int
    {
        $query = $this->createQueryBuilder();
        $query->count($item)->from($tableName);
        foreach ($identifiers as $identifier => $value) {
            $query->andWhere($query->expr()->eq($identifier, $query->createNamedParameter($value)));
        }
        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * Returns the version of the current platform if applicable, containing the platform as prefix.
     *
     * If no version information is available only the platform name will be shown.
     * If the platform name is unknown or unsupported the driver name will be shown.
     *
     * @internal only and not part of public API.
     */
    public function getPlatformServerVersion(): string
    {
        $platform = $this->getDatabasePlatform();
        $version = trim($this->getServerVersion());
        if ($version !== '') {
            $version = ' ' . $version;
        }
        return match (true) {
            // @todo Check if we should use 'MariaDB' now directly instead of MySQL as an alias.
            $platform instanceof DoctrineMariaDBPlatform => 'MySQL' . $version,
            $platform instanceof DoctrineMySQLPlatform => 'MySQL' . $version,
            $platform instanceof DoctrinePostgreSQLPlatform => 'PostgreSQL' . $version,
            default => (str_replace('Platform', '', array_reverse(explode('\\', $platform::class))[0])) . $version,
        };
    }

    /**
     * Execute commands after initializing a new connection.
     */
    public function prepareConnection(string $commands): void
    {
        if (empty($commands)) {
            return;
        }
        $this->prepareConnectionCommands = GeneralUtility::trimExplode(
            LF,
            str_replace(
                '\' . LF . \'',
                LF,
                $commands
            ),
            true
        );
    }

    /**
     * Returns the ID of the last inserted row.
     * If the underlying driver does not support identity columns, an exception is thrown.
     *
     * @return numeric-string
     */
    public function lastInsertId(): string
    {
        return (string)parent::lastInsertId();
    }

    /**
     * Gets the ExpressionBuilder for the connection.
     */
    public function getExpressionBuilder(): ExpressionBuilder
    {
        return $this->expressionBuilder;
    }

    /**
     * This method ensures that data values a properly converted to their database equivalent.
     * Additionally, it adds the proper types to the type-array, if this has no manual preset types.
     * Note: Types are *only* added if not given externally.
     *
     * @internal Should be private, but mocked in tests currently.
     */
    protected function ensureDatabaseValueTypes(string $tableName, array &$data, array &$types): void
    {
        // If types are incoming already (meaning they're hand over to insert() for instance), don't auto-set them.
        $setAllTypes = $types === [];
        $tableInfo = $this->getSchemaInformation()->getTableInfo($tableName);
        $databasePlatform = $this->getDatabasePlatform();
        array_walk($data, function (mixed &$value, string $key) use ($tableInfo, $setAllTypes, &$types, $databasePlatform): void {
            $typeName = ($types[$key] ?? '');
            if (!$setAllTypes && is_string($typeName) && $typeName !== '' && Type::hasType($typeName)) {
                $types[$key] = Type::getType($typeName)->getBindingType();
            } elseif ($typeName instanceof Type) {
                $types[$key] = $typeName->getBindingType();
            }
            if ($tableInfo->hasColumnInfo($key)) {
                $type = $tableInfo->getColumnInfo($key)->getType();
                if ($setAllTypes) {
                    $types[$key] = $type->getBindingType();
                }
                $value = $type->convertToDatabaseValue($value, $databasePlatform);
            }
        });
    }

    /**
     * @internal May vanish anytime, currently used core-internal at some places.
     */
    public function getSchemaInformation(): SchemaInformation
    {
        return new SchemaInformation(
            $this,
            GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime'),
            GeneralUtility::makeInstance(CacheManager::class)->getCache('database_schema'),
            GeneralUtility::makeInstance(PackageDependentCacheIdentifier::class),
        );
    }

    /**
     * Executes a function in a transaction.
     *
     * The function gets passed this Connection instance as an (optional) parameter.
     *
     * If an exception occurs during execution of the function or transaction commit,
     * the transaction is rolled back and the exception re-thrown.
     *
     * @param \Closure(self):T $func The function to execute transactionally.
     *
     * @return T The value returned by $func
     *
     * @throws \Throwable
     *
     * @template T
     */
    public function transactional(\Closure $func): mixed
    {
        /** @var \Closure(\Doctrine\DBAL\Connection):T $func Required to satisfy PHPStan. */
        return parent::transactional($func);
    }
}
