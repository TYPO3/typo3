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

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Connection extends \Doctrine\DBAL\Connection
{
    /**
     * Represents a SQL NULL data type.
     */
    const PARAM_NULL = \PDO::PARAM_NULL; // 0

    /**
     * Represents a SQL INTEGER data type.
     */
    const PARAM_INT = \PDO::PARAM_INT; // 1

    /**
     * Represents a SQL CHAR, VARCHAR data type.
     */
    const PARAM_STR = \PDO::PARAM_STR; // 2

    /**
     * Represents a SQL large object data type.
     */
    const PARAM_LOB = \PDO::PARAM_LOB; // 3

    /**
     * Represents a recordset type. Not currently supported by any drivers.
     */
    const PARAM_STMT = \PDO::PARAM_STMT; // 4

    /**
     * Represents a boolean data type.
     */
    const PARAM_BOOL = \PDO::PARAM_BOOL; // 5

    /**
     * @var array
     */
    private $prepareConnectionCommands = [];

    /**
     * Prevents the duplicate registration of event handlers and types for this connection.
     * If true the events will not be initialized any more in the getDatabasePlatform() method.
     *
     * @var bool
     */
    private $customConnectSetupExecuted = false;

    /**
     * Initializes a new instance of the Connection class.
     *
     * @param array $params The connection parameters.
     * @param Driver $driver The driver to use.
     * @param Configuration|null $config The configuration, optional.
     * @param EventManager|null $em The event manager, optional.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __construct(array $params, Driver $driver, Configuration $config = null, EventManager $em = null)
    {
        parent::__construct($params, $driver, $config, $em);
        $this->_expr = GeneralUtility::makeInstance(ExpressionBuilder::class, $this);
    }

    /**
     * Gets the DatabasePlatform for the connection and initializes custom types and event listeners.
     *
     * @return \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    public function connect(): bool
    {
        // Early return if the connection is already open and custom setup has been done.
        if ($this->customConnectSetupExecuted || !parent::connect()) {
            return false;
        }

        foreach ($this->prepareConnectionCommands as $command) {
            if ($this->executeUpdate($command) === false) {
                GeneralUtility::sysLog(
                    'Could not initialize DB connection with query "' . $command . '": ' . $this->errorInfo(),
                    'core',
                    GeneralUtility::SYSLOG_SEVERITY_ERROR
                );
            }
        }

        $this->customConnectSetupExecuted = true;

        return true;
    }

    /**
     * Creates a new instance of a SQL query builder.
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(QueryBuilder::class, $this);
    }

    /**
     * Quotes a string so it can be safely used as a table or column name, even if
     * it is a reserved name.
     * EXAMPLE: tableName.fieldName => "tableName"."fieldName"
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * @param string $identifier The name to be quoted.
     *
     * @return string The quoted name.
     */
    public function quoteIdentifier($identifier): string
    {
        if ($identifier === '*') {
            return $identifier;
        }

        return parent::quoteIdentifier($identifier);
    }

    /**
     * Quotes an array of column names so it can be safely used, even if the name is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * @param array $input
     *
     * @return array
     */
    public function quoteIdentifiers(array $input): array
    {
        return array_map([$this, 'quoteIdentifier'], $input);
    }

    /**
     * Quotes an associative array of column-value so the column names can be safely used, even
     * if the name is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * @param array $input
     *
     * @return array
     */
    public function quoteColumnValuePairs(array $input): array
    {
        return array_combine($this->quoteIdentifiers(array_keys($input)), array_values($input));
    }

    /**
     * Detect if the column types are specified by column name or using
     * positional information. In the first case quote the field names
     * accordingly.
     *
     * @param array $input
     * @return array
     */
    protected function quoteColumnTypes(array $input): array
    {
        if (!is_string(key($input))) {
            return $input;
        }

        return $this->quoteColumnValuePairs($input);
    }

    /**
     * Inserts a table row with specified data.
     *
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableName The name of the table to insert data into.
     * @param array $data An associative array containing column-value pairs.
     * @param array $types Types of the inserted data.
     *
     * @return int The number of affected rows.
     */
    public function insert($tableName, array $data, array $types = []): int
    {
        return parent::insert(
            $this->quoteIdentifier($tableName),
            $this->quoteColumnValuePairs($data),
            $this->quoteColumnTypes($types)
        );
    }

    /**
     * Bulk inserts table rows with specified data.
     *
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableName The name of the table to insert data into.
     * @param array $data An array containing associative arrays of column-value pairs.
     * @param array $columns An array containing associative arrays of column-value pairs.
     * @param array $types Types of the inserted data.
     *
     * @return int The number of affected rows.
     */
    public function bulkInsert(string $tableName, array $data, array $columns = [], array $types = []): int
    {
        $query = GeneralUtility::makeInstance(Query\BulkInsertQuery::class, $this, $tableName, $columns);
        foreach ($data as $values) {
            $query->addValues($values, $types);
        }

        return $query->execute();
    }

    /**
     * Executes an SQL SELECT statement on a table.
     *
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string[] $columns The columns of the table which to select.
     * @param string $tableName The name of the table on which to select.
     * @param array $identifiers The selection criteria. An associative array containing column-value pairs.
     * @param string[] $groupBy The columns to group the results by.
     * @param array $orderBy Associative array of column name/sort directions pairs.
     * @param int $limit The maximum number of rows to return.
     * @param int $offset The first result row to select (when used with limit)
     *
     * @return Statement The executed statement.
     */
    public function select(
        array $columns,
        string $tableName,
        array $identifiers = [],
        array $groupBy = [],
        array $orderBy = [],
        int $limit = 0,
        int $offset = 0
    ): Statement {
        $query = $this->createQueryBuilder();
        $query->select(...$columns)
            ->from($tableName);

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

        return $query->execute();
    }

    /**
     * Executes an SQL UPDATE statement on a table.
     *
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableName The name of the table to update.
     * @param array $data An associative array containing column-value pairs.
     * @param array $identifier The update criteria. An associative array containing column-value pairs.
     * @param array $types Types of the merged $data and $identifier arrays in that order.
     *
     * @return int The number of affected rows.
     */
    public function update($tableName, array $data, array $identifier, array $types = []): int
    {
        return parent::update(
            $this->quoteIdentifier($tableName),
            $this->quoteColumnValuePairs($data),
            $this->quoteColumnValuePairs($identifier),
            $this->quoteColumnTypes($types)
        );
    }

    /**
     * Executes an SQL DELETE statement on a table.
     *
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableName The name of the table on which to delete.
     * @param array $identifier The deletion criteria. An associative array containing column-value pairs.
     * @param array $types The types of identifiers.
     *
     * @return int The number of affected rows.
     */
    public function delete($tableName, array $identifier, array $types = []): int
    {
        return parent::delete(
            $this->quoteIdentifier($tableName),
            $this->quoteColumnValuePairs($identifier),
            $this->quoteColumnTypes($types)
        );
    }

    /**
     * Executes an SQL TRUNCATE statement on a table.
     *
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     * Table expression is not escaped and not safe for user-input.
     *
     * @param string $tableName The name of the table to truncate.
     * @param bool $cascade Not supported on many platforms but would cascade the truncate by following foreign keys.
     *
     * @return int The number of affected rows. For a truncate this is unreliable as theres no meaningful information.
     */
    public function truncate(string $tableName, bool $cascade = false): int
    {
        return $this->executeUpdate(
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
     *
     * @return int The number of rows counted
     */
    public function count(string $item, string $tableName, array $identifiers): int
    {
        $query = $this->createQueryBuilder();
        $query->count($item)
            ->from($tableName);

        foreach ($identifiers as $identifier => $value) {
            $query->andWhere($query->expr()->eq($identifier, $query->createNamedParameter($value)));
        }

        return (int)$query->execute()->fetchColumn(0);
    }

    /**
     * Returns the version of the current platform if applicable.
     *
     * If no version information is available only the platform name will be shown.
     * If the platform name is unknown or unsupported the driver name will be shown.
     *
     * @return string
     * @internal
     */
    public function getServerVersion(): string
    {
        $version = $this->getDatabasePlatform()->getName();
        switch ($version) {
            case 'mysql':
            case 'pdo_mysql':
            case 'drizzle_pdo_mysql':
                $version = 'MySQL';
                break;
            case 'postgresql':
            case 'pdo_postgresql':
                $version = 'PostgreSQL';
                break;
            case 'oci8':
            case 'pdo_oracle':
                $version = 'Oracle';
                break;
            case 'sqlsrv':
            case 'pdo_sqlsrv':
                $version = 'SQLServer';
                break;
        }

        // Driver does not support version specific platforms.
        if (!$this->getDriver() instanceof \Doctrine\DBAL\VersionAwarePlatformDriver) {
            return $version;
        }

        if ($this->getWrappedConnection() instanceof \Doctrine\DBAL\Driver\ServerInfoAwareConnection
            && !$this->getWrappedConnection()->requiresQueryForServerVersion()
        ) {
            $version .= ' ' . $this->getWrappedConnection()->getServerVersion();
        }

        return $version;
    }

    /**
     * Execute commands after initializing a new connection.
     *
     * @param string $commands
     */
    public function prepareConnection(string $commands)
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
     * Returns the ID of the last inserted row or sequence value.
     * If table and fieldname have been provided it tries to build
     * the sequence name for PostgreSQL. For MySQL the parameters
     * are not required / and only the table name is passed through.
     *
     * @param string|null $tableName
     * @param string $fieldName
     * @return string
     */
    public function lastInsertId($tableName = null, string $fieldName = 'uid'): string
    {
        if ($this->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            return parent::lastInsertId(trim(implode('_', [$tableName, $fieldName, 'seq']), '_'));
        }

        return (string)parent::lastInsertId($tableName);
    }
}
