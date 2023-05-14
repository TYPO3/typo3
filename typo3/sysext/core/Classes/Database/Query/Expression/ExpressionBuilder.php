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

namespace TYPO3\CMS\Core\Database\Query\Expression;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\TrimMode;
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
    public const EQ = '=';
    public const NEQ = '<>';
    public const LT = '<';
    public const LTE = '<=';
    public const GT = '>';
    public const GTE = '>=';

    public const QUOTE_NOTHING = 0;
    public const QUOTE_IDENTIFIER = 1;
    public const QUOTE_PARAMETER = 2;

    /**
     * The DBAL Connection.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Initializes a new ExpressionBuilder
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Creates a conjunction of the given boolean expressions
     *
     * @param CompositeExpression|string ...$expressions Optional clause. Requires at least one defined when converting to string.
     *
     * @deprecated since v12, will be removed in v13. Use ExpressionBuilder::and() instead.
     */
    public function andX(...$expressions): CompositeExpression
    {
        trigger_error(
            'ExpressionBuilder::andX() will be removed in TYPO3 v13.0. Use ExpressionBuilder::and() instead.',
            E_USER_DEPRECATED
        );
        return CompositeExpression::and(...$expressions);
    }

    /**
     * Creates a disjunction of the given boolean expressions.
     *
     * @param CompositeExpression|string ...$expressions Optional clause. Requires at least one defined when converting to string.
     *
     * @deprecated since v12, will be removed in v13. Use ExpressionBuilder::or() instead.
     */
    public function orX(...$expressions): CompositeExpression
    {
        trigger_error(
            'ExpressionBuilder::orX() will be removed in TYPO3 v13.0. Use ExpressionBuilder::or() instead.',
            E_USER_DEPRECATED
        );
        return CompositeExpression::or(...$expressions);
    }

    /**
     * Creates a conjunction of the given boolean expressions
     */
    public function and(CompositeExpression|string|null ...$expressions): CompositeExpression
    {
        return CompositeExpression::and(...$expressions);
    }

    /**
     * Creates a disjunction of the given boolean expressions.
     */
    public function or(CompositeExpression|string|null ...$expressions): CompositeExpression
    {
        return CompositeExpression::or(...$expressions);
    }

    /**
     * Creates a comparison expression.
     *
     * @param mixed $leftExpression The left expression.
     * @param string $operator One of the ExpressionBuilder::* constants.
     * @param mixed $rightExpression The right expression.
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
     */
    public function gte(string $fieldName, $value): string
    {
        return $this->comparison($this->connection->quoteIdentifier($fieldName), static::GTE, $value);
    }

    /**
     * Creates an IS NULL expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     */
    public function isNull(string $fieldName): string
    {
        return $this->connection->quoteIdentifier($fieldName) . ' IS NULL';
    }

    /**
     * Creates an IS NOT NULL expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
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
     */
    public function like(string $fieldName, $value): string
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof PostgreSQLPlatform) {
            // Use ILIKE to mimic case-insensitive search like most people are trained from MySQL/MariaDB.
            return $this->comparison($this->connection->quoteIdentifier($fieldName), 'ILIKE', $value);
        }
        // Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
        //       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
        //       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt
        return $this->comparison($this->connection->quoteIdentifier($fieldName), 'LIKE', $value)
            . sprintf(' ESCAPE %s', $this->connection->quote('\\'));
    }

    /**
     * Creates a NOT LIKE() comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param mixed $value Argument to be used in NOT LIKE() comparison. No automatic quoting/escaping is done.
     */
    public function notLike(string $fieldName, $value): string
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof PostgreSQLPlatform) {
            // Use ILIKE to mimic case-insensitive search like most people are trained from MySQL/MariaDB.
            return $this->comparison($this->connection->quoteIdentifier($fieldName), 'NOT ILIKE', $value);
        }
        // Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
        //       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
        //       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt
        return $this->comparison($this->connection->quoteIdentifier($fieldName), 'NOT LIKE', $value)
            . sprintf(' ESCAPE %s', $this->connection->quote('\\'));
    }

    /**
     * Creates a IN () comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param string|array $value The placeholder or the array of values to be used by IN() comparison.
     *                            No automatic quoting/escaping is done.
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
     * @param string $fieldName The field name. Will be quoted according to database platform automatically.
     * @param string $value Argument to be used in FIND_IN_SET() comparison. No automatic quoting/escaping is done.
     * @param bool $isColumn Set when the value to compare is a column on a table to activate casting
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function inSet(string $fieldName, string $value, bool $isColumn = false): string
    {
        if ($value === '') {
            throw new \InvalidArgumentException(
                'ExpressionBuilder::inSet() can not be used with an empty string value.',
                1459696089
            );
        }

        if (str_contains($value, ',')) {
            throw new \InvalidArgumentException(
                'ExpressionBuilder::inSet() can not be used with values that contain a comma (",").',
                1459696090
            );
        }

        switch ($this->connection->getDatabasePlatform()->getName()) {
            case 'postgresql':
            case 'pdo_postgresql':
                return $this->comparison(
                    $isColumn ? $value . '::text' : $this->literal($this->unquoteLiteral((string)$value)),
                    self::EQ,
                    sprintf(
                        'ANY(string_to_array(%s, %s))',
                        $this->connection->quoteIdentifier($fieldName) . '::text',
                        $this->literal(',')
                    )
                );
            case 'oci8':
            case 'pdo_oracle':
                throw new \RuntimeException(
                    'FIND_IN_SET support for database platform "Oracle" not yet implemented.',
                    1459696680
                );
            case 'sqlite':
            case 'sqlite3':
            case 'pdo_sqlite':
                if (str_starts_with($value, ':') || $value === '?') {
                    throw new \InvalidArgumentException(
                        'ExpressionBuilder::inSet() for SQLite can not be used with placeholder arguments.',
                        1476029421
                    );
                }
                $comparison = sprintf(
                    'instr(%s, %s)',
                    implode(
                        '||',
                        [
                            $this->literal(','),
                            $this->connection->quoteIdentifier($fieldName),
                            $this->literal(','),
                        ]
                    ),
                    $isColumn ?
                        implode(
                            '||',
                            [
                                $this->literal(','),
                                // do not explicitly quote value as it is expected to be
                                // quoted by the caller
                                'cast(' . $value . ' as text)',
                                $this->literal(','),
                            ]
                        )
                        : $this->literal(
                            ',' . $this->unquoteLiteral($value) . ','
                        )
                );
                return $comparison;
            default:
                return sprintf(
                    'FIND_IN_SET(%s, %s)',
                    $value,
                    $this->connection->quoteIdentifier($fieldName)
                );
        }
    }

    /**
     * Returns a comparison that can find a value in a list field (CSV) but is negated.
     *
     * @param string $fieldName The field name. Will be quoted according to database platform automatically.
     * @param string $value Argument to be used in FIND_IN_SET() comparison. No automatic quoting/escaping is done.
     * @param bool $isColumn Set when the value to compare is a column on a table to activate casting
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function notInSet(string $fieldName, string $value, bool $isColumn = false): string
    {
        if ($value === '') {
            throw new \InvalidArgumentException(
                'ExpressionBuilder::notInSet() can not be used with an empty string value.',
                1627573099
            );
        }

        if (str_contains($value, ',')) {
            throw new \InvalidArgumentException(
                'ExpressionBuilder::notInSet() can not be used with values that contain a comma (",").',
                1627573100
            );
        }

        switch ($this->connection->getDatabasePlatform()->getName()) {
            case 'postgresql':
            case 'pdo_postgresql':
                return $this->comparison(
                    $isColumn ? $value . '::text' : $this->literal($this->unquoteLiteral((string)$value)),
                    self::NEQ,
                    sprintf(
                        'ALL(string_to_array(%s, %s))',
                        $this->connection->quoteIdentifier($fieldName) . '::text',
                        $this->literal(',')
                    )
                );
            case 'oci8':
            case 'pdo_oracle':
                throw new \RuntimeException(
                    'negative FIND_IN_SET support for database platform "Oracle" not yet implemented.',
                    1627573101
                );
            case 'sqlite':
            case 'sqlite3':
            case 'pdo_sqlite':
                if (str_starts_with($value, ':') || $value === '?') {
                    throw new \InvalidArgumentException(
                        'ExpressionBuilder::inSet() for SQLite can not be used with placeholder arguments.',
                        1627573103
                    );
                }
                $comparison = sprintf(
                    'instr(%s, %s) = 0',
                    implode(
                        '||',
                        [
                            $this->literal(','),
                            $this->connection->quoteIdentifier($fieldName),
                            $this->literal(','),
                        ]
                    ),
                    $isColumn ?
                        implode(
                            '||',
                            [
                                $this->literal(','),
                                // do not explicitly quote value as it is expected to be
                                // quoted by the caller
                                'cast(' . $value . ' as text)',
                                $this->literal(','),
                            ]
                        )
                        : $this->literal(
                            ',' . $this->unquoteLiteral($value) . ','
                        )
                );
                return $comparison;
            default:
                return sprintf(
                    'NOT FIND_IN_SET(%s, %s)',
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
     * @param string|null $alias
     */
    public function min(string $fieldName, string $alias = null): string
    {
        return $this->calculation('MIN', $fieldName, $alias);
    }

    /**
     * Creates a MAX expression for the given field/alias.
     *
     * @param string|null $alias
     */
    public function max(string $fieldName, string $alias = null): string
    {
        return $this->calculation('MAX', $fieldName, $alias);
    }

    /**
     * Creates a AVG expression for the given field/alias.
     *
     * @param string|null $alias
     */
    public function avg(string $fieldName, string $alias = null): string
    {
        return $this->calculation('AVG', $fieldName, $alias);
    }

    /**
     * Creates a SUM expression for the given field/alias.
     *
     * @param string|null $alias
     */
    public function sum(string $fieldName, string $alias = null): string
    {
        return $this->calculation('SUM', $fieldName, $alias);
    }

    /**
     * Creates a COUNT expression for the given field/alias.
     *
     * @param string|null $alias
     */
    public function count(string $fieldName, string $alias = null): string
    {
        return $this->calculation('COUNT', $fieldName, $alias);
    }

    /**
     * Creates a LENGTH expression for the given field/alias.
     *
     * @param string|null $alias
     */
    public function length(string $fieldName, string $alias = null): string
    {
        return $this->calculation('LENGTH', $fieldName, $alias);
    }

    /**
     * Create a SQL aggregate function.
     *
     * @param string|null $alias
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
     * Creates a TRIM expression for the given field.
     *
     * @param string $fieldName Field name to build expression for
     * @param int $position Either constant out of LEADING, TRAILING, BOTH
     * @param string $char Character to be trimmed (defaults to space)
     * @return string
     */
    public function trim(string $fieldName, int $position = TrimMode::UNSPECIFIED, string $char = null)
    {
        return $this->connection->getDatabasePlatform()->getTrimExpression(
            $this->connection->quoteIdentifier($fieldName),
            $position,
            ($char === null ? false : $this->literal($char))
        );
    }

    /**
     * Quotes a given input parameter.
     *
     * @param mixed $input The parameter to be quoted.
     * @param Connection::PARAM_* $type The type of the parameter.
     * @return mixed Often string, but also int or float or similar depending on $input and platform
     */
    public function literal($input, int $type = Connection::PARAM_STR)
    {
        return $this->connection->quote($input, $type);
    }

    /**
     * Unquote a string literal. Used to unquote values for internal platform adjustments.
     *
     * @param string $value The value to be unquoted
     * @return string The unquoted value
     */
    protected function unquoteLiteral(string $value): string
    {
        $quoteChar = $this->connection
            ->getDatabasePlatform()
            ->getStringLiteralQuoteCharacter();

        $isQuoted = str_starts_with($value, $quoteChar) && str_ends_with($value, $quoteChar);

        if ($isQuoted) {
            return str_replace($quoteChar . $quoteChar, $quoteChar, substr($value, 1, -1));
        }

        return $value;
    }
}
