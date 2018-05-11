<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Database\Query;

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

use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * Initializes a new QueryBuilder.
     *
     * @param Connection $connection The DBAL Connection.
     * @param QueryRestrictionContainerInterface $restrictionContainer
     * @param \Doctrine\DBAL\Query\QueryBuilder $concreteQueryBuilder
     * @param array $additionalRestrictions
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
    public function getRestrictions()
    {
        return $this->restrictionContainer;
    }

    /**
     * @param QueryRestrictionContainerInterface $restrictionContainer
     */
    public function setRestrictions(QueryRestrictionContainerInterface $restrictionContainer)
    {
        foreach ($this->additionalRestrictions as $restrictionClass => $options) {
            if (empty($options['disabled'])) {
                $restriction = GeneralUtility::makeInstance($restrictionClass);
                $restrictionContainer->add($restriction);
            }
        }
        $this->restrictionContainer = $restrictionContainer;
    }

    /**
     * Re-apply default restrictions
     */
    public function resetRestrictions()
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
     * Executes this query using the bound parameters and their types.
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function execute()
    {
        if ($this->getType() !== \Doctrine\DBAL\Query\QueryBuilder::SELECT) {
            return $this->concreteQueryBuilder->execute();
        }

        // Set additional query restrictions
        $originalWhereConditions = $this->addAdditionalWhereConditions();

        $result = $this->concreteQueryBuilder->execute();

        // Restore the original query conditions in case the user keeps
        // on modifying the state.
        $this->concreteQueryBuilder->add('where', $originalWhereConditions, false);

        return $result;
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
     * @param string $sqlPart
     * @param bool $append
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function add(string $sqlPartName, string $sqlPart, bool $append = false): QueryBuilder
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
     * @param string[] $selects
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function select(string ...$selects): QueryBuilder
    {
        $this->concreteQueryBuilder->select(...$this->quoteIdentifiersForSelect($selects));

        return $this;
    }

    /**
     * Adds an item that is to be returned in the query result.
     *
     * @param string[] $selects The selection expression.
     *
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
     * @param string[] $selects Literal SQL expressions to be selected. Warning: No quoting will be done!
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
     * @param string[] $selects Literal SQL expressions to be selected.
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
     * @param string $alias The table alias used in the constructed query.
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
     * @param string $alias The table alias used in the constructed query.
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
     * @param string $alias The alias of the table. Will be quoted according to database platform automatically.
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
     * @param string $condition The condition for the join.
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
     * @param string $condition The condition for the join.
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
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function leftJoin(string $fromAlias, string $join, string $alias, string $condition = null): QueryBuilder
    {
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
     * @param string $condition The condition for the join.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function rightJoin(string $fromAlias, string $join, string $alias, string $condition = null): QueryBuilder
    {
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
     * @param string $value The value, expression, placeholder, etc.
     * @param bool $createNamedParameter Automatically create a named parameter for the value
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function set(string $key, $value, bool $createNamedParameter = true): QueryBuilder
    {
        $this->concreteQueryBuilder->set(
            $this->quoteIdentifier($key),
            $createNamedParameter ? $this->createNamedParameter($value) : $value
        );

        return $this;
    }

    /**
     * Specifies one or more restrictions to the query result.
     * Replaces any previously specified restrictions, if any.
     *
     * @param mixed,... $predicates
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function where(...$predicates): QueryBuilder
    {
        $this->concreteQueryBuilder->where(...$predicates);

        return $this;
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * conjunction with any previously specified restrictions.
     *
     * @param mixed,... $where The query restrictions.
     *
     * @return QueryBuilder This QueryBuilder instance.
     *
     * @see where()
     */
    public function andWhere(...$where): QueryBuilder
    {
        $this->concreteQueryBuilder->andWhere(...$where);

        return $this;
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * @param mixed,... $where The WHERE statement.
     *
     * @return QueryBuilder This QueryBuilder instance.
     *
     * @see where()
     */
    public function orWhere(...$where): QueryBuilder
    {
        $this->concreteQueryBuilder->orWhere(...$where);

        return $this;
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * @param mixed,... $groupBy The grouping expression.
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
     * @param mixed,... $groupBy The grouping expression.
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
     * @param string $value The value that should be inserted into the column.
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
     * @param mixed,... $having The restriction over the groups.
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
     * @param mixed,... $having The restriction to append.
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
     * @param mixed,... $having The restriction to add.
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
     * @param string $order The ordering direction. No automatic quoting/escaping.
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
     * @param string $order The ordering direction.
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
    public function resetQueryPart($queryPartName): QueryBuilder
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
     * @param string $placeHolder The name to bind with. The string must start with a colon ':'.
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
        return addcslashes($value, '_%');
    }

    /**
     * Quotes a given input parameter.
     *
     * @param mixed $input The parameter to be quoted.
     * @param int|null $type The type of the parameter.
     *
     * @return mixed Often string, but also int or float or similar depending on $input and platform
     */
    public function quote($input, int $type = null)
    {
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
            list($fieldName, $alias, $suffix) = array_pad(
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
            $queriedTables[$tableAlias] = $tableName;
        }

        // Loop through all JOIN tables
        foreach ($this->getQueryPart('join') as $fromTable => $joins) {
            foreach ($joins as $join) {
                $tableName = $this->unquoteSingleIdentifier($join['joinTable']);
                $tableAlias = isset($join['joinAlias']) ? $this->unquoteSingleIdentifier($join['joinAlias']) : $tableName;
                $queriedTables[$tableAlias] = $tableName;
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
}
