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

use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform as DoctrineMySQL80Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\ForUpdate;
use Doctrine\DBAL\Query\ForUpdate\ConflictResolutionMode;
use Doctrine\DBAL\Query\From;
use Doctrine\DBAL\Query\Join;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\DBAL\Query\QueryException;
use Doctrine\DBAL\Query\QueryType;
use Doctrine\DBAL\Query\Union;
use Doctrine\DBAL\Query\UnionType;
use TYPO3\CMS\Core\Database\Connection;

/**
 * QueryBuilder class is responsible to dynamically create SQL queries.
 *
 * Important: Verify that every feature you use will work with your database vendor.
 * SQL Query Builder does not attempt to validate the generated SQL at all.
 *
 * The query builder does no validation whatsoever if certain features even work with the
 * underlying database vendor. Limit queries and joins are NOT applied to UPDATE and DELETE statements
 * even if some vendors such as MySQL support it.
 *
 * @internal not part of public core API. Uses as intermediate decorator wrapper to keep track of state, which is
 *           considered internal and therefore by Doctrine DBAL but TYPO3 requires internal access.
 */
class ConcreteQueryBuilder extends DoctrineQueryBuilder
{
    /**
     * The complete SQL string for this query.
     */
    protected ?string $sql = null;

    /**
     * The type of query this is. Can be select, update or delete.
     */
    protected QueryType $type = QueryType::SELECT;

    /**
     * The SELECT parts of the query.
     *
     * @var string[]
     */
    protected array $select = [];

    /**
     * Whether this is a SELECT DISTINCT query.
     */
    protected bool $distinct = false;

    /**
     * The FROM parts of a SELECT query.
     *
     * @var From[]
     */
    protected array $from = [];

    protected ?ForUpdate $forUpdate = null;

    /**
     * The list of joins, indexed by from alias.
     *
     * @var array<string, Join[]>
     */
    protected array $join = [];

    /**
     * The WHERE part of a SELECT, UPDATE or DELETE query.
     */
    protected string|CompositeExpression|null $where = null;

    /**
     * The GROUP BY part of a SELECT query.
     *
     * @var string[]
     */
    protected array $groupBy = [];

    /**
     * The HAVING part of a SELECT query.
     */
    protected string|CompositeExpression|null $having = null;

    /**
     * The ORDER BY parts of a SELECT query.
     *
     * @var string[]
     */
    protected array $orderBy = [];

    /**
     * The WITH query parts.
     */
    protected WithCollection $typo3_with;

    /**
     * The QueryBuilder for the union parts.
     *
     * @var Union[]
     */
    protected array $typo3_unionParts = [];

    /**
     * Initializes a new <tt>QueryBuilder</tt>.
     *
     * @param Connection $connection The DBAL Connection.
     */
    public function __construct(protected readonly Connection $connection)
    {
        parent::__construct($this->connection);
        $this->typo3_with = new WithCollection();
    }

    /**
     * Deep clone of all expression objects in the SQL parts.
     */
    public function __clone()
    {
        parent::__clone();
        foreach ($this->from as $key => $from) {
            $this->from[$key] = clone $from;
        }
        foreach ($this->join as $fromAlias => $joins) {
            foreach ($joins as $key => $join) {
                $this->join[$fromAlias][$key] = clone $join;
            }
        }
        if (is_object($this->where)) {
            $this->where = clone $this->where;
        }
        if (is_object($this->having)) {
            $this->having = clone $this->having;
        }
    }

    /**
     * Specifies union parts to be used to build a UNION query.
     * Replaces any previously specified parts.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->union('SELECT 1 AS field1', 'SELECT 2 AS field1');
     * </code>
     *
     * @return $this
     */
    public function union(string|ConcreteQueryBuilder|DoctrineQueryBuilder $part): self
    {
        parent::union($part);
        $this->type = QueryType::UNION;
        $this->typo3_unionParts = [new Union($part)];
        return $this;
    }

    /**
     * Add parts to be used to build a UNION query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->union('SELECT 1 AS field1')
     *         ->addUnion('SELECT 2 AS field1', 'SELECT 3 AS field1')
     * </code>
     *
     * @return $this
     */
    public function addUnion(string|ConcreteQueryBuilder|DoctrineQueryBuilder $part, UnionType $type = UnionType::DISTINCT): self
    {
        parent::addUnion($part, $type);
        $this->type = QueryType::UNION;
        $this->typo3_unionParts[] = new Union($part, $type);
        return $this;
    }

    /**
     * Specifies an item that is to be returned in the query result.
     * Replaces any previously specified selections, if any.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id', 'p.id')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'p', 'u.id = p.user_id');
     * </code>
     *
     * @param string ...$expressions The selection expressions.
     *
     * @return $this This QueryBuilder instance.
     */
    public function select(string ...$expressions): self
    {
        parent::select(...$expressions);
        $this->type = QueryType::SELECT;
        if (count($expressions) < 1) {
            return $this;
        }
        $this->select = $expressions;
        $this->sql = null;
        return $this;
    }

    /**
     * Adds or removes DISTINCT to/from the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id')
     *         ->distinct()
     *         ->from('users', 'u')
     * </code>
     *
     * @return $this This QueryBuilder instance.
     */
    public function distinct(bool $distinct = true): self
    {
        parent::distinct($distinct);
        $this->distinct = $distinct;
        $this->sql = null;
        return $this;
    }

    /**
     * Adds an item that is to be returned in the query result.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id')
     *         ->addSelect('p.id')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'u.id = p.user_id');
     * </code>
     *
     * @param string $expression     The selection expression.
     * @param string ...$expressions Additional selection expressions.
     *
     * @return $this This QueryBuilder instance.
     */
    public function addSelect(string $expression, string ...$expressions): self
    {
        parent::addSelect($expression, ...$expressions);
        $this->type = QueryType::SELECT;
        $this->select = array_merge($this->select, [$expression], $expressions);
        $this->sql = null;
        return $this;
    }

    /**
     * Turns the query being built into a bulk delete query that ranges over
     * a certain table.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->delete('users', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter(':user_id', 1);
     * </code>
     *
     * @param string $table The table whose rows are subject to the deletion.
     *
     * @return $this This QueryBuilder instance.
     */
    public function delete(string $table): self
    {
        parent::delete($table);
        $this->type = QueryType::DELETE;
        $this->sql = null;
        return $this;
    }

    /**
     * Turns the query being built into a bulk update query that ranges over
     * a certain table
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->update('counters', 'c')
     *         ->set('c.value', 'c.value + 1')
     *         ->where('c.id = ?');
     * </code>
     *
     * @param string $table The table whose rows are subject to the update.
     *
     * @return $this This QueryBuilder instance.
     */
    public function update(string $table): self
    {
        parent::update($table);
        $this->type = QueryType::UPDATE;
        $this->sql = null;
        return $this;
    }

    /**
     * Turns the query being built into an insert query that inserts into
     * a certain table
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->insert('users')
     *         ->values(
     *             array(
     *                 'name' => '?',
     *                 'password' => '?'
     *             )
     *         );
     * </code>
     *
     * @param string $table The table into which the rows should be inserted.
     *
     * @return $this This QueryBuilder instance.
     */
    public function insert(string $table): self
    {
        parent::insert($table);
        $this->type = QueryType::INSERT;
        $this->sql = null;
        return $this;
    }

    /**
     * Creates and adds a query root corresponding to the table identified by the
     * given alias, forming a cartesian product with any existing query roots.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.id')
     *         ->from('users', 'u')
     * </code>
     *
     * @param string      $table The table.
     * @param string|null $alias The alias of the table.
     *
     * @return $this This QueryBuilder instance.
     */
    public function from(string $table, ?string $alias = null): self
    {
        parent::from($table, $alias);
        $this->from[] = new From($table, $alias);
        $this->sql = null;
        return $this;
    }

    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->innerJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This QueryBuilder instance.
     */
    public function innerJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self
    {
        parent::innerJoin($fromAlias, $join, $alias, $condition);
        $this->join[$fromAlias][] = Join::inner($join, $alias, $condition);
        $this->sql = null;
        return $this;
    }

    /**
     * Creates and adds a left join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->leftJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This QueryBuilder instance.
     */
    public function leftJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self
    {
        parent::leftJoin($fromAlias, $join, $alias, $condition);
        $this->join[$fromAlias][] = Join::left($join, $alias, $condition);
        $this->sql = null;
        return $this;
    }

    /**
     * Creates and adds a right join to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->rightJoin('u', 'phonenumbers', 'p', 'p.is_primary = 1');
     * </code>
     *
     * @param string $fromAlias The alias that points to a from clause.
     * @param string $join      The table name to join.
     * @param string $alias     The alias of the join table.
     * @param string $condition The condition for the join.
     *
     * @return $this This QueryBuilder instance.
     */
    public function rightJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self
    {
        parent::rightJoin($fromAlias, $join, $alias, $condition);
        $this->join[$fromAlias][] = Join::right($join, $alias, $condition);
        $this->sql = null;
        return $this;
    }

    /**
     * Specifies one or more restrictions to the query result.
     * Replaces any previously specified restrictions, if any.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('c.value')
     *         ->from('counters', 'c')
     *         ->where('c.id = ?');
     *
     *     // You can optionally programmatically build and/or expressions
     *     $qb = $conn->createQueryBuilder();
     *
     *     $or = $qb->expr()->orx();
     *     $or->add($qb->expr()->eq('c.id', 1));
     *     $or->add($qb->expr()->eq('c.id', 2));
     *
     *     $qb->update('counters', 'c')
     *         ->set('c.value', 'c.value + 1')
     *         ->where($or);
     * </code>
     *
     * @param string|CompositeExpression $predicate     The WHERE clause predicate.
     * @param string|CompositeExpression ...$predicates Additional WHERE clause predicates.
     *
     * @return $this This QueryBuilder instance.
     */
    public function where(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        $where = $this->where = $this->createPredicate($predicate, ...$predicates);
        parent::where($where);
        $this->sql = null;
        return $this;
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * conjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.username LIKE ?')
     *         ->andWhere('u.is_active = 1');
     * </code>
     *
     * @see where()
     *
     * @param string|CompositeExpression $predicate     The predicate to append.
     * @param string|CompositeExpression ...$predicates Additional predicates to append.
     *
     * @return $this This QueryBuilder instance.
     */
    public function andWhere(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        $where = $this->where = $this->appendToPredicate(
            $this->where,
            CompositeExpression::TYPE_AND,
            $predicate,
            ...$predicates,
        );
        $this->where($where);
        return $this;
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->where('u.id = 1')
     *         ->orWhere('u.id = 2');
     * </code>
     *
     * @see where()
     *
     * @param string|CompositeExpression $predicate     The predicate to append.
     * @param string|CompositeExpression ...$predicates Additional predicates to append.
     *
     * @return $this This QueryBuilder instance.
     */
    public function orWhere(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        $where = $this->where = $this->appendToPredicate($this->where, CompositeExpression::TYPE_OR, $predicate, ...$predicates);
        $this->where($where);
        return $this;
    }

    /**
     * Specifies one or more grouping expressions over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->groupBy('u.id');
     * </code>
     *
     * @param string $expression     The grouping expression
     * @param string ...$expressions Additional grouping expressions
     *
     * @return $this This QueryBuilder instance.
     */
    public function groupBy(string $expression, string ...$expressions): self
    {
        $groupBy = $this->groupBy = array_merge([$expression], $expressions);
        parent::groupBy(...$groupBy);
        $this->sql = null;
        return $this;
    }

    /**
     * Adds one or more grouping expressions to the query.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u.name')
     *         ->from('users', 'u')
     *         ->groupBy('u.lastLogin')
     *         ->addGroupBy('u.createdAt');
     * </code>
     *
     * @param string $expression     The grouping expression
     * @param string ...$expressions Additional grouping expressions
     *
     * @return $this This QueryBuilder instance.
     */
    public function addGroupBy(string $expression, string ...$expressions): self
    {
        $groupBy = $this->groupBy = array_merge($this->groupBy, [$expression], $expressions);
        $this->groupBy(...$groupBy);
        return $this;
    }

    /**
     * Specifies a restriction over the groups of the query.
     * Replaces any previous having restrictions, if any.
     *
     * @param string|CompositeExpression $predicate     The HAVING clause predicate.
     * @param string|CompositeExpression ...$predicates Additional HAVING clause predicates.
     *
     * @return $this This QueryBuilder instance.
     */
    public function having(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        $having = $this->having = $this->createPredicate($predicate, ...$predicates);
        parent::having($having);
        $this->sql = null;
        return $this;
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * conjunction with any existing having restrictions.
     *
     * @param string|CompositeExpression $predicate     The predicate to append.
     * @param string|CompositeExpression ...$predicates Additional predicates to append.
     *
     * @return $this This QueryBuilder instance.
     */
    public function andHaving(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        $having = $this->having = $this->appendToPredicate(
            $this->having,
            CompositeExpression::TYPE_AND,
            $predicate,
            ...$predicates,
        );
        $this->having($having);
        return $this;
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * disjunction with any existing having restrictions.
     *
     * @param string|CompositeExpression $predicate     The predicate to append.
     * @param string|CompositeExpression ...$predicates Additional predicates to append.
     *
     * @return $this This QueryBuilder instance.
     */
    public function orHaving(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        $having = $this->having = $this->appendToPredicate(
            $this->having,
            CompositeExpression::TYPE_OR,
            $predicate,
            ...$predicates,
        );
        $this->having($having);
        return $this;
    }

    /**
     * Creates a CompositeExpression from one or more predicates combined by the AND logic.
     */
    private function createPredicate(
        string|CompositeExpression $predicate,
        string|CompositeExpression ...$predicates,
    ): string|CompositeExpression {
        if (count($predicates) === 0) {
            return $predicate;
        }
        $predicates = array_filter($predicates, static fn(CompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        return new CompositeExpression(CompositeExpression::TYPE_AND, $predicate, ...$predicates);
    }

    /**
     * Appends the given predicates combined by the given type of logic to the current predicate.
     */
    private function appendToPredicate(
        string|CompositeExpression|null $currentPredicate,
        string $type,
        string|CompositeExpression ...$predicates,
    ): string|CompositeExpression {
        $predicates = array_filter($predicates, static fn(CompositeExpression|string|null $value): bool => !self::isEmptyPart($value));
        if ($currentPredicate instanceof CompositeExpression && $currentPredicate->getType() === $type) {
            return $currentPredicate->with(...$predicates);
        }
        if ($currentPredicate !== null) {
            array_unshift($predicates, $currentPredicate);
        } elseif (count($predicates) === 1) {
            return $predicates[0];
        }
        return new CompositeExpression($type, ...$predicates);
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return $this This QueryBuilder instance.
     */
    public function orderBy(string $sort, ?string $order = null): self
    {
        parent::orderBy($sort, $order);
        $orderBy = $sort;
        if ($order !== null) {
            $orderBy .= ' ' . $order;
        }
        $this->orderBy = [$orderBy];
        $this->sql = null;
        return $this;
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param string $sort  The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return $this This QueryBuilder instance.
     */
    public function addOrderBy(string $sort, ?string $order = null): self
    {
        parent::addOrderBy($sort, $order);
        $orderBy = $sort;
        if ($order !== null) {
            $orderBy .= ' ' . $order;
        }
        $this->orderBy[] = $orderBy;
        $this->sql = null;
        return $this;
    }

    /**
     * Resets the WHERE conditions for the query.
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetWhere(): self
    {
        parent::resetWhere();
        $this->where = null;
        $this->sql = null;
        return $this;
    }

    /**
     * Resets the grouping for the query.
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetGroupBy(): self
    {
        parent::resetGroupBy();
        $this->groupBy = [];
        $this->sql = null;
        return $this;
    }

    /**
     * Resets the HAVING conditions for the query.
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetHaving(): self
    {
        $this->resetHaving();
        $this->having = null;
        $this->sql = null;
        return $this;
    }

    /**
     * Resets the ordering for the query.
     *
     * @return $this This QueryBuilder instance.
     */
    public function resetOrderBy(): self
    {
        parent::resetOrderBy();
        $this->orderBy = [];
        $this->sql = '';
        return $this;
    }

    public function setMaxResults(?int $maxResults): ConcreteQueryBuilder
    {
        parent::setMaxResults($maxResults);
        $this->sql = null;
        return $this;
    }

    public function setFirstResult(int $firstResult): ConcreteQueryBuilder
    {
        parent::setFirstResult($firstResult);
        $this->sql = null;
        return $this;
    }

    public function forUpdate(ConflictResolutionMode $conflictResolutionMode = ConflictResolutionMode::ORDINARY): DoctrineQueryBuilder
    {
        parent::forUpdate($conflictResolutionMode);
        $this->forUpdate = new ForUpdate($conflictResolutionMode);

        $this->sql = null;

        return $this;
    }

    public function getSQL(): string
    {
        if ($this->typo3_with->isEmpty()) {
            return parent::getSQL();
        }
        return $this->sql ??= $this->prependWith(parent::getSQL());
    }

    //##################################################################################################################
    // Below are added methods not originated from Doctrine DBAL QueryBuilder
    //##################################################################################################################

    /**
     * @param string[] $fields
     * @param string[] $dependsOn
     *
     * @internal not part of public API, experimental and may change at any given time.
     */
    public function typo3_with(
        string $name,
        string|QueryBuilder $expression,
        array $fields = [],
        array $dependsOn = [],
    ): self {
        $this->typo3_with->set(new With($name, $fields, $dependsOn, $expression, false));

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
        string|QueryBuilder $expression,
        array $fields = [],
        array $dependsOn = [],
    ): self {
        $this->typo3_with->add(new With($name, $fields, $dependsOn, $expression, false));

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
        string|QueryBuilder $initialExpression,
        string|QueryBuilder $recursiveExpression,
        array $fields = [],
        array $dependsOn = [],
    ): self {
        // @todo Switch to UNION QueryBuilder once https://review.typo3.org/c/Packages/TYPO3.CMS/+/83943 has been added.
        $unionPattern = ($uniqueRows ? '%s UNION %s' : '%s UNION ALL %s');
        $unionExpression = sprintf($unionPattern, $initialExpression, $recursiveExpression);
        $this->typo3_with->set(new With($name, $fields, $dependsOn, $unionExpression, true));

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
        string|QueryBuilder $initialExpression,
        string|QueryBuilder $recursiveExpression,
        array $fields = [],
        array $dependsOn = [],
    ): self {
        // @todo Switch to UNION QueryBuilder once https://review.typo3.org/c/Packages/TYPO3.CMS/+/83943 has been added.
        $unionPattern = ($uniqueRows ? '%s UNION %s' : '%s UNION ALL %s');
        $unionExpression = sprintf($unionPattern, $initialExpression, $recursiveExpression);
        $this->typo3_with->add(new With($name, $fields, $dependsOn, $unionExpression, true));

        return $this;
    }

    /**
     * Determine if a query part used for where or having is empty. Used as array_filter in ConcreteQueryBuilder
     * methods. This is needed to avoid invalid sql syntax by empty parts, which can happen to relaxed custom
     * CompositeExpression handling.
     *
     * For example used to avoid : (uid = 1) and () and (pid = 2).
     *
     * @see ConcreteQueryBuilder::createPredicate()
     * @see ConcreteQueryBuilder::appendToPredicate()
     * @see \TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression::isEmptyPart()
     *
     * @param CompositeExpression|string|null $value
     * @return bool
     */
    protected static function isEmptyPart(CompositeExpression|string|null $value): bool
    {
        return $value === null
            || ($value instanceof CompositeExpression && $value->count() === 0)
            || trim((string)$value, '() ') === ''
        ;
    }

    /**
     * @todo Should be handled in {@see AbstractPlatform} class hierarchy directly in doctrine directly if support gets
     *       accepted or handled internally here to avoid the force to extend and replace platform classes.
     */
    private function supportsCommonTableExpressions(): bool
    {
        $platform = $this->connection->getDatabasePlatform();
        return $platform instanceof DoctrineMariaDBPlatform
            || $platform instanceof DoctrineMySQL80Platform
            || $platform instanceof DoctrineSQLitePlatform
            || $platform instanceof DoctrinePostgreSQLPlatform
        ;
    }

    private function prependWith(string $sql): string
    {
        if (!$this->typo3_with->isEmpty() && !$this->supportsCommonTableExpressions()) {
            throw new QueryException(
                'WITH not supported for current connection.',
                1717762530,
            );
        }
        return $this->typo3_with . $sql;
    }
}
