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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform as PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServer2012Platform as SQLServerPlatform;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\LimitToTablesRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Object oriented approach to building SQL queries.
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
class QueryBuilder
{
    /**
     * The DBAL Connection.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * @var \Doctrine\DBAL\Query\QueryBuilder
     */
    protected $concreteQueryBuilder;

    /**
     * @var QueryRestrictionContainerInterface
     */
    protected $restrictionContainer;

    /**
     * @var array
     */
    protected $additionalRestrictions;

    /**
     * List of table aliases which are completely ignored
     * when generating the table restrictions in the where-clause.
     *
     * Aliases added here are part of a LEFT/RIGHT JOIN, having
     * their restrictions applied in the JOIN's ON condition already.
     *
     * @var string[]
     */
    private $restrictionsAppliedInJoinCondition = [];

    /**
     * Initializes a new QueryBuilder.
     *
     * @param Connection $connection The DBAL Connection.
     * @param QueryRestrictionContainerInterface|null $restrictionContainer
     * @param \Doctrine\DBAL\Query\QueryBuilder|null $concreteQueryBuilder
     * @param array|null $additionalRestrictions
     */
    public function __construct(
        Connection $connection,
        QueryRestrictionContainerInterface $restrictionContainer = null,
        \Doctrine\DBAL\Query\QueryBuilder $concreteQueryBuilder = null,
        array $additionalRestrictions = null
    ) {
        $this->connection = $connection;
        $this->additionalRestrictions = $additionalRestrictions ?: $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'] ?? [];
        $this->setRestrictions($restrictionContainer ?: GeneralUtility::makeInstance(DefaultRestrictionContainer::class));
        $this->concreteQueryBuilder = $concreteQueryBuilder ?: GeneralUtility::makeInstance(\Doctrine\DBAL\Query\QueryBuilder::class, $connection);
    }

    /**
     * @return QueryRestrictionContainerInterface
     */
    public function getRestrictions(): QueryRestrictionContainerInterface
    {
        return $this->restrictionContainer;
    }

    /**
     * @param QueryRestrictionContainerInterface $restrictionContainer
     */
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
     *
     * @param array $tableAliases
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
     *
     * @return ExpressionBuilder
     */
    public function expr(): ExpressionBuilder
    {
        return $this->connection->getExpressionBuilder();
    }

    /**
     * Gets the type of the currently built query.
     *
     * @return int
     * @internal
     */
    public function getType(): int
    {
        return $this->concreteQueryBuilder->getType();
    }

    /**
     * Gets the associated DBAL Connection for this query builder.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Gets the state of this query builder instance.
     *
     * @return int Either QueryBuilder::STATE_DIRTY or QueryBuilder::STATE_CLEAN.
     * @internal
     */
    public function getState(): int
    {
        return $this->concreteQueryBuilder->getState();
    }

    /**
     * Gets the concrete implementation of the query builder
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
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
     *
     * @return Statement
     */
    public function prepare(): Statement
    {
        $connection = $this->getConnection();

        $originalWhereConditions = null;
        if ($this->getType() === \Doctrine\DBAL\Query\QueryBuilder::SELECT) {
            $originalWhereConditions = $this->addAdditionalWhereConditions();
        }

        $sql = $this->concreteQueryBuilder->getSQL();
        $params = $this->concreteQueryBuilder->getParameters();
        $types = $this->concreteQueryBuilder->getParameterTypes();
        $this->throwExceptionOnInvalidPreparedStatementParamArrayType($types);
        $this->throwExceptionOnNamedParameterForPreparedStatement($params);
        $statement = $connection->prepare($sql);
        $this->bindTypedValues($statement, $params, $types);

        if ($originalWhereConditions !== null) {
            $this->concreteQueryBuilder->add('where', $originalWhereConditions, false);
        }

        return $statement;
    }

    /**
     * Executes this query using the bound parameters and their types.
     *
     * doctrine/dbal decided to split execute() into executeQuery() and
     * executeStatement() for doctrine/dbal:^3.0, like it was done on
     * connection level already, thus these methods are added to this
     * decorator class also as preparation for extension authors, that
     * they are able to write code which is compatible across two core
     * versions and avoid deprecation warning. Additional this will ease
     * backports without the need to switch between execute() and executeQuery().
     *
     * It is recommended to use directly executeQuery() for 'SELECT' and
     * executeStatement() for 'INSERT', 'UPDATE' and 'DELETE' queries.
     *
     * @return Result|int
     * @throws DBALException
     * @todo Deprecate in v12 along with raise to min doctrine/dbal:^3.2 to align with doctrine/dbal deprecation.
     */
    public function execute()
    {
        if ($this->getType() !== \Doctrine\DBAL\Query\QueryBuilder::SELECT) {
            return $this->executeStatement();
        }

        return $this->executeQuery();
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
     *
     * @throws DBALException
     */
    public function executeQuery(): Result
    {
        // Set additional query restrictions
        $originalWhereConditions = $this->addAdditionalWhereConditions();

        // @todo Call $this->concreteQueryBuilder->executeQuery()
        //        directly with doctrine/dbal:^3.2 raise in v12.
        $return = $this->concreteQueryBuilder->execute();

        // Restore the original query conditions in case the user keeps
        // on modifying the state.
        $this->concreteQueryBuilder->add('where', $originalWhereConditions, false);

        return $return;
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
     *
     * @throws DBALException
     */
    public function executeStatement(): int
    {
        // @todo Call $this->concreteQueryBuilder->executeStatement()
        //        directly with doctrine/dbal:^3.2 raise in v12.
        return $this->concreteQueryBuilder->execute();
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
        if ($this->getType() !== \Doctrine\DBAL\Query\QueryBuilder::SELECT) {
            return $this->concreteQueryBuilder->getSQL();
        }

        // Set additional query restrictions
        $originalWhereConditions = $this->addAdditionalWhereConditions();

        $sql = $this->concreteQueryBuilder->getSQL();

        // Restore the original query conditions in case the user keeps
        // on modifying the state.
        $this->concreteQueryBuilder->add('where', $originalWhereConditions, false);

        return $sql;
    }

    /**
     * Sets a query parameter for the query being constructed.
     *
     * @param string|int $key The parameter position or name.
     * @param mixed $value The parameter value.
     * @param int|null $type One of the Connection::PARAM_* constants.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setParameter($key, $value, int $type = null): QueryBuilder
    {
        $this->concreteQueryBuilder->setParameter($key, $value, $type);

        return $this;
    }

    /**
     * Sets a collection of query parameters for the query being constructed.
     *
     * @param array $params The query parameters to set.
     * @param array $types The query parameters types to set.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setParameters(array $params, array $types = []): QueryBuilder
    {
        $this->concreteQueryBuilder->setParameters($params, $types);

        return $this;
    }

    /**
     * Gets all defined query parameters for the query being constructed indexed by parameter index or name.
     *
     * @return array The currently defined query parameters indexed by parameter index or name.
     */
    public function getParameters(): array
    {
        return $this->concreteQueryBuilder->getParameters();
    }

    /**
     * Gets a (previously set) query parameter of the query being constructed.
     *
     * @param string|int $key The key (index or name) of the bound parameter.
     *
     * @return mixed The value of the bound parameter.
     */
    public function getParameter($key)
    {
        return $this->concreteQueryBuilder->getParameter($key);
    }

    /**
     * Gets all defined query parameter types for the query being constructed indexed by parameter index or name.
     *
     * @return array The currently defined query parameter types indexed by parameter index or name.
     */
    public function getParameterTypes(): array
    {
        return $this->concreteQueryBuilder->getParameterTypes();
    }

    /**
     * Gets a (previously set) query parameter type of the query being constructed.
     *
     * @param string|int $key The key (index or name) of the bound parameter type.
     *
     * @return mixed The value of the bound parameter type.
     */
    public function getParameterType($key)
    {
        return $this->concreteQueryBuilder->getParameterType($key);
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
        $this->concreteQueryBuilder->setFirstResult($firstResult);

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
        return (int)$this->concreteQueryBuilder->getFirstResult();
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param int $maxResults The maximum number of results to retrieve.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setMaxResults(int $maxResults): QueryBuilder
    {
        $this->concreteQueryBuilder->setMaxResults($maxResults);

        return $this;
    }

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns 0 if setMaxResults was not applied to this query builder.
     *
     * @return int The maximum number of results.
     */
    public function getMaxResults(): int
    {
        return (int)$this->concreteQueryBuilder->getMaxResults();
    }

    /**
     * Either appends to or replaces a single, generic query part.
     *
     * The available parts are: 'select', 'from', 'set', 'where',
     * 'groupBy', 'having' and 'orderBy'.
     *
     * @param string $sqlPartName
     * @param string|array $sqlPart
     * @param bool $append
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function add(string $sqlPartName, $sqlPart, bool $append = false): QueryBuilder
    {
        $this->concreteQueryBuilder->add($sqlPartName, $sqlPart, $append);

        return $this;
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
        $countExpr = $this->getConnection()->getDatabasePlatform()->getCountExpression(
            $item === '*' ? $item : $this->quoteIdentifier($item)
        );
        $this->concreteQueryBuilder->select($countExpr);

        return $this;
    }

    /**
     * Specifies items that are to be returned in the query result.
     * Replaces any previously specified selections, if any.
     *
     * @param string ...$selects
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function select(string ...$selects): QueryBuilder
    {
        $this->concreteQueryBuilder->select(...$this->quoteIdentifiersForSelect($selects));

        return $this;
    }

    /**
     * Specifies that this query should be DISTINCT.
     */
    public function distinct(): QueryBuilder
    {
        $this->concreteQueryBuilder->distinct();

        return $this;
    }

    /**
     * Adds an item that is to be returned in the query result.
     *
     * @param string ...$selects
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function addSelect(string ...$selects): QueryBuilder
    {
        $this->concreteQueryBuilder->addSelect(...$this->quoteIdentifiersForSelect($selects));

        return $this;
    }

    /**
     * Specifies items that are to be returned in the query result.
     * Replaces any previously specified selections, if any.
     * This should only be used for literal SQL expressions as no
     * quoting/escaping of any kind will be performed on the items.
     *
     * @param string ...$selects Literal SQL expressions to be selected. Warning: No quoting will be done!
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function selectLiteral(string ...$selects): QueryBuilder
    {
        $this->concreteQueryBuilder->select(...$selects);

        return $this;
    }

    /**
     * Adds an item that is to be returned in the query result. This should
     * only be used for literal SQL expressions as no quoting/escaping of
     * any kind will be performed on the items.
     *
     * @param string ...$selects Literal SQL expressions to be selected.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function addSelectLiteral(string ...$selects): QueryBuilder
    {
        $this->concreteQueryBuilder->addSelect(...$selects);

        return $this;
    }

    /**
     * Turns the query being built into a bulk delete query that ranges over
     * a certain table.
     *
     * @param string $delete The table whose rows are subject to the deletion.
     *                       Will be quoted according to database platform automatically.
     * @param string|null $alias The table alias used in the constructed query.
     *                      Will be quoted according to database platform automatically.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function delete(string $delete, string $alias = null): QueryBuilder
    {
        $this->concreteQueryBuilder->delete(
            $this->quoteIdentifier($delete),
            empty($alias) ? $alias : $this->quoteIdentifier($alias)
        );

        return $this;
    }

    /**
     * Turns the query being built into a bulk update query that ranges over
     * a certain table
     *
     * @param string $update The table whose rows are subject to the update.
     * @param string|null $alias The table alias used in the constructed query.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function update(string $update, string $alias = null): QueryBuilder
    {
        $this->concreteQueryBuilder->update(
            $this->quoteIdentifier($update),
            empty($alias) ? $alias : $this->quoteIdentifier($alias)
        );

        return $this;
    }

    /**
     * Turns the query being built into an insert query that inserts into
     * a certain table
     *
     * @param string $insert The table into which the rows should be inserted.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function insert(string $insert): QueryBuilder
    {
        $this->concreteQueryBuilder->insert($this->quoteIdentifier($insert));

        return $this;
    }

    /**
     * Creates and adds a query root corresponding to the table identified by the
     * given alias, forming a cartesian product with any existing query roots.
     *
     * @param string $from The table. Will be quoted according to database platform automatically.
     * @param string|null $alias The alias of the table. Will be quoted according to database platform automatically.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function from(string $from, string $alias = null): QueryBuilder
    {
        $this->concreteQueryBuilder->from(
            $this->quoteIdentifier($from),
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
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function join(string $fromAlias, string $join, string $alias, string $condition = null): QueryBuilder
    {
        $this->concreteQueryBuilder->innerJoin(
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
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function innerJoin(string $fromAlias, string $join, string $alias, string $condition = null): QueryBuilder
    {
        $this->concreteQueryBuilder->innerJoin(
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
     * @param string|null $condition The condition for the join.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function leftJoin(string $fromAlias, string $join, string $alias, string $condition = null): QueryBuilder
    {
        $condition = $this->expr()->andX(
            $condition,
            $this->restrictionContainer->buildExpression([$alias ?? $join => $join], $this->expr())
        );
        $this->restrictionsAppliedInJoinCondition[] = $alias ?? $join;

        $this->concreteQueryBuilder->leftJoin(
            $this->quoteIdentifier($fromAlias),
            $this->quoteIdentifier($join),
            $this->quoteIdentifier($alias),
            $condition
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
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function rightJoin(string $fromAlias, string $join, string $alias, string $condition = null): QueryBuilder
    {
        $fromTable = $fromAlias;
        // find the table belonging to the $fromAlias, if it's an alias at all
        foreach ($this->getQueryPart('from') ?: [] as $from) {
            if (isset($from['alias']) && $this->unquoteSingleIdentifier($from['alias']) === $fromAlias) {
                $fromTable = $this->unquoteSingleIdentifier($from['table']);
                break;
            }
        }

        $condition = $this->expr()->andX(
            $condition,
            $this->restrictionContainer->buildExpression([$fromAlias => $fromTable], $this->expr())
        );
        $this->restrictionsAppliedInJoinCondition[] = $fromAlias;

        $this->concreteQueryBuilder->rightJoin(
            $this->quoteIdentifier($fromAlias),
            $this->quoteIdentifier($join),
            $this->quoteIdentifier($alias),
            $condition
        );

        return $this;
    }

    /**
     * Sets a new value for a column in a bulk update query.
     *
     * @param string $key The column to set.
     * @param mixed $value The value, expression, placeholder, etc.
     * @param bool $createNamedParameter Automatically create a named parameter for the value
     * @param int $type
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function set(string $key, $value, bool $createNamedParameter = true, int $type = \PDO::PARAM_STR): QueryBuilder
    {
        $this->concreteQueryBuilder->set(
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
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function where(...$predicates): QueryBuilder
    {
        // Doctrine DBAL 3.x requires a non-empty $predicate, however TYPO3 uses static values
        // such as PageRepository->$where_hid_del which could be empty
        $predicates = array_filter($predicates);
        if (empty($predicates)) {
            return $this;
        }
        $this->concreteQueryBuilder->where(...$predicates);

        return $this;
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * conjunction with any previously specified restrictions.
     *
     * @param string|CompositeExpression ...$where The query restrictions.
     *
     * @return QueryBuilder This QueryBuilder instance.
     *
     * @see where()
     */
    public function andWhere(...$where): QueryBuilder
    {
        // Doctrine DBAL 3.x requires a non-empty $predicate, however TYPO3 uses static values
        // such as PageRepository->$where_hid_del which could be empty
        $where = array_filter($where);
        if (empty($where)) {
            return $this;
        }
        $this->concreteQueryBuilder->andWhere(...$where);

        return $this;
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * @param string|CompositeExpression ...$where The WHERE statement.
     *
     * @return QueryBuilder This QueryBuilder instance.
     *
     * @see where()
     */
    public function orWhere(...$where): QueryBuilder
    {
        // Doctrine DBAL 3.x requires a non-empty $predicate, however TYPO3 uses static values
        // such as PageRepository->$where_hid_del which could be empty
        $where = array_filter($where);
        if (empty($where)) {
            return $this;
        }
        $this->concreteQueryBuilder->orWhere(...$where);

        return $this;
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * @param string ...$groupBy The grouping expression.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function groupBy(...$groupBy): QueryBuilder
    {
        $this->concreteQueryBuilder->groupBy(...$this->quoteIdentifiers($groupBy));

        return $this;
    }

    /**
     * Adds a grouping expression to the query.
     *
     * @param string ...$groupBy The grouping expression.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function addGroupBy(...$groupBy): QueryBuilder
    {
        $this->concreteQueryBuilder->addGroupBy(...$this->quoteIdentifiers($groupBy));

        return $this;
    }

    /**
     * Sets a value for a column in an insert query.
     *
     * @param string $column The column into which the value should be inserted.
     * @param mixed $value The value that should be inserted into the column.
     * @param bool $createNamedParameter Automatically create a named parameter for the value
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setValue(string $column, $value, bool $createNamedParameter = true): QueryBuilder
    {
        $this->concreteQueryBuilder->setValue(
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
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function values(array $values, bool $createNamedParameters = true): QueryBuilder
    {
        if ($createNamedParameters === true) {
            foreach ($values as &$value) {
                $value = $this->createNamedParameter($value);
            }
        }

        $this->concreteQueryBuilder->values($this->quoteColumnValuePairs($values));

        return $this;
    }

    /**
     * Specifies a restriction over the groups of the query.
     * Replaces any previous having restrictions, if any.
     *
     * @param mixed ...$having The restriction over the groups.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function having(...$having): QueryBuilder
    {
        $this->concreteQueryBuilder->having(...$having);
        return $this;
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * conjunction with any existing having restrictions.
     *
     * @param mixed ...$having The restriction to append.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function andHaving(...$having): QueryBuilder
    {
        $this->concreteQueryBuilder->andHaving(...$having);

        return $this;
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * disjunction with any existing having restrictions.
     *
     * @param mixed ...$having The restriction to add.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function orHaving(...$having): QueryBuilder
    {
        $this->concreteQueryBuilder->orHaving(...$having);

        return $this;
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string $fieldName The fieldName to order by. Will be quoted according to database platform automatically.
     * @param string|null $order The ordering direction. No automatic quoting/escaping.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function orderBy(string $fieldName, string $order = null): QueryBuilder
    {
        $this->concreteQueryBuilder->orderBy($this->connection->quoteIdentifier($fieldName), $order);

        return $this;
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param string $fieldName The fieldName to order by. Will be quoted according to database platform automatically.
     * @param string|null $order The ordering direction.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function addOrderBy(string $fieldName, string $order = null): QueryBuilder
    {
        $this->concreteQueryBuilder->addOrderBy($this->connection->quoteIdentifier($fieldName), $order);

        return $this;
    }

    /**
     * Gets a query part by its name.
     *
     * @param string $queryPartName
     *
     * @return mixed
     */
    public function getQueryPart(string $queryPartName)
    {
        return $this->concreteQueryBuilder->getQueryPart($queryPartName);
    }

    /**
     * Gets all query parts.
     *
     * @return array
     */
    public function getQueryParts(): array
    {
        return $this->concreteQueryBuilder->getQueryParts();
    }

    /**
     * Resets SQL parts.
     *
     * @param array|null $queryPartNames
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function resetQueryParts(array $queryPartNames = null): QueryBuilder
    {
        $this->concreteQueryBuilder->resetQueryParts($queryPartNames);

        return $this;
    }

    /**
     * Resets a single SQL part.
     *
     * @param string $queryPartName
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function resetQueryPart(string $queryPartName): QueryBuilder
    {
        $this->concreteQueryBuilder->resetQueryPart($queryPartName);

        return $this;
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

    /**
     * Creates a new named parameter and bind the value $value to it.
     *
     * This method provides a shortcut for PDOStatement::bindValue
     * when using prepared statements.
     *
     * The parameter $value specifies the value that you want to bind. If
     * $placeholder is not provided bindValue() will automatically create a
     * placeholder for you. An automatic placeholder will be of the name
     * ':dcValue1', ':dcValue2' etc.
     *
     * @param mixed $value
     * @param int $type
     * @param string|null $placeHolder The name to bind with. The string must start with a colon ':'.
     *
     * @return string the placeholder name used.
     */
    public function createNamedParameter($value, int $type = \PDO::PARAM_STR, string $placeHolder = null): string
    {
        return $this->concreteQueryBuilder->createNamedParameter($value, $type, $placeHolder);
    }

    /**
     * Creates a new positional parameter and bind the given value to it.
     *
     * Attention: If you are using positional parameters with the query builder you have
     * to be very careful to bind all parameters in the order they appear in the SQL
     * statement , otherwise they get bound in the wrong order which can lead to serious
     * bugs in your code.
     *
     * @param mixed $value
     * @param int $type
     *
     * @return string
     */
    public function createPositionalParameter($value, int $type = \PDO::PARAM_STR): string
    {
        return $this->concreteQueryBuilder->createPositionalParameter($value, $type);
    }

    /**
     * Quotes like wildcards for given string value.
     *
     * @param string $value The value to be quoted.
     *
     * @return string The quoted value.
     */
    public function escapeLikeWildcards(string $value): string
    {
        return $this->connection->escapeLikeWildcards($value);
    }

    /**
     * Quotes a given input parameter.
     *
     * @param mixed $input The parameter to be quoted.
     * @param int|null $type The type of the parameter.
     * @return mixed Often string, but also int or float or similar depending on $input and platform
     * @todo: Change signature to quote($input, int $type = \PDO::PARAM_STR) - not nullable anymore, as breaking change in v12.
     */
    public function quote($input, ?int $type = \PDO::PARAM_STR)
    {
        // @todo: drop this line together with signature change in v12
        $type = $type ?? \PDO::PARAM_STR;
        return $this->getConnection()->quote($input, $type);
    }

    /**
     * Quotes a string so it can be safely used as a table or column name, even if
     * it is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * @param string $identifier The name to be quoted.
     *
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
     *
     * @param array $input
     *
     * @return array
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
     * @param array $input
     *
     * @return array
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
     *
     * @param array $input
     *
     * @return array
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
        array_walk($values, static function (&$value) use ($connection) {
            $value = $connection->quote($value, Connection::PARAM_INT);
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
        array_walk($values, static function (&$value) use ($connection) {
            $value = $connection->quote($value, Connection::PARAM_STR);
        });

        return implode(',', $values);
    }

    /**
     * Creates a cast of the $fieldName to a text datatype depending on the database management system.
     *
     * @param string $fieldName The fieldname will be quoted and casted according to database platform automatically
     * @return string
     */
    public function castFieldToTextType(string $fieldName): string
    {
        $databasePlatform = $this->connection->getDatabasePlatform();
        // https://dev.mysql.com/doc/refman/5.7/en/cast-functions.html#function_convert
        if ($databasePlatform instanceof MySqlPlatform) {
            return sprintf('CONVERT(%s, CHAR)', $this->connection->quoteIdentifier($fieldName));
        }
        // https://www.postgresql.org/docs/current/sql-createcast.html
        if ($databasePlatform instanceof PostgreSqlPlatform) {
            return sprintf('%s::text', $this->connection->quoteIdentifier($fieldName));
        }
        // https://www.sqlite.org/lang_expr.html#castexpr
        if ($databasePlatform instanceof SqlitePlatform) {
            return sprintf('CAST(%s as TEXT)', $this->connection->quoteIdentifier($fieldName));
        }
        // https://docs.microsoft.com/en-us/sql/t-sql/functions/cast-and-convert-transact-sql?view=sql-server-ver15#implicit-conversions
        if ($databasePlatform instanceof SQLServerPlatform) {
            return sprintf('CAST(%s as VARCHAR)', $this->connection->quoteIdentifier($fieldName));
        }
        // https://docs.oracle.com/javadb/10.8.3.0/ref/rrefsqlj33562.html
        if ($databasePlatform instanceof OraclePlatform) {
            return sprintf('CAST(%s as VARCHAR)', $this->connection->quoteIdentifier($fieldName));
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
        $platform = $this->getConnection()->getDatabasePlatform();
        if ($platform instanceof SQLServerPlatform) {
            // mssql quotes identifiers with [ and ], not a single character
            $identifier = ltrim($identifier, '[');
            $identifier = rtrim($identifier, ']');
        } else {
            $quoteChar = $platform->getIdentifierQuoteCharacter();
            $identifier = trim($identifier, $quoteChar);
            $identifier = str_replace($quoteChar . $quoteChar, $quoteChar, $identifier);
        }
        return $identifier;
    }

    /**
     * Return all tables/aliases used in FROM or JOIN query parts from the query builder.
     *
     * The table names are automatically unquoted. This is a helper for to build the list
     * of queried tables for the AbstractRestrictionContainer.
     *
     * @return string[]
     */
    protected function getQueriedTables(): array
    {
        $queriedTables = [];

        // Loop through all FROM tables
        foreach ($this->getQueryPart('from') as $from) {
            $tableName = $this->unquoteSingleIdentifier($from['table']);
            $tableAlias = isset($from['alias']) ? $this->unquoteSingleIdentifier($from['alias']) : $tableName;
            if (!in_array($tableAlias, $this->restrictionsAppliedInJoinCondition, true)) {
                $queriedTables[$tableAlias] = $tableName;
            }
        }

        // Loop through all JOIN tables
        foreach ($this->getQueryPart('join') as $fromTable => $joins) {
            foreach ($joins as $join) {
                $tableName = $this->unquoteSingleIdentifier($join['joinTable']);
                $tableAlias = isset($join['joinAlias']) ? $this->unquoteSingleIdentifier($join['joinAlias']) : $tableName;
                if (!in_array($tableAlias, $this->restrictionsAppliedInJoinCondition, true)) {
                    $queriedTables[$tableAlias] = $tableName;
                }
            }
        }

        return $queriedTables;
    }

    /**
     * Add the additional query conditions returned by the QueryRestrictionBuilder
     * to the current query and return the original set of conditions so that they
     * can be restored after the query has been built/executed.
     *
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression|mixed
     */
    protected function addAdditionalWhereConditions()
    {
        $originalWhereConditions = $this->concreteQueryBuilder->getQueryPart('where');
        $expression = $this->restrictionContainer->buildExpression($this->getQueriedTables(), $this->expr());
        // This check would be obsolete, as the composite expression would not add empty expressions anyway
        // But we keep it here to only clone the previous state, in case we really will change it.
        // Once we remove this state preserving functionality, we can remove the count check here
        // and just add the expression to the query builder.
        if ($expression->count() > 0) {
            if ($originalWhereConditions instanceof CompositeExpression) {
                // Save the original query conditions so we can restore
                // them after the query has been built.
                $originalWhereConditions = clone $originalWhereConditions;
            }
            $this->concreteQueryBuilder->andWhere($expression);
        }

        return $originalWhereConditions;
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

    private function throwExceptionOnInvalidPreparedStatementParamArrayType(array $types): void
    {
        $invalidTypeMap = [
            Connection::PARAM_INT_ARRAY => 'PARAM_INT_ARRAY',
            Connection::PARAM_STR_ARRAY => 'PARAM_STR_ARRAY',
        ];
        foreach ($types as $type) {
            if ($invalidTypeMap[$type] ?? false) {
                throw UnsupportedPreparedStatementParameterTypeException::new($invalidTypeMap[$type]);
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
     * @param DriverStatement                                                      $stmt   Prepared statement
     * @param list<mixed>|array<string, mixed>                                     $params Statement parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     */
    private function bindTypedValues(DriverStatement $stmt, array $params, array $types): void
    {
        // Check whether parameters are positional or named. Mixing is not allowed.
        if (is_int(key($params))) {
            $bindIndex = 1;

            foreach ($params as $key => $value) {
                if (isset($types[$key])) {
                    $type                  = $types[$key];
                    [$value, $bindingType] = $this->getBindingInfo($value, $type);
                    $stmt->bindValue($bindIndex, $value, $bindingType);
                } else {
                    $stmt->bindValue($bindIndex, $value);
                }

                ++$bindIndex;
            }
        } else {
            // Named parameters
            foreach ($params as $name => $value) {
                if (isset($types[$name])) {
                    $type                  = $types[$name];
                    [$value, $bindingType] = $this->getBindingInfo($value, $type);
                    $stmt->bindValue($name, $value, $bindingType);
                } else {
                    $stmt->bindValue($name, $value);
                }
            }
        }
    }

    /**
     * Gets the binding type of a given type.
     *
     * Cloned from doctrine/dbal connection, as we need to call from external
     * to support and work with prepared statement from QueryBuilder instance
     * directly.
     *
     * This needs to be checked with each doctrine/dbal release raise.
     *
     * @param mixed                $value The value to bind.
     * @param int|string|Type|null $type  The type to bind (PDO or DBAL).
     *
     * @return array{mixed, int} [0] => the (escaped) value, [1] => the binding type.
     */
    private function getBindingInfo($value, $type): array
    {
        if (is_string($type)) {
            $type = Type::getType($type);
        }

        if ($type instanceof Type) {
            $value       = $type->convertToDatabaseValue($value, $this->getConnection()->getDatabasePlatform());
            $bindingType = $type->getBindingType();
        } else {
            $bindingType = $type ?? ParameterType::STRING;
        }

        return [$value, $bindingType];
    }
}
