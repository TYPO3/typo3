<?php
declare (strict_types = 1);
namespace TYPO3\CMS\Core\Database\Query\Expression;

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

use TYPO3\CMS\Core\Database\Connection;

/**
 * ExpressionBuilder class is responsible to dynamically create SQL query parts.
 *
 * It takes care building query conditions while ensuring table and column names
 * are quoted within the created expressions / SQL fragments. It is a facade to
 * the actual Doctrine ExpressionBuilder.
 *
 * The ExpressionBuilder is used within the context of the QueryBuilder to ensure
 * queries are being build based on the requirements of the database platform in
 * use.
 */
class ExpressionBuilder
{
    const EQ = '=';
    const NEQ = '<>';
    const LT = '<';
    const LTE = '<=';
    const GT = '>';
    const GTE = '>=';

    const QUOTE_NOTHING = 0;
    const QUOTE_IDENTIFIER = 1;
    const QUOTE_PARAMETER = 2;

    /**
     * The DBAL Connection.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Initializes a new ExpressionBuilder
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Creates a conjunction of the given boolean expressions
     *
     * @param mixed,... $expressions Optional clause. Requires at least one defined when converting to string.
     *
     * @return CompositeExpression
     */
    public function andX(...$expressions): CompositeExpression
    {
        return new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
    }

    /**
     * Creates a disjunction of the given boolean expressions.
     *
     * @param mixed,... $expressions Optional clause. Requires at least one defined when converting to string.
     *
     * @return CompositeExpression
     */
    public function orX(...$expressions): CompositeExpression
    {
        return new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
    }

    /**
     * Creates a comparison expression.
     *
     * @param mixed $leftExpression The left expression.
     * @param string $operator One of the ExpressionBuilder::* constants.
     * @param mixed $rightExpression The right expression.
     *
     * @return string
     */
    public function comparison($leftExpression, string $operator, $rightExpression): string
    {
        return $leftExpression . ' ' . $operator . ' ' . $rightExpression;
    }

    /**
     * Creates an equality comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param mixed $value The value. No automatic quoting/escaping is done.
     *
     * @return string
     */
    public function eq(string $fieldName, $value): string
    {
        return $this->comparison($this->connection->quoteIdentifier($fieldName), static::EQ, $value);
    }

    /**
     * Creates a non equality comparison expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> <> <right expr>. Example:
     *
     *     [php]
     *     // u.id <> 1
     *     $q->where($q->expr()->neq('u.id', '1'));
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param mixed $value The value. No automatic quoting/escaping is done.
     *
     * @return string
     */
    public function neq(string $fieldName, $value): string
    {
        return $this->comparison($this->connection->quoteIdentifier($fieldName), static::NEQ, $value);
    }

    /**
     * Creates a lower-than comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param mixed $value The value. No automatic quoting/escaping is done.
     *
     * @return string
     */
    public function lt($fieldName, $value): string
    {
        return $this->comparison($this->connection->quoteIdentifier($fieldName), static::LT, $value);
    }

    /**
     * Creates a lower-than-equal comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param mixed $value The value. No automatic quoting/escaping is done.
     *
     * @return string
     */
    public function lte(string $fieldName, $value): string
    {
        return $this->comparison($this->connection->quoteIdentifier($fieldName), static::LTE, $value);
    }

    /**
     * Creates a greater-than comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param mixed $value The value. No automatic quoting/escaping is done.
     *
     * @return string
     */
    public function gt(string $fieldName, $value): string
    {
        return $this->comparison($this->connection->quoteIdentifier($fieldName), static::GT, $value);
    }

    /**
     * Creates a greater-than-equal comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param mixed $value The value. No automatic quoting/escaping is done.
     *
     * @return string
     */
    public function gte(string $fieldName, $value): string
    {
        return $this->comparison($this->connection->quoteIdentifier($fieldName), static::GTE, $value);
    }

    /**
     * Creates an IS NULL expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     *
     * @return string
     */
    public function isNull(string $fieldName): string
    {
        return $this->connection->quoteIdentifier($fieldName) . ' IS NULL';
    }

    /**
     * Creates an IS NOT NULL expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     *
     * @return string
     */
    public function isNotNull(string $fieldName): string
    {
        return $this->connection->quoteIdentifier($fieldName) . ' IS NOT NULL';
    }

    /**
     * Creates a LIKE() comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param mixed $value Argument to be used in LIKE() comparison. No automatic quoting/escaping is done.
     *
     * @return string
     */
    public function like(string $fieldName, $value): string
    {
        return $this->comparison($this->connection->quoteIdentifier($fieldName), 'LIKE', $value);
    }

    /**
     * Creates a NOT LIKE() comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param mixed $value Argument to be used in NOT LIKE() comparison. No automatic quoting/escaping is done.
     *
     * @return string
     */
    public function notLike(string $fieldName, $value): string
    {
        return $this->comparison($this->connection->quoteIdentifier($fieldName), 'NOT LIKE', $value);
    }

    /**
     * Creates a IN () comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param string|array $value The placeholder or the array of values to be used by IN() comparison.
     *                            No automatic quoting/escaping is done.
     *
     * @return string
     */
    public function in(string $fieldName, $value): string
    {
        return $this->comparison(
            $this->connection->quoteIdentifier($fieldName),
            'IN',
            '(' . implode(', ', (array)$value) . ')'
        );
    }

    /**
     * Creates a NOT IN () comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param string|array $value The placeholder or the array of values to be used by NOT IN() comparison.
     *                            No automatic quoting/escaping is done.
     *
     * @return string
     */
    public function notIn(string $fieldName, $value): string
    {
        return $this->comparison(
            $this->connection->quoteIdentifier($fieldName),
            'NOT IN',
            '(' . implode(', ', (array)$value) . ')'
        );
    }

    /**
     * Returns a comparison that can find a value in a list field (CSV).
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param string $value Argument to be used in FIND_IN_SET() comparison. No automatic quoting/escaping is done.
     * @return string
     * @throws \RuntimeException
     */
    public function inSet(string $fieldName, string $value): string
    {
        if ($value === '') {
            throw new \InvalidArgumentException(
                'ExpressionBuilder::inSet() can not be used with an empty string value.',
                1459696089
            );
        }

        if (strpos($value, ',') !== false) {
            throw new \InvalidArgumentException(
                'ExpressionBuilder::inSet() can not be used with values that contain a comma (",").',
                1459696090
            );
        }

        switch ($this->connection->getDatabasePlatform()->getName()) {
            case 'postgresql':
            case 'pdo_postgresql':
                return $this->comparison(
                    sprintf(
                        'any(string_to_array(%s, %s))',
                        $this->connection->quoteIdentifier($fieldName),
                        $this->literal(',')
                    ),
                    self::EQ,
                    $value
                );
                break;
            case 'oci8':
            case 'pdo_oracle':
                throw new \RuntimeException(
                    'FIND_IN_SET support for database platform "Oracle" not yet implemented.',
                    1459696680
                );
                break;
            case 'sqlsrv':
            case 'pdo_sqlsrv':
                throw new \RuntimeException(
                    'FIND_IN_SET support for database platform "SQLServer" not yet implemented.',
                    1459696681
                );
                break;
            default:
                return sprintf(
                    'FIND_IN_SET(%s, %s)',
                    $value,
                    $this->connection->quoteIdentifier($fieldName)
                );
        }
    }

    /**
     * Creates a bitwise AND expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param int $value Argument to be used in the bitwise AND operation
     * @return string
     */
    public function bitAnd(string $fieldName, int $value): string
    {
        switch ($this->connection->getDatabasePlatform()->getName()) {
            case 'oci8':
            case 'pdo_oracle':
                return sprintf(
                    'BITAND(%s, %s)',
                    $this->connection->quoteIdentifier($fieldName),
                    $value
                );
            default:
                return $this->comparison(
                    $this->connection->quoteIdentifier($fieldName),
                    '&',
                    $value
                );
        }
    }

    /**
     * Creates a MIN expression for the given field/alias.
     *
     * @param string $fieldName
     * @param string|null $alias
     * @return string
     */
    public function min(string $fieldName, string $alias = null): string
    {
        return $this->calculation('MIN', $fieldName, $alias);
    }

    /**
     * Creates a MAX expression for the given field/alias.
     *
     * @param string $fieldName
     * @param string|null $alias
     * @return string
     */
    public function max(string $fieldName, string $alias = null): string
    {
        return $this->calculation('MAX', $fieldName, $alias);
    }

    /**
     * Creates a AVG expression for the given field/alias.
     *
     * @param string $fieldName
     * @param string|null $alias
     * @return string
     */
    public function avg(string $fieldName, string $alias = null): string
    {
        return $this->calculation('AVG', $fieldName, $alias);
    }

    /**
     * Creates a SUM expression for the given field/alias.
     *
     * @param string $fieldName
     * @param string|null $alias
     * @return string
     */
    public function sum(string $fieldName, string $alias = null): string
    {
        return $this->calculation('SUM', $fieldName, $alias);
    }

    /**
     * Creates a COUNT expression for the given field/alias.
     *
     * @param string $fieldName
     * @param string|null $alias
     * @return string
     */
    public function count(string $fieldName, string $alias = null): string
    {
        return $this->calculation('COUNT', $fieldName, $alias);
    }

    /**
     * Create a SQL aggregrate function.
     *
     * @param string $aggregateName
     * @param string $fieldName
     * @param string|null $alias
     * @return string
     */
    protected function calculation(string $aggregateName, string $fieldName, string $alias = null): string
    {
        $aggregateSQL = sprintf(
            '%s(%s)',
            $aggregateName,
            $this->connection->quoteIdentifier($fieldName)
        );

        if (!empty($alias)) {
            $aggregateSQL .= ' AS ' . $this->connection->quoteIdentifier($alias);
        }

        return $aggregateSQL;
    }

    /**
     * Quotes a given input parameter.
     *
     * @param mixed $input The parameter to be quoted.
     * @param string|null $type The type of the parameter.
     *
     * @return string
     */
    public function literal($input, string $type = null): string
    {
        return $this->connection->quote($input, $type);
    }
}
