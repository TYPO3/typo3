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

namespace TYPO3\CMS\Core\Database\Query;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\From;
use Doctrine\DBAL\Query\Join;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\DBAL\Query\QueryType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Platform\PlatformHelper;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\LimitToTablesRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend;
use TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Storage\Typo3DbQueryParserTest;

/**
 * Object-oriented approach to building SQL queries.
 *
 * It's a facade to the Doctrine DBAL QueryBuilder that implements PHP7 type hinting and automatic
 * quoting of table and column names.
 *
 * <code>
 * $query->select('aField', 'anotherField')
 *       ->from('aTable')
 *       ->where($query->expr()->eq('aField', 1))
 *       ->andWhere($query->expr()->gte('anotherField',10'))
 *       ->execute()
 * </code>
 *
 * Additional functionality included is support for COUNT() and TRUNCATE() statements.
 */
class QueryBuilder extends ConcreteQueryBuilder
{
    protected ConcreteQueryBuilder $concreteQueryBuilder;

    protected QueryRestrictionContainerInterface $restrictionContainer;

    protected array $additionalRestrictions;

    /**
     * List of table aliases which are completely ignored
     * when generating the table restrictions in the where-clause.
     *
     * Aliases added here are part of a LEFT/RIGHT JOIN, having
     * their restrictions applied in the JOIN's ON condition already.
     *
     * @var string[]
     */
    private array $restrictionsAppliedInJoinCondition = [];

    /**
     * Initializes a new QueryBuilder.
     *
     * @param Connection $connection The DBAL Connection.
     * @param QueryRestrictionContainerInterface|null $restrictionContainer
     * @param ConcreteQueryBuilder|null $concreteQueryBuilder
     * @param array|null $additionalRestrictions
     */
    public function __construct(
        Connection $connection,
        ?QueryRestrictionContainerInterface $restrictionContainer = null,
        ?ConcreteQueryBuilder $concreteQueryBuilder = null,
        ?array $additionalRestrictions = null
    ) {
        parent::__construct($connection);
        $this->additionalRestrictions = $additionalRestrictions ?: $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'] ?? [];
        $this->setRestrictions($restrictionContainer ?: GeneralUtility::makeInstance(DefaultRestrictionContainer::class));
        $this->concreteQueryBuilder = $concreteQueryBuilder ?: GeneralUtility::makeInstance(ConcreteQueryBuilder::class, $connection);
    }

    /**
     * Deep clone of the QueryBuilder
     * @see \Doctrine\DBAL\Query\QueryBuilder::__clone()
     */
    public function __clone()
    {
        $this->concreteQueryBuilder = clone $this->concreteQueryBuilder;
        $this->restrictionContainer = clone $this->restrictionContainer;
    }

    /**
     * Gets a string representation of this QueryBuilder which corresponds to
     * the final SQL query being constructed.
     *
     * @return string The string representation of this QueryBuilder.
     */
    public function __toString(): string
    {
        return $this->getSQL();
    }

    public function getRestrictions(): QueryRestrictionContainerInterface
    {
        return $this->restrictionContainer;
    }

    public function setRestrictions(QueryRestrictionContainerInterface $restrictionContainer): void
    {
        foreach ($this->additionalRestrictions as $restrictionClass => $options) {
            if (empty($options['disabled'])) {
                /** @var QueryRestrictionInterface $restriction */
                $restriction = GeneralUtility::makeInstance($restrictionClass);
                $restrictionContainer->add($restriction);
            }
        }
        $this->restrictionContainer = $restrictionContainer;
    }

    /**
     * Limits ALL currently active restrictions of the restriction container to the table aliases given
     */
    public function limitRestrictionsToTables(array $tableAliases): void
    {
        $this->restrictionContainer = GeneralUtility::makeInstance(LimitToTablesRestrictionContainer::class)->addForTables($this->restrictionContainer, $tableAliases);
    }

    /**
     * Re-apply default restrictions
     */
    public function resetRestrictions(): void
    {
        $this->setRestrictions(GeneralUtility::makeInstance(DefaultRestrictionContainer::class));
    }

    /**
     * Gets an ExpressionBuilder used for object-oriented construction of query expressions.
     * This producer method is intended for convenient inline usage. Example:
     *
     * For more complex expression construction, consider storing the expression
     * builder object in a local variable.
     */
    public function expr(): ExpressionBuilder
    {
        return $this->connection->getExpressionBuilder();
    }

    /**
     * Gets the associated DBAL Connection for this query builder.
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Gets the concrete implementation of the query builder
     *
     * @internal
     */
    public function getConcreteQueryBuilder(): \Doctrine\DBAL\Query\QueryBuilder
    {
        return $this->concreteQueryBuilder;
    }

    /**
     * Create prepared statement out of QueryBuilder instance.
     *
     * doctrine/dbal does not provide support for prepared statement
     * in QueryBuilder, but as TYPO3 uses the API throughout the code
     * via QueryBuilder, so the functionality of
     * prepared statements for multiple executions is added.
     *
     * You should be aware that this method will throw a named
     * 'UnsupportedPreparedStatementParameterTypeException()'
     * exception, if 'PARAM_INT_ARRAY' or 'PARAM_STR_ARRAY' is set,
     * as this is not supported for prepared statements directly.
     *
     * NamedPlaceholder are not supported, and if one or
     * more are set a 'NamedParameterNotSupportedForPreparedStatementException'
     * will be thrown.
     */
    public function prepare(): Statement
    {
        $connection = $this->getConnection();
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $originalWhereConditions = null;
        try {
            if ($concreteQueryBuilder->type === QueryType::SELECT) {
                $originalWhereConditions = $this->addAdditionalWhereConditions();
            }
            $sql = $concreteQueryBuilder->getSQL();
            $params = $concreteQueryBuilder->getParameters();
            $types = $concreteQueryBuilder->getParameterTypes();
            $this->throwExceptionOnInvalidPreparedStatementParamArrayType($types);
            $this->throwExceptionOnNamedParameterForPreparedStatement($params);
            $statement = $connection->prepare($sql)->getWrappedStatement();
            $this->bindTypedValues($statement, $params, $types);
        } finally {
            if ($concreteQueryBuilder->type === QueryType::SELECT) {
                $concreteQueryBuilder->resetWhere();
                if ($originalWhereConditions !== null) {
                    $concreteQueryBuilder->where($originalWhereConditions);
                }
            }
        }
        return new Statement($connection, $statement, $sql);
    }

    /**
     * Executes an SQL query (SELECT) and returns a Result.
     *
     * doctrine/dbal decided to split execute() into executeQuery() and
     * executeStatement() for doctrine/dbal:^3.0, like it was done on
     * connection level already, thus these methods are added to this
     * decorator class also as preparation for extension authors, that
     * they are able to write code which is compatible across two core
     * versions and avoid deprecation warning. Additional this will ease
     * backport without the need to switch if execute() is not used anymore.
     */
    public function executeQuery(): Result
    {
        // Set additional query restrictions
        $originalWhereConditions = $this->addAdditionalWhereConditions();
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        try {
            return $concreteQueryBuilder->executeQuery();
        } finally {
            $concreteQueryBuilder->resetWhere();
            if ($originalWhereConditions !== null) {
                $concreteQueryBuilder->where($originalWhereConditions);
            }
        }
    }

    /**
     * Executes an SQL statement (INSERT, UPDATE and DELETE) and returns
     * the number of affected rows.
     *
     * doctrine/dbal decided to split execute() into executeQuery() and
     * executeStatement() for doctrine/dbal:^3.0, like it was done on
     * connection level already, thus these methods are added to this
     * decorator class also as preparation for extension authors, that
     * they are able to write code which is compatible across two core
     * versions and avoid deprecation warning. Additional this will ease
     * backport without the need to switch if execute() is not used anymore.
     *
     * @return int The number of affected rows.
     */
    public function executeStatement(): int
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->executeStatement();
    }

    /**
     * Gets the complete SQL string formed by the current specifications of this QueryBuilder.
     *
     * If the statement is a SELECT TYPE query restrictions based on TCA settings will
     * automatically be applied based on the current QuerySettings.
     *
     * @return string The SQL query string.
     */
    public function getSQL(): string
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        if ($concreteQueryBuilder->type !== QueryType::SELECT) {
            return $concreteQueryBuilder->getSQL();
        }
        // Set additional query restrictions
        $originalWhereConditions =  $this->addAdditionalWhereConditions();
        try {
            $sql = $concreteQueryBuilder->getSQL();
        } finally {
            $concreteQueryBuilder->resetWhere();
            if ($originalWhereConditions !== null) {
                $concreteQueryBuilder->where($originalWhereConditions);
            }
        }
        return $sql;
    }

    /**
     * Sets a query parameter for the query being constructed.
     *
     * @param int<0, max>|string $key Parameter position or name
     */
    public function setParameter(
        int|string $key,
        mixed $value,
        string|ParameterType|Type|ArrayParameterType $type = ParameterType::STRING,
    ): QueryBuilder {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->setParameter($key, $value, $type);
        return $this;
    }

    /**
     * Sets a collection of query parameters for the query being constructed.
     *
     * @param list<mixed>|array<string, mixed> $params The query parameters to set.
     * @param array<int<0, max>, string|Type|ParameterType|ArrayParameterType>|array<string, string|Type|ParameterType|ArrayParameterType> $types The query parameters types to set.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setParameters(array $params, array $types = []): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->setParameters($params, $types);
        return $this;
    }

    /**
     * Gets all defined query parameters for the query being constructed indexed by parameter index or name.
     *
     * @return list<mixed>|array<string, mixed> The currently defined query parameters indexed by parameter index or name.
     */
    public function getParameters(): array
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->getParameters();
    }

    /**
     * Gets a (previously set) query parameter of the query being constructed.
     *
     * @param string|int $key The key (index or name) of the bound parameter.
     *
     * @return mixed The value of the bound parameter.
     */
    public function getParameter(string|int $key): mixed
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->getParameter($key);
    }

    /**
     * Gets all defined query parameter types for the query being constructed indexed by parameter index or name.
     *
     * @return array<int<0, max>, string|Type|ParameterType|ArrayParameterType>|array<string, string|Type|ParameterType|ArrayParameterType> The currently defined query parameter types indexed by parameter index or name.
     */
    public function getParameterTypes(): array
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->getParameterTypes();
    }

    /**
     * Gets a (previously set) query parameter type of the query being constructed.
     *
     * @param string|int $key The key (index or name) of the bound parameter type.
     *
     * @return string|ParameterType|Type|ArrayParameterType The value of the bound parameter type.
     */
    public function getParameterType(string|int $key): string|ParameterType|Type|ArrayParameterType
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->getParameterType($key);
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param int $firstResult The first result to return.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setFirstResult(int $firstResult): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->setFirstResult($firstResult);
        return $this;
    }

    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this QueryBuilder.
     *
     * @return int The position of the first result.
     */
    public function getFirstResult(): int
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->getFirstResult();
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param int|null $maxResults The maximum number of results to retrieve or NULL to retrieve all results.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setMaxResults(?int $maxResults = null): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->setMaxResults($maxResults);
        return $this;
    }

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns 0 if setMaxResults was not applied to this query builder.
     *
     * @return int|null The maximum number of results.
     */
    public function getMaxResults(): ?int
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->getMaxResults();
    }

    /**
     * Specifies the item that is to be counted in the query result.
     * Replaces any previously specified selections, if any.
     *
     * @param string $item Will be quoted according to database platform automatically.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function count(string $item): QueryBuilder
    {
        $countExpr = $this->getCountExpression(
            $item === '*' ? $item : $this->quoteIdentifier($item)
        );
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->select($countExpr);

        return $this;
    }

    protected function getCountExpression(string $column): string
    {
        return 'COUNT(' . $column . ')';
    }

    /**
     * Specifies items that are to be returned in the query result.
     * Replaces any previously specified selections, if any.
     */
    public function select(string ...$selects): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->select(...$this->quoteIdentifiersForSelect($selects));
        return $this;
    }

    /**
     * Adds or removes DISTINCT to/from the query.
     */
    public function distinct(bool $distinct = true): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->distinct($distinct);
        return $this;
    }

    /**
     * Adds an item that is to be returned in the query result.
     */
    public function addSelect(string ...$selects): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->addSelect(...$this->quoteIdentifiersForSelect($selects));
        return $this;
    }

    /**
     * Specifies items that are to be returned in the query result.
     * Replaces any previously specified selections, if any.
     * This should only be used for literal SQL expressions as no
     * quoting/escaping of any kind will be performed on the items.
     *
     * @param string ...$selects Literal SQL expressions to be selected. Warning: No quoting will be done!
     */
    public function selectLiteral(string ...$selects): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->select(...$selects);
        return $this;
    }

    /**
     * Adds an item that is to be returned in the query result. This should
     * only be used for literal SQL expressions as no quoting/escaping of
     * any kind will be performed on the items.
     *
     * @param string ...$selects Literal SQL expressions to be selected.
     */
    public function addSelectLiteral(string ...$selects): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->addSelect(...$selects);
        return $this;
    }

    /**
     * Turns the query being built into a bulk delete query that ranges over
     * a certain table.
     *
     * @param string $table The table whose rows are subject to the deletion.
     *                      Will be quoted according to database platform automatically.
     */
    public function delete(string $table): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->delete($this->quoteIdentifier($table));
        return $this;
    }

    /**
     * Turns the query being built into a bulk update query that ranges over
     * a certain table
     *
     * @param string $table The table whose rows are subject to the update.
     */
    public function update(string $table): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->update($this->quoteIdentifier($table));
        return $this;
    }

    /**
     * Turns the query being built into an insert query that inserts into
     * a certain table
     *
     * @param string $table The table into which the rows should be inserted.
     */
    public function insert(string $table): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->insert($this->quoteIdentifier($table));
        return $this;
    }

    /**
     * Creates and adds a query root corresponding to the table identified by the
     * given alias, forming a cartesian product with any existing query roots.
     *
     * @param string $table The table. Will be quoted according to database platform automatically.
     * @param string|null $alias The alias of the table. Will be quoted according to database platform automatically.
     */
    public function from(string $table, ?string $alias = null): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->from(
            $this->quoteIdentifier($table),
            empty($alias) ? $alias : $this->quoteIdentifier($alias)
        );
        return $this;
    }

    /**
     * Creates and adds a join to the query.
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join The table name to join.
     * @param string $alias The alias of the join table.
     * @param string|null $condition The condition for the join.
     */
    public function join(string $fromAlias, string $join, string $alias, ?string $condition = null): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->innerJoin(
            $this->quoteIdentifier($fromAlias),
            $this->quoteIdentifier($join),
            $this->quoteIdentifier($alias),
            $condition
        );
        return $this;
    }

    /**
     * Creates and adds a join to the query.
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join The table name to join.
     * @param string $alias The alias of the join table.
     * @param string|null $condition The condition for the join.
     */
    public function innerJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->innerJoin(
            $this->quoteIdentifier($fromAlias),
            $this->quoteIdentifier($join),
            $this->quoteIdentifier($alias),
            $condition
        );
        return $this;
    }

    /**
     * Creates and adds a left join to the query.
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join The table name to join.
     * @param string $alias The alias of the join table.
     * @param CompositeExpression|string|null $condition The condition for the join.
     */
    public function leftJoin(string $fromAlias, string $join, string $alias, CompositeExpression|string|null $condition = null): QueryBuilder
    {
        $conditionExpression = (string)$this->expr()->and(
            $condition,
            $this->restrictionContainer->buildExpression([$alias ?? $join => $join], $this->expr())
        );
        $this->restrictionsAppliedInJoinCondition[] = $alias ?? $join;
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->leftJoin(
            $this->quoteIdentifier($fromAlias),
            $this->quoteIdentifier($join),
            $this->quoteIdentifier($alias),
            $conditionExpression
        );
        return $this;
    }

    /**
     * Creates and adds a right join to the query.
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join The table name to join.
     * @param string $alias The alias of the join table.
     * @param string|null $condition The condition for the join.
     */
    public function rightJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): QueryBuilder
    {
        $fromTable = $fromAlias;
        // find the table belonging to the $fromAlias, if it's an alias at all
        foreach ($this->concreteQueryBuilder->from as $from) {
            if (is_string($from->alias) && $from->alias !== '' && $this->unquoteSingleIdentifier($from->alias) === $fromAlias) {
                $fromTable = $this->unquoteSingleIdentifier($from->alias);
                break;
            }
        }
        $conditionExpression = (string)$this->expr()->and(
            $condition,
            $this->restrictionContainer->buildExpression([$fromAlias => $fromTable], $this->expr())
        );
        $this->restrictionsAppliedInJoinCondition[] = $fromAlias;
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->rightJoin(
            $this->quoteIdentifier($fromAlias),
            $this->quoteIdentifier($join),
            $this->quoteIdentifier($alias),
            $conditionExpression
        );
        return $this;
    }

    /**
     * Sets a new value for a column in a bulk update query.
     *
     * @param string $key The column to set.
     * @param mixed $value The value, expression, placeholder, etc.
     * @param bool $createNamedParameter Automatically create a named parameter for the value
     */
    public function set(string $key, $value, bool $createNamedParameter = true, ParameterType|ArrayParameterType $type = Connection::PARAM_STR): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->set(
            $this->quoteIdentifier($key),
            $createNamedParameter ? $this->createNamedParameter($value, $type) : $value
        );
        return $this;
    }

    /**
     * Specifies one or more restrictions to the query result.
     * Replaces any previously specified restrictions, if any.
     *
     * @param string|CompositeExpression ...$predicates
     */
    public function where(...$predicates): QueryBuilder
    {
        // Doctrine DBAL 3.x requires a non-empty $predicate, however TYPO3 uses static values
        // such as PageRepository->$where_hid_del which could be empty
        $predicates = array_filter($predicates, static fn(CompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        if (empty($predicates)) {
            $this->resetWhere();
            return $this;
        }
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->where(...$predicates);
        return $this;
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * conjunction with any previously specified restrictions.
     *
     * @param string|CompositeExpression ...$predicates The query restrictions.
     * @see where()
     */
    public function andWhere(...$predicates): QueryBuilder
    {
        // Doctrine DBAL 3.x requires a non-empty $predicate, however TYPO3 uses static values
        // such as PageRepository->$where_hid_del which could be empty
        $predicates = array_filter($predicates, static fn(CompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        if (empty($predicates)) {
            return $this;
        }
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->andWhere(...$predicates);
        return $this;
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * @param string|CompositeExpression ...$predicates The WHERE statement.
     * @see where()
     */
    public function orWhere(...$predicates): QueryBuilder
    {
        // Doctrine DBAL 3.x requires a non-empty $predicate, however TYPO3 uses static values
        // such as PageRepository->$where_hid_del which could be empty
        $predicates = array_filter($predicates, static fn(CompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        if (empty($predicates)) {
            return $this;
        }
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->orWhere(...$predicates);
        return $this;
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * @param string ...$groupBy The grouping expression.
     */
    public function groupBy(...$groupBy): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->groupBy(...$this->quoteIdentifiers($groupBy));
        return $this;
    }

    /**
     * Adds a grouping expression to the query.
     *
     * @param string ...$groupBy The grouping expression.
     */
    public function addGroupBy(...$groupBy): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->addGroupBy(...$this->quoteIdentifiers($groupBy));
        return $this;
    }

    /**
     * Sets a value for a column in an insert query.
     *
     * @param string $column The column into which the value should be inserted.
     * @param mixed $value The value that should be inserted into the column.
     * @param bool $createNamedParameter Automatically create a named parameter for the value
     */
    public function setValue(string $column, $value, bool $createNamedParameter = true): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->setValue(
            $this->quoteIdentifier($column),
            $createNamedParameter ? $this->createNamedParameter($value) : $value
        );
        return $this;
    }

    /**
     * Specifies values for an insert query indexed by column names.
     * Replaces any previous values, if any.
     *
     * @param array $values The values to specify for the insert query indexed by column names.
     * @param bool $createNamedParameters Automatically create named parameters for all values
     */
    public function values(array $values, bool $createNamedParameters = true): QueryBuilder
    {
        if ($createNamedParameters === true) {
            foreach ($values as &$value) {
                $value = $this->createNamedParameter($value);
            }
        }
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->values($this->quoteColumnValuePairs($values));
        return $this;
    }

    /**
     * Specifies a restriction over the groups of the query.
     * Replaces any previous having restrictions, if any.
     *
     * @param mixed ...$predicates The restriction over the groups.
     */
    public function having(...$predicates): QueryBuilder
    {
        $predicates = array_filter($predicates, static fn(CompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        if (empty($predicates)) {
            $this->resetHaving();
            return $this;
        }
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->having(...$predicates);
        return $this;
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * conjunction with any existing having restrictions.
     *
     * @param mixed ...$predicates The restriction to append.
     */
    public function andHaving(...$predicates): QueryBuilder
    {
        $predicates = array_filter($predicates, static fn(CompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        if (empty($predicates)) {
            return $this;
        }
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->andHaving(...$predicates);
        return $this;
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * disjunction with any existing having restrictions.
     *
     * @param mixed ...$predicates The restriction to add.
     */
    public function orHaving(...$predicates): QueryBuilder
    {
        $predicates = array_filter($predicates, static fn(CompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        if (empty($predicates)) {
            return $this;
        }
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->orHaving(...$predicates);
        return $this;
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string $fieldName The fieldName to order by. Will be quoted according to database platform automatically.
     * @param string|null $order The ordering direction. No automatic quoting/escaping.
     */
    public function orderBy(string $fieldName, ?string $order = null): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->orderBy($this->connection->quoteIdentifier($fieldName), $order);
        return $this;
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param string $fieldName The fieldName to order by. Will be quoted according to database platform automatically.
     * @param string|null $order The ordering direction.
     */
    public function addOrderBy(string $fieldName, ?string $order = null): QueryBuilder
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->addOrderBy($this->connection->quoteIdentifier($fieldName), $order);
        return $this;
    }

    /**
     * Resets the WHERE conditions for the query.
     */
    public function resetWhere(): self
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->resetWhere();
        return $this;
    }

    /**
     * Resets the grouping for the query.
     */
    public function resetGroupBy(): self
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->resetGroupBy();
        return $this;
    }

    /**
     * Resets the HAVING conditions for the query.
     */
    public function resetHaving(): self
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->resetHaving();
        return $this;
    }

    /**
     * Resets the ordering for the query.
     */
    public function resetOrderBy(): self
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->resetOrderBy();
        return $this;
    }

    /**
     * Creates a new named parameter and bind the value $value to it.
     *
     * This method provides a shortcut for {@see Statement::bindValue()}
     * when using prepared statements.
     *
     * The parameter $value specifies the value that you want to bind. If
     * $placeholder is not provided createNamedParameter() will automatically
     * create a placeholder for you. An automatic placeholder will be of the
     * name ':dcValue1', ':dcValue2' etc.
     *
     * Example:
     * <code>
     * $value = 2;
     * $q->eq( 'id', $q->createNamedParameter( $value ) );
     * $stmt = $q->executeQuery(); // executed with 'id = 2'
     * </code>
     *
     * @link http://www.zetacomponents.org
     * @param string|null $placeHolder The name to bind with. The string must start with a colon ':'.
     * @return string the placeholder name used.
     */
    public function createNamedParameter(
        mixed $value,
        string|ParameterType|Type|ArrayParameterType $type = ParameterType::STRING,
        ?string $placeHolder = null
    ): string {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->createNamedParameter($value, $type, $placeHolder);
    }

    /**
     * Creates a new positional parameter and bind the given value to it.
     *
     * Attention: If you are using positional parameters with the query builder you have
     * to be very careful to bind all parameters in the order they appear in the SQL
     * statement , otherwise they get bound in the wrong order which can lead to serious
     * bugs in your code.
     *
     * Example:
     * <code>
     *  $qb = $conn->createQueryBuilder();
     *  $qb->select('u.*')
     *     ->from('users', 'u')
     *     ->where('u.username = ' . $qb->createPositionalParameter('Foo', ParameterType::STRING))
     *     ->orWhere('u.username = ' . $qb->createPositionalParameter('Bar', ParameterType::STRING))
     * </code>
     */
    public function createPositionalParameter(
        mixed $value,
        string|ParameterType|Type|ArrayParameterType $type = ParameterType::STRING,
    ): string {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->createPositionalParameter($value, $type);
    }

    /**
     * Quotes like wildcards for given string value.
     *
     * @param string $value The value to be quoted.
     * @return string The quoted value.
     */
    public function escapeLikeWildcards(string $value): string
    {
        return $this->connection->escapeLikeWildcards($value);
    }

    /**
     * Quotes a given input parameter.
     *
     * @param string $input The parameter to be quoted.
     * @return string Often string, but also int or float or similar depending on $input and platform
     */
    public function quote(string $input): string
    {
        return $this->getConnection()->quote($input);
    }

    /**
     * Quotes a string so it can be safely used as a table or column name, even if
     * it is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * @param string $identifier The name to be quoted.
     * @return string The quoted name.
     */
    public function quoteIdentifier(string $identifier): string
    {
        return $this->getConnection()->quoteIdentifier($identifier);
    }

    /**
     * Quotes an array of column names so it can be safely used, even if the name is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     */
    public function quoteIdentifiers(array $input): array
    {
        return $this->getConnection()->quoteIdentifiers($input);
    }

    /**
     * Quotes an array of column names so it can be safely used, even if the name is a reserved name.
     * Takes into account the special case of the * placeholder that can only be used in SELECT type
     * statements.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * @throws \InvalidArgumentException
     */
    public function quoteIdentifiersForSelect(array $input): array
    {
        foreach ($input as &$select) {
            [$fieldName, $alias, $suffix] = array_pad(
                GeneralUtility::trimExplode(
                    ' AS ',
                    str_ireplace(' as ', ' AS ', $select),
                    true,
                    3
                ),
                3,
                null
            );
            if (!empty($suffix)) {
                throw new \InvalidArgumentException(
                    'QueryBuilder::quoteIdentifiersForSelect() could not parse the select ' . $select . '.',
                    1461170686
                );
            }

            // The SQL * operator must not be quoted. As it can only occur either by itself
            // or preceded by a tablename (tablename.*) check if the last character of a select
            // expression is the * and quote only prepended table name. In all other cases the
            // full expression is being quoted.
            if (substr($fieldName, -2) === '.*') {
                $select = $this->quoteIdentifier(substr($fieldName, 0, -2)) . '.*';
            } elseif ($fieldName !== '*') {
                $select = $this->quoteIdentifier($fieldName);
            }

            // Quote the alias for the current fieldName, if given
            if (!empty($alias)) {
                $select .= ' AS ' . $this->quoteIdentifier($alias);
            }
        }
        return $input;
    }

    /**
     * Quotes an associative array of column-value so the column names can be safely used, even
     * if the name is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     */
    public function quoteColumnValuePairs(array $input): array
    {
        return $this->getConnection()->quoteColumnValuePairs($input);
    }

    /**
     * Implode array to comma separated list with database int-quoted values to be used as direct
     * value list for database 'in(...)' or  'notIn(...') expressions. Empty array will return 'NULL'
     * as string to avoid database query failure, as 'IN()' is invalid, but 'IN(NULL)' is fine.
     *
     * This method should be used with care, the preferred way is to use placeholders. It is however
     * useful when dealing with potentially many values, which could reach placeholder limit quickly.
     *
     * When working with prepared statement from QueryBuilder, use this method to proper quote array
     * with integer values.
     *
     * The method can not be used in queries that re-bind a prepared statement to change values for
     * subsequent execution due to a PDO limitation.
     *
     * Return value should only be used as value list for database queries 'in()' and 'notIn()' .
     */
    public function quoteArrayBasedValueListToIntegerList(array $values): string
    {
        if (empty($values)) {
            return 'NULL';
        }
        // Ensure values are all integer
        $values = GeneralUtility::intExplode(',', implode(',', $values));
        // Ensure all values are quoted as int for used dbms
        $connection = $this;
        array_walk($values, static function (mixed &$value) use ($connection): void {
            $value = $connection->quote((string)$value);
        });
        return implode(',', $values);
    }

    /**
     * Implode array to comma separated list with database string-quoted values to be used as direct
     * value list for database 'in(...)' or  'notIn(...') expressions. Empty array will return 'NULL'
     * as string to avoid database query failure, as 'IN()' is invalid, but 'IN(NULL)' is fine.
     *
     * This method should be used with care, the preferred way is to use placeholders. It is however
     * useful when dealing with potentially many values, which could reach placeholder limit quickly.
     *
     * When working with prepared statement from QueryBuilder, use this method to proper quote array
     * with integer values.
     *
     * The method can not be used in queries that re-bind a prepared statement to change values for
     * subsequent execution due to a PDO limitation.
     *
     * Return value should only be used as value list for database queries 'in()' and 'notIn()' .
     */
    public function quoteArrayBasedValueListToStringList(array $values): string
    {
        if (empty($values)) {
            return 'NULL';
        }
        // Ensure values are all strings
        $values = GeneralUtility::trimExplode(',', implode(',', $values));
        // Ensure all values are quoted as string values for used dbmns
        $connection = $this;
        array_walk($values, static function (mixed &$value) use ($connection): void {
            $value = $connection->quote((string)$value);
        });
        return implode(',', $values);
    }

    /**
     * Creates a cast of the $fieldName to a text datatype depending on the database management system.
     *
     * @param string $fieldName The fieldname will be quoted and casted according to database platform automatically
     *
     * @todo Deprecate this method in favor of {@see ExpressionBuilder::castText()}.
     */
    public function castFieldToTextType(string $fieldName): string
    {
        $databasePlatform = $this->connection->getDatabasePlatform();
        // https://dev.mysql.com/doc/refman/5.7/en/cast-functions.html#function_convert
        if ($databasePlatform instanceof DoctrineMariaDBPlatform || $databasePlatform instanceof DoctrineMySQLPlatform) {
            return sprintf('CONVERT(%s, CHAR)', $this->connection->quoteIdentifier($fieldName));
        }
        // https://www.postgresql.org/docs/current/sql-createcast.html
        if ($databasePlatform instanceof DoctrinePostgreSQLPlatform) {
            return sprintf('%s::text', $this->connection->quoteIdentifier($fieldName));
        }
        // https://www.sqlite.org/lang_expr.html#castexpr
        if ($databasePlatform instanceof DoctrineSQLitePlatform) {
            return sprintf('CAST(%s as TEXT)', $this->connection->quoteIdentifier($fieldName));
        }
        throw new \RuntimeException(
            sprintf(
                '%s is not implemented for the used database platform "%s", yet!',
                __METHOD__,
                get_class($this->connection->getDatabasePlatform())
            ),
            1584637096
        );
    }

    /**
     * Unquote a single identifier (no dot expansion). Used to unquote the table names
     * from the expressionBuilder so that the table can be found in the TCA definition.
     *
     * @param string $identifier The identifier / table name
     * @return string The unquoted table name / identifier
     */
    protected function unquoteSingleIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        $quoteChar = GeneralUtility::makeInstance(PlatformHelper::class)
            ->getIdentifierQuoteCharacter($this->getConnection()->getDatabasePlatform());
        $identifier = trim($identifier, $quoteChar);
        $identifier = str_replace($quoteChar . $quoteChar, $quoteChar, $identifier);
        return $identifier;
    }

    /**
     * @internal This method reflects needed quoted char determination for `unquoteSingleIdentifier()`. Until `doctrine/dbal ^4`
     *           the corresponding information has been used from the database platform class which is not available
     *           anymore.
     */
    protected function getIdentifierQuoteCharacter(): string
    {
        return substr($this->connection->getDatabasePlatform()->quoteSingleIdentifier('fake'), 0, 1);
    }

    /**
     * Returns selected fields from internal query state.
     *
     * @internal only, used for Extbase internal handling and core tests. Don't use it.
     * @return string[]
     */
    public function getSelect(): array
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->select;
    }

    /**
     * Returns from tables from internal query state.
     *
     * @see Typo3DbBackend::getObjectDataByQuery()
     * @see Typo3DbBackend::getObjectCountByQuery()
     * @internal only, used for Extbase internal handling and core tests. Don't use it.
     * @return From[]
     */
    public function getFrom()
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->from;
    }

    /**
     * Returns where expressions from internal query state.
     *
     * @see Typo3DbQueryParserTest
     * @internal only, used for Extbase internal handling and core tests. Don't use it.
     * @return CompositeExpression|string|null
     */
    public function getWhere(): CompositeExpression|string|null
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->where;
    }

    /**
     * Returns having expressions from internal query state.
     *
     * @internal only, used for Extbase internal handling and core tests. Don't use it.
     * @return CompositeExpression|string|null
     */
    public function getHaving(): CompositeExpression|string|null
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->having;
    }

    /**
     * Returns order-by definitions from internal query state.
     *
     * @return string[]
     * @see RelationHandler::readForeignField()
     * @see Typo3DbQueryParserTest
     * @internal only, used for Extbase internal handling and core tests. Don't use it.
     */
    public function getOrderBy(): array
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->orderBy;
    }

    /**
     * Returns selected group-by definitions from internal query state.
     *
     * @see Typo3DbQueryParserTest
     * @return string[]
     * @internal only, used for Extbase internal handling and core tests. Don't use it.
     */
    public function getGroupBy(): array
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->groupBy;
    }

    /**
     * Returns the list of joins, indexed by `from-alias` from the internal query state.
     *
     * @return array<string, Join[]>
     * @internal only, used for Extbase internal handling and core tests. Don't use it.
     */
    public function getJoin(): array
    {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        return $concreteQueryBuilder->join;
    }

    /**
     * Return all tables/aliases used in FROM or JOIN query parts from the query builder.
     *
     * The table names are automatically unquoted. This is a helper for to build the list
     * of queried tables for the AbstractRestrictionContainer.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    protected function getQueriedTables(): array
    {
        /** @var array<non-empty-string, non-empty-string> $queriedTables */
        $queriedTables = [];
        // Loop through all FROM tables
        foreach ($this->concreteQueryBuilder->from as $from) {
            $tableName = $this->unquoteSingleIdentifier($from->table);
            $tableAlias = is_string($from->alias) && $from->alias !== '' ? $this->unquoteSingleIdentifier($from->alias) : $tableName;
            if (!in_array($tableAlias, $this->restrictionsAppliedInJoinCondition, true)) {
                $queriedTables[$tableAlias] = $tableName;
            }
        }

        // Loop through all JOIN tables
        foreach ($this->concreteQueryBuilder->join as $joins) {
            foreach ($joins as $join) {
                $tableName = $this->unquoteSingleIdentifier($join->table);
                $tableAlias = is_string($join->alias) && $join->alias !== '' ? $this->unquoteSingleIdentifier($join->alias) : $tableName;
                if (!in_array($tableAlias, $this->restrictionsAppliedInJoinCondition, true)) {
                    $queriedTables[$tableAlias] = $tableName;
                }
            }
        }
        return $queriedTables;
    }

    /**
     * @param string[] $fields
     * @param string[] $dependsOn
     *
     * @internal not part of public API, experimental and may change at any given time.
     */
    public function typo3_with(
        string $name,
        string|DoctrineQueryBuilder|ConcreteQueryBuilder|QueryBuilder $expression,
        array $fields = [],
        array $dependsOn = [],
    ): self {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->typo3_with($name, $expression, $fields, $dependsOn);
        return $this;
    }

    /**
     * @param string[] $fields
     * @param string[] $dependsOn
     *
     * @internal not part of public API, experimental and may change at any given time.
     */
    public function typo3_addWith(
        string $name,
        string|DoctrineQueryBuilder|ConcreteQueryBuilder|QueryBuilder $expression,
        array $fields = [],
        array $dependsOn = [],
    ): self {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->typo3_addWith($name, $expression, $fields, $dependsOn);
        return $this;
    }

    /**
     * @param string[] $fields
     * @param string[] $dependsOn
     *
     * @internal not part of public API, experimental and may change at any given time.
     */
    public function typo3_withRecursive(
        string $name,
        bool $uniqueRows,
        string|DoctrineQueryBuilder|ConcreteQueryBuilder|QueryBuilder $expression,
        string|DoctrineQueryBuilder|ConcreteQueryBuilder|QueryBuilder $initialExpression,
        array $fields = [],
        array $dependsOn = [],
    ): self {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->typo3_withRecursive($name, $uniqueRows, $expression, $initialExpression, $fields, $dependsOn);
        return $this;
    }

    /**
     * @param string[] $fields
     * @param string[] $dependsOn
     *
     * @internal not part of public API, experimental and may change at any given time.
     */
    public function typo3_addWithRecursive(
        string $name,
        bool $uniqueRows,
        string|DoctrineQueryBuilder|ConcreteQueryBuilder|QueryBuilder $expression,
        string|DoctrineQueryBuilder|ConcreteQueryBuilder|QueryBuilder $initialExpression,
        array $fields = [],
        array $dependsOn = [],
    ): self {
        $concreteQueryBuilder = $this->concreteQueryBuilder;
        $concreteQueryBuilder->typo3_addWithRecursive($name, $uniqueRows, $expression, $initialExpression, $fields, $dependsOn);
        return $this;
    }

    /**
     * Add the additional query conditions returned by the QueryRestrictionBuilder
     * to the current query and return the original set of conditions so that they
     * can be restored after the query has been built/executed.
     */
    protected function addAdditionalWhereConditions(): CompositeExpression|string|null
    {
        $originalWhereConditions = $this->getWhere();
        $expression = $this->restrictionContainer->buildExpression($this->getQueriedTables(), $this->expr());
        // This check would be obsolete, as the composite expression would not add empty expressions anyway
        // But we keep it here to only clone the previous state, in case we really will change it.
        // Once we remove this state preserving functionality, we can remove the count check here
        // and just add the expression to the query builder.
        if ($expression->count() > 0) {
            $this->concreteQueryBuilder->andWhere($expression);
        }
        return $originalWhereConditions;
    }

    private function throwExceptionOnInvalidPreparedStatementParamArrayType(array $types): void
    {
        foreach ($types as $type) {
            $invalidTypeLabel = match ($type) {
                Connection::PARAM_INT_ARRAY => 'PARAM_INT_ARRAY',
                Connection::PARAM_STR_ARRAY => 'PARAM_STR_ARRAY',
                default => false,
            };
            if ($invalidTypeLabel !== false) {
                throw UnsupportedPreparedStatementParameterTypeException::new($invalidTypeLabel);
            }
        }
    }

    private function throwExceptionOnNamedParameterForPreparedStatement(array $params): void
    {
        foreach ($params as $key => $value) {
            if (is_string($key) && !MathUtility::canBeInterpretedAsInteger($key)) {
                throw NamedParameterNotSupportedForPreparedStatementException::new($key);
            }
        }
    }

    /**
     * Binds a set of parameters, some or all of which are typed with a PDO binding type
     * or DBAL mapping type, to a given statement.
     *
     * Cloned from doctrine/dbal connection, as we need to call from external
     * to support and work with prepared statement from QueryBuilder instance
     * directly.
     *
     * This needs to be checked with each doctrine/dbal release raise.
     *
     * @see \Doctrine\DBAL\Connection::bindParameters()
     * @param DriverStatement $stmt
     * @param list<mixed>|array<string, mixed> $params
     * @param array<int, string|ParameterType|Type>|array<string, string|ParameterType|Type> $types
     */
    private function bindTypedValues(DriverStatement $stmt, array $params, array $types): void
    {
        // Check whether parameters are positional or named. Mixing is not allowed.
        $stringType = new StringType();
        if (is_int(key($params))) {
            $bindIndex = 1;

            foreach ($params as $key => $value) {
                $type = (isset($types[$key])) ? $types[$key] : $stringType;
                [$value, $bindingType] = $this->getBindingInfo($value, $type);
                $stmt->bindValue($bindIndex, $value, $bindingType);

                ++$bindIndex;
            }
        } else {
            // Named parameters
            foreach ($params as $name => $value) {
                $type = (isset($types[$name])) ? $types[$name] : $stringType;
                [$value, $bindingType] = $this->getBindingInfo($value, $type);
                $stmt->bindValue($name, $value, $bindingType);
            }
        }
    }

    /**
     * Gets the binding type of given type.
     *
     * Cloned from doctrine/dbal connection, as we need to call from external
     * to support and work with prepared statement from QueryBuilder instance
     * directly.
     *
     * This needs to be checked with each doctrine/dbal release raise.
     *
     * @see \Doctrine\DBAL\Connection::getBindingInfo()
     * @param mixed $value The value to bind.
     * @param string|ParameterType|Type $type The type to bind.
     * @return array{mixed, ParameterType} [0] => the (escaped) value, [1] => the binding type.
     */
    private function getBindingInfo(mixed $value, string|ParameterType|Type $type): array
    {
        if (is_string($type)) {
            $type = Type::getType($type);
        }
        if ($type instanceof Type) {
            $value       = $type->convertToDatabaseValue($value, $this->connection->getDatabasePlatform());
            $bindingType = $type->getBindingType();
        } else {
            $bindingType = $type;
        }
        return [$value, $bindingType];
    }
}
