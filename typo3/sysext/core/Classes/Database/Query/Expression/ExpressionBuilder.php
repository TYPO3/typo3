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

use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use Doctrine\DBAL\Platforms\TrimMode;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder as DoctrineExpressionBuilder;

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
class ExpressionBuilder extends DoctrineExpressionBuilder
{
    public function __construct(protected readonly DoctrineConnection $connection)
    {
        // parent::__construct() skipped by intention, otherwise the private property
        // nature of the parent constructor will prevent access in extended methods.
    }

    /**
     * Creates a conjunction of the given boolean expressions
     */
    public function and(
        CompositeExpression|\Doctrine\DBAL\Query\Expression\CompositeExpression|string|null ...$expressions,
    ): CompositeExpression {
        return CompositeExpression::and(...$expressions);
    }

    /**
     * Creates a disjunction of the given boolean expressions.
     */
    public function or(CompositeExpression|\Doctrine\DBAL\Query\Expression\CompositeExpression|string|null ...$expressions): CompositeExpression
    {
        return CompositeExpression::or(...$expressions);
    }

    /**
     * Creates a comparison expression.
     *
     * @param mixed $leftExpression The left expression.
     * @param string $operator One of the ExpressionBuilder::* constants.
     * @param mixed $rightExpression The right expression.
     * @todo: Add types to signature - either mixed, or (better) string like doctrine.
     *        Similar for other methods below. Especially have a look at $value below.
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
     * When converted to string, it will generate a <left expr> <> <right expr>. Example::
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
    public function lt(string $fieldName, $value): string
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
    public function like(string $fieldName, mixed $value, ?string $escapeChar = null): string
    {
        $fieldName = $this->connection->quoteIdentifier($fieldName);
        $platform = $this->connection->getDatabasePlatform();
        $escapeChar ??= '\\';
        if ($escapeChar !== null) {
            $escapeChar = $this->connection->quote($escapeChar);
        }
        if ($platform instanceof DoctrinePostgreSQLPlatform) {
            // Use ILIKE to mimic case-insensitive search like most people are trained from MySQL/MariaDB.
            return $this->comparison($fieldName, 'ILIKE', $value);
        }
        // Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
        //       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
        //       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt
        return $this->comparison($fieldName, 'LIKE', $value)
            . ($escapeChar !== null && $escapeChar !== '' ? sprintf(' ESCAPE %s', $escapeChar) : '');
    }

    /**
     * Creates a NOT LIKE() comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param mixed $value Argument to be used in NOT LIKE() comparison. No automatic quoting/escaping is done.
     */
    public function notLike(string $fieldName, mixed $value, ?string $escapeChar = null): string
    {
        $fieldName = $this->connection->quoteIdentifier($fieldName);
        $platform = $this->connection->getDatabasePlatform();
        $escapeChar ??= '\\';
        if ($escapeChar !== null) {
            $escapeChar = $this->connection->quote($escapeChar);
        }
        if ($platform instanceof DoctrinePostgreSQLPlatform) {
            // Use ILIKE to mimic case-insensitive search like most people are trained from MySQL/MariaDB.
            return $this->comparison($fieldName, 'NOT ILIKE', $value);
        }
        // Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
        //       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
        //       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt
        return $this->comparison($fieldName, 'NOT LIKE', $value)
            . ($escapeChar !== null && $escapeChar !== '' ? sprintf(' ESCAPE %s', $escapeChar) : '');
    }

    /**
     * Creates an IN () comparison expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param string|array $value The placeholder or the array of values to be used by IN() comparison.
     *                            No automatic quoting/escaping is done.
     */
    public function in(string $fieldName, $value): string
    {
        if ($value === []) {
            throw new \InvalidArgumentException(
                'ExpressionBuilder::in() can not be used with an empty array value.',
                1701857902
            );
        }
        if ($value === '') {
            throw new \InvalidArgumentException(
                'ExpressionBuilder::in() can not be used with an empty string value.',
                1701857903
            );
        }
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
        if ($value === []) {
            throw new \InvalidArgumentException(
                'ExpressionBuilder::notIn() can not be used with an empty array value.',
                1701857904
            );
        }
        if ($value === '') {
            throw new \InvalidArgumentException(
                'ExpressionBuilder::notIn() can not be used with an empty string value.',
                1701857905
            );
        }
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
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof DoctrinePostgreSQLPlatform) {
            return $this->comparison(
                $isColumn ? $value . '::text' : $this->literal($this->unquoteLiteral($value)),
                self::EQ,
                sprintf(
                    'ANY(string_to_array(%s, %s))',
                    $this->connection->quoteIdentifier($fieldName) . '::text',
                    $this->literal(',')
                )
            );
        }
        if ($platform instanceof DoctrineSQLitePlatform) {
            if (str_starts_with($value, ':') || $value === '?') {
                throw new \InvalidArgumentException(
                    'ExpressionBuilder::inSet() for SQLite can not be used with placeholder arguments.',
                    1476029421
                );
            }
            return sprintf(
                'instr(%s, %s)',
                implode(
                    '||',
                    [
                        $this->literal(','),
                        $this->connection->quoteIdentifier($fieldName),
                        $this->literal(','),
                    ]
                ),
                $isColumn
                    ? implode(
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
        }
        if ($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform) {
            return sprintf(
                'FIND_IN_SET(%s, %s)',
                $value,
                $this->connection->quoteIdentifier($fieldName)
            );
        }
        throw new \RuntimeException(
            sprintf('FIND_IN_SET support for database platform "%s" not yet implemented.', $platform::class),
            1459696680
        );
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
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof DoctrinePostgreSQLPlatform) {
            return $this->comparison(
                $isColumn ? $value . '::text' : $this->literal($this->unquoteLiteral($value)),
                self::NEQ,
                sprintf(
                    'ALL(string_to_array(%s, %s))',
                    $this->connection->quoteIdentifier($fieldName) . '::text',
                    $this->literal(',')
                )
            );
        }
        if ($platform instanceof DoctrineSQLitePlatform) {
            if (str_starts_with($value, ':') || $value === '?') {
                throw new \InvalidArgumentException(
                    'ExpressionBuilder::inSet() for SQLite can not be used with placeholder arguments.',
                    1627573103
                );
            }
            return sprintf(
                'instr(%s, %s) = 0',
                implode(
                    '||',
                    [
                        $this->literal(','),
                        $this->connection->quoteIdentifier($fieldName),
                        $this->literal(','),
                    ]
                ),
                $isColumn
                    ? implode(
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
        }
        if ($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform) {
            return sprintf(
                'NOT FIND_IN_SET(%s, %s)',
                $value,
                $this->connection->quoteIdentifier($fieldName)
            );
        }
        throw new \RuntimeException(
            sprintf('negative FIND_IN_SET support for database platform "%s" not yet implemented.', $platform::class),
            1627573101
        );
    }

    /**
     * Creates a bitwise AND expression with the given arguments.
     *
     * @param string $fieldName The fieldname. Will be quoted according to database platform automatically.
     * @param int $value Argument to be used in the bitwise AND operation
     */
    public function bitAnd(string $fieldName, int $value): string
    {
        return $this->comparison(
            $this->connection->quoteIdentifier($fieldName),
            '&',
            $value
        );
    }

    /**
     * Creates a MIN expression for the given field/alias.
     *
     * @param string|null $alias
     */
    public function min(string $fieldName, ?string $alias = null): string
    {
        return $this->calculation('MIN', $fieldName, $alias);
    }

    /**
     * Creates a MAX expression for the given field/alias.
     *
     * @param string|null $alias
     */
    public function max(string $fieldName, ?string $alias = null): string
    {
        return $this->calculation('MAX', $fieldName, $alias);
    }

    /**
     * Creates an AVG expression for the given field/alias.
     *
     * @param string|null $alias
     */
    public function avg(string $fieldName, ?string $alias = null): string
    {
        return $this->calculation('AVG', $fieldName, $alias);
    }

    /**
     * Creates a SUM expression for the given field/alias.
     *
     * @param string|null $alias
     */
    public function sum(string $fieldName, ?string $alias = null): string
    {
        return $this->calculation('SUM', $fieldName, $alias);
    }

    /**
     * Creates a COUNT expression for the given field/alias.
     *
     * @param string|null $alias
     */
    public function count(string $fieldName, ?string $alias = null): string
    {
        return $this->calculation('COUNT', $fieldName, $alias);
    }

    /**
     * Creates a LENGTH expression for the given field/alias.
     *
     * @param string|null $alias
     */
    public function length(string $fieldName, ?string $alias = null): string
    {
        return $this->calculation('LENGTH', $fieldName, $alias);
    }

    /**
     * Creates an expression to alias a value, field value or sub-expression.
     *
     * **Example:**
     * ```
     * $queryBuilder->selectLiteral(
     *   $queryBuilder->quoteIdentifier('uid'),
     *   $queryBuilder->expr()->as('(1 + 1 + 1)', 'calculated_field'),
     * );
     * ```
     *
     * **Result with MySQL:**
     * ```
     * (1 + 1 + 1) AS `calculated_field`
     * ```
     *
     * @param string $expression Value, identifier or expression which should be aliased
     * @param string $asIdentifier Alias identifier
     * @return string Returns aliased expression
     */
    public function as(string $expression, string $asIdentifier = ''): string
    {
        if (trim($this->trimIdentifierQuotes(trim($expression))) === '') {
            throw new \InvalidArgumentException(
                sprintf('Value or expression must be provided as first argument for "%s"', __METHOD__),
                1709826333
            );
        }
        $asIdentifier = trim($this->trimIdentifierQuotes(trim($this->unquoteLiteral($asIdentifier))));
        if ($asIdentifier !== '') {
            $asIdentifier = ' AS ' . $this->connection->quoteIdentifier($asIdentifier);
        }
        return $expression . $asIdentifier;
    }

    /**
     * Concatenate multiple values or expressions into one string value.
     * No automatic quoting or value casting! Ensure each part evaluates to a valid varchar value!
     *
     * **Example:**
     * ```
     * // Combine value of two fields with a space
     * $concatExpressionAsString = $queryBuilder->expr()->concat(
     *   $queryBuilder->quoteIdentifier('first_name_field'),
     *   $queryBuilder->quote(' '),
     *   $queryBuilder->quoteIdentifier('last_name_field')
     * );
     * ```
     *
     * **Result with MySQL:**
     * ```
     * CONCAT(`first_name_field`, " ", `last_name_field`)
     * ```
     *
     * @param string ...$parts Unquoted value or expression part to concatenated with the other parts
     * @return string Returns the concatenation expression compatible with the database connection platform
     */
    public function concat(string ...$parts): string
    {
        return $this->connection->getDatabasePlatform()->getConcatExpression(...$parts);
    }

    /**
     * Create a `CAST()` statement to cast value or expression to a varchar with a given dynamic max length.
     * MySQL does not support `VARCHAR` as cast type, therefor `CHAR` is used.
     *
     * **Example:**
     * ```
     * $fieldVarcharCastExpression = $queryBuilder->expr()->castVarchar(
     *   $queryBuilder->quote('123'), // integer as string
     *   255,                         // convert to varchar(255) field - dynamic length
     *   'new_field_identifier',
     * );
     * ```
     *
     * **Result with MySQL:**
     * ```
     * CAST("123" AS VARCHAR(255))
     * ```
     *
     * @param string $value Unquoted value or expression, which should be cast
     * @param int $length Dynamic varchar field length
     * @param string $asIdentifier Used to add a field identifier alias (`AS`) if non-empty string (optional)
     * @return string Returns the cast expression compatible for the database platform
     */
    public function castVarchar(string $value, int $length = 255, string $asIdentifier = ''): string
    {
        $platform = $this->connection->getDatabasePlatform();
        $pattern = match (true) {
            $platform instanceof DoctrinePostgreSQLPlatform => '(%s::%s(%s))',
            default => '(CAST(%s AS %s(%s)))'
        };
        $type = match (true) {
            // MariaDB added VARCHAR as alias for CHAR to the CAST function, therefore we
            // need to use CHAR here - albeit this still creates a VARCHAR type as long as
            // length is not ZERO.
            // https://dev.mysql.com/doc/refman/8.0/en/cast-functions.html#function_cast
            $platform instanceof DoctrineMySQLPlatform => 'CHAR',
            default => 'VARCHAR'
        };
        return $this->as(sprintf($pattern, $value, $type, $length), $asIdentifier);
    }

    /**
     * Create a `CAST` statement to cast a value or expression result to signed integer type.
     *
     * Be aware that some database vendors will emit an error if an invalid type has been provided (PostgreSQL),
     * and other silently return valid integer from the string discarding the non-integer part (starting with digits)
     * or silently returning unrelated integer value. Use with care.
     *
     * No automatic quoting or value casting! Ensure each part evaluates to a valid value!
     *
     * **Example:**
     * ```
     * $queryBuilder->expr()->castInt(
     *  '(' . '1 * 10' . ')',
     *  'virtual_field',
     * );
     * ```
     *
     * **Result with MySQL:**
     * ```
     * CAST(('1 * 10') AS INTEGER) AS `virtual_field`
     * ```
     *
     * @param string $value Quoted value or expression result which should be cast to integer type
     * @param string $asIdentifier Optionally add a field identifier alias (`AS`)
     * @return string Returns the integer cast expression compatible with the connection database platform
     */
    public function castInt(string $value, string $asIdentifier = ''): string
    {
        // @todo Consider to add a flag to allow unsigned integer casting, except for PostgresSQL
        //       which does not support unsigned integer type at all.
        $type = 'SIGNED INTEGER';
        $pattern = '(CAST(%s AS %s))';
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof DoctrinePostgreSQLPlatform) {
            $pattern = '%s::%s';
            $type = 'INTEGER';
        }
        return $this->as(sprintf($pattern, $value, $type), $asIdentifier);
    }

    /**
     * Creates a cast for the `$expression` result to a text datatype depending on the database management system.
     *
     * Note that for MySQL/MariaDB the corresponding CHAR/VARCHAR types are used with a length of `16383` reflecting
     * 65554 bytes with `utf8mb4` and working with default `max_packet_size=16KB`. For SQLite and PostgreSQL the text
     * type conversion is used.
     *
     * Main purpose of this expression is to use it in a expression chain to convert non-text values to text in chain
     * with other expressions, for example to {@see self::concat()} multiple values or to ensure the type,  within
     * `UNION/UNION ALL` query parts for example in recursive `Common Table Expressions` parts.
     *
     * This is a replacement for {@see QueryBuilder::castFieldToTextType()} with minor adjustments like enforcing and
     * limiting the size to a fixed variant to be more usable in sensible areas like `Common Table Expressions`.
     *
     * Alternatively the {@see self::castVarchar()} can be used which allows for dynamic length setting per expression
     * call.
     *
     * **Example:**
     * ```
     * $queryBuilder->expr()->castText(
     *    '(' . '1 * 10' . ')',
     *    'virtual_field'
     * );
     * ```
     *
     * **Result with MySQL:**
     * ```
     * CAST((1 * 10) AS CHAR(16383) AS `virtual_field`
     * ```
     *
     * @throws \RuntimeException when used with a unsupported platform.
     */
    public function castText(CompositeExpression|\Stringable|string $expression, string $asIdentifier = ''): string
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof DoctrinePostgreSQLPlatform) {
            return $this->as(sprintf('((%s)::%s)', $expression, 'text'), $asIdentifier);
        }
        if ($platform instanceof DoctrineSQLitePlatform) {
            return $this->as(sprintf('(CAST((%s) AS %s))', $expression, 'TEXT'), $asIdentifier);
        }
        if ($platform instanceof DoctrineMariaDBPlatform) {
            // 16383 is the maximum for a VARCHAR field with `utf8mb4`
            return $this->as(sprintf('(CAST((%s) AS %s(%s)))', $expression, 'VARCHAR', '16383'), $asIdentifier);
        }
        if ($platform instanceof DoctrineMySQLPlatform) {
            // 16383 is the maximum for a VARCHAR field with `utf8mb4`
            return $this->as(sprintf('(CAST((%s) AS %s(%s)))', $expression, 'CHAR', '16383'), $asIdentifier);
        }
        throw new \RuntimeException(
            sprintf(
                '%s is not implemented for the used database platform "%s", yet!',
                __METHOD__,
                get_class($this->connection->getDatabasePlatform())
            ),
            1722105672
        );
    }

    /**
     * Create an SQL aggregate function.
     *
     * @param string|null $alias
     */
    protected function calculation(string $aggregateName, string $fieldName, ?string $alias = null): string
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
     * @param TrimMode $position Either constant out of LEADING, TRAILING, BOTH
     * @param string|null $char Character to be trimmed (defaults to space)
     */
    public function trim(string $fieldName, TrimMode $position = TrimMode::UNSPECIFIED, ?string $char = null): string
    {
        return $this->connection->getDatabasePlatform()->getTrimExpression(
            $this->connection->quoteIdentifier($fieldName),
            $position,
            ($char === null ? null : $this->literal($char))
        );
    }

    /**
     * Create a statement to generate a value repeating defined $value for $numberOfRepeats times.
     * This method can be used to provide the repeat number as a sub-expression or calculation.
     *
     * This method does not quote anything! Ensure proper quoting (value/identifier) for $numberOfRepeats and $value.
     *
     * **Example:**
     * ```
     * $queryBuilder->expr()->repeat(
     *   20,
     *   $queryBuilder->quote('0'),
     *   $queryBuilder->quoteIdentifier('aliased_field'),
     * );
     * ```
     *
     * **Result with MySQL:**
     * ```
     * REPEAT("0", 20) AS `aliased_field`
     * ```
     *
     * @param int|string $numberOfRepeats Statement or value defining how often the $value should be repeated. Proper quoting must be ensured.
     * @param string $value Value which should be repeated. Proper quoting must be ensured
     * @param string $asIdentifier Provide `AS` identifier if not empty
     * @return string Returns the platform compatible statement to create the x-times repeated value
     */
    public function repeat(int|string $numberOfRepeats, string $value, string $asIdentifier = ''): string
    {
        $numberOfRepeats = $this->castInt((string)$numberOfRepeats);
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof DoctrineSQLitePlatform) {
            $pattern = "replace(printf('%%.' || %s || 'c', '/'),'/', %s)";
            return $this->as(
                sprintf($pattern, $numberOfRepeats, $value),
                $asIdentifier
            );
        }
        $pattern = 'REPEAT(%s, %s)';
        return $this->as(
            sprintf($pattern, $value, $numberOfRepeats),
            $asIdentifier,
        );
    }

    /**
     * Create statement containing $numberOfSpaces spaces.
     * This method does not quote anything! Ensure proper quoting (value/identifier) for $numberOfSpaces!
     *
     * **Example:**
     * ```
     * $queryBuilder->expr()->space(
     *   $queryBuilder->expr()->castInt(
     *     $queryBuilder->quoteIdentifier('table_repeat_number_field')
     *   ),
     *   $queryBuilder->quoteIdentifier('aliased_field'),
     * );
     * ```
     *
     * **Result with MySQL:**
     * ```
     * SPACE(CAST(`table_repeat_number_field` AS INTEGER)) AS `aliased_field`
     * ```
     *
     * @param int|string $numberOfSpaces Statement or value defining how often a space should be repeated. Proper quoting must be ensured.
     * @param string $asIdentifier Provide `AS` identifier if not empty
     * @return string Returns the platform compatible statement to create the x-times repeated space(s).
     */
    public function space(int|string $numberOfSpaces, string $asIdentifier = ''): string
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform) {
            // Use `SPACE()` method supported by MySQL and MariaDB
            $pattern = 'SPACE(%s)';
            $numberOfSpaces = $this->castInt((string)$numberOfSpaces);
            return $this->as(
                sprintf($pattern, $numberOfSpaces),
                $asIdentifier,
            );
        }
        // Emulate `SPACE()` by using the `repeat()` expression.
        return $this->repeat($numberOfSpaces, $this->connection->quote(' '), $asIdentifier);
    }

    /**
     * Extract $length character of $value from the right side.
     * $length can be an integer like value or a sub-expression evaluating to an integer value
     * to define the length of the extracted length from the right side.
     * This method does not quote anything! Ensure proper quoting (value/identifier) $length and $value!
     *
     * **Example:**
     * ```
     * $queryBuilder->expr()->left(
     *   $queryBuilder->castInt('(' . '23' . ')'),
     *   $queryBuilder->quoteIdentifier('table_field_name'),
     *   'virtual_field'
     * );
     * ```
     *
     * **Result with MySQL:**
     * ```
     * LEFT(CAST(`table_field_name` AS INTEGER), CAST("23" AS INTEGER)) AS `virtual_field`
     * ```
     *
     * @param int|string $length Integer value or expression providing the length as integer
     * @param string $value Value, identifier or expression defining the value to extract from the left
     * @param string $asIdentifier Provide `AS` identifier if not empty
     * @return string Return the expression to extract defined substring from the right side.
     */
    public function left(int|string $length, string $value, string $asIdentifier = ''): string
    {
        $length = (is_string($length)) ? $this->castInt($length) : (string)$length;
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof DoctrineSQLitePlatform) {
            // SQLite does not support `LEFT()`, use `SUBSTRING()` instead. Weirdly, we need to increment the length by
            // one to get the correct substring length.
            return $this->as(
                $platform->getSubstringExpression($value, '0', $length . ' + 1'),
                $asIdentifier,
            );
        }
        return $this->as(
            sprintf('LEFT(%s, %s)', $value, $length),
            $asIdentifier,
        );
    }

    /**
     * Extract $length character of $value from the right side.
     * $length can be an integer like value or a sub-expression evaluating to an integer value to
     * define the length of the extracted length from the right side.
     * This method does not quote anything! Ensure proper quoting (value/identifier) $length and $value!
     *
     * **Example:**
     * ```
     * $expression5 = $queryBuilder->expr()->right(
     *    6,
     *    $queryBuilder->quote('some-string'),
     *    'calculated_row_field',
     *  );
     * ```
     *
     * **Result with MySQL:**
     * ```
     * RIGHT("some-string", CAST(6 AS INTEGER)) AS `calculated_row_field`
     * ```
     *
     * @param int|string $length Integer value or expression providing the length as integer
     * @param string $value Value, identifier or expression defining the value to extract from the left
     * @param string $asIdentifier Provide `AS` identifier if not empty
     * @return string Return the expression to extract defined substring from the right side
     */
    public function right(int|string $length, string $value, string $asIdentifier = ''): string
    {
        if ($asIdentifier !== '') {
            $asIdentifier = ' AS ' . $this->connection->quoteIdentifier($this->unquoteLiteral($this->trimIdentifierQuotes($asIdentifier)));
        }
        $length = (is_string($length)) ? $this->castInt($length) : (string)$length;
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof DoctrineSQLitePlatform) {
            // SQLite does not support `RIGHT()`, use `SUBSTRING()` instead.
            return $this->connection->getDatabasePlatform()
                ->getSubstringExpression($value, $length . ' * -1') . $asIdentifier;
        }
        return sprintf('RIGHT(%s, %s)', $value, $length) . $asIdentifier;
    }

    /**
     * Left-pad the value or sub-expression result with $paddingValue, to a total length of $length.
     * No automatic quoting or escaping is done, which allows the usage of a sub-expression for $value!
     *
     * **Example:**
     * ```
     * $queryBuilder->expr()->leftPad(
     *   $queryBuilder->quote('123'),
     *   10,
     *   '0',
     *   'padded_value'
     * );
     * ```
     *
     * **Result with MySQL:**
     * ```
     * LPAD("123", CAST("10" AS INTEGER), "0") AS `padded_value`
     * ```
     *
     * @param string $value Value, identifier or expression defining the value which should be left padded
     * @param int|string $length Padded length, to either fill up with $paddingValue on the left side or crop to
     * @param string $paddingValue Padding character used to fill up if characters are missing on the left side
     * @param string $asIdentifier Provide `AS` identifier if not empty
     * @return string Returns database connection platform compatible left-pad expression.
     */
    public function leftPad(string $value, int|string $length, string $paddingValue, string $asIdentifier = ''): string
    {
        if (trim($this->unquoteLiteral($paddingValue), ' ') === '') {
            throw new \InvalidArgumentException(
                sprintf('Empty $paddingValue provided for "%s".', __METHOD__),
                1709658914
            );
        }
        if (strlen(trim($this->unquoteLiteral($paddingValue), ' ')) > 1) {
            throw new \InvalidArgumentException(
                sprintf('Invalid $paddingValue "%s" provided for "%s". Exactly one char allowed.', $paddingValue, __METHOD__),
                1709659006
            );
        }
        // PostgresSQL is really picky about types when calling functions, therefore we ensure that the value or
        // expression result is ensured to be string-typed by casting it to a varchar result for all platforms.
        $value = $this->castVarchar($value);
        $paddingValue = $this->connection->quote($this->unquoteLiteral($paddingValue));
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof DoctrineSQLitePlatform) {
            // SQLite does not support `LPAD()`, therefore we need to build up a generic nested method construct to
            // mimic that method for now. Basically, the length is checked and either the substring up to the length
            // returned OR the substring from the right side up to the length on the concentrated repeated-value with
            // the value to cut of the overlapped prefixed repeated value.
            $repeat = $this->repeat(
                $length,
                $paddingValue
            );
            $pattern = 'IIF(LENGTH(%s) >= %s, %s, %s)';
            return $this->as(sprintf(
                $pattern,
                // Value and length for the length check to consider which part to use.
                $value,
                $this->castInt((string)$length),
                // Return substring with $length from left side to mimic `LPAD()` behaviour of other platforms.
                $platform->getSubstringExpression($value, '0', $this->castInt((string)$length) . ' + 1'),
                // Concatenate `repeat + value` and fetch the substring with length from the right side,
                // so we cut of overlapping prefixed repeat placeholders.
                $this->right($length, $this->concat($repeat, $value))
            ), $asIdentifier);
        }
        return $this->as(sprintf('LPAD(%s, %s, %s)', $value, $this->castInt((string)$length), $paddingValue), $asIdentifier);
    }

    /**
     * Right-pad the value or sub-expression result with $paddingValue, to a total length of $length.
     * No automatic quoting or escaping is done, which allows the usage of a sub-expression for $value!
     *
     * **Example:**
     * ```
     * $queryBuilder->expr()->rightPad(
     *   $queryBuilder->quote('123'),
     *   10,
     *   '0',
     *   'padded_value'
     * );
     * ```
     *
     * **Result with MySQL:**
     * ```
     * RPAD("123", CAST("10" AS INTEGER), "0") AS `padded_value`
     * ```
     *
     * @param string $value Value, identifier or expression defining the value which should be right padded
     * @param int|string $length Value, identifier or expression defining the padding length to fill up or crop
     * @param string $paddingValue Padding character used to fill up if characters are missing on the right side
     * @param string $asIdentifier Provide `AS` identifier if not empty
     * @return string Returns database connection platform compatible right-pad expression
     */
    public function rightPad(string $value, int|string $length, string $paddingValue, string $asIdentifier = ''): string
    {
        if (trim($this->unquoteLiteral($paddingValue), ' ') === '') {
            throw new \InvalidArgumentException(
                sprintf('Empty $paddingValue provided for "%s".', __METHOD__),
                1709664589
            );
        }
        if (strlen(trim($this->unquoteLiteral($paddingValue), ' ')) > 1) {
            throw new \InvalidArgumentException(
                sprintf('Invalid $paddingValue "%s" provided for "%s". Exactly one char allowed.', $paddingValue, __METHOD__),
                1709664598
            );
        }
        // PostgresSQL is really picky about types when calling functions, therefore we ensure that the value or
        // expression result is ensured to be string-type by casting it to a varchar result for all platforms.
        $value = $this->castVarchar($value);
        $paddingValue = $this->connection->quote($this->unquoteLiteral($paddingValue));
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof DoctrineSQLitePlatform) {
            $repeat = $this->repeat(
                $length,
                $paddingValue
            );
            $pattern = 'IIF(LENGTH(%s) >= %s, %s, %s)';
            return $this->as(sprintf(
                $pattern,
                // Value and length for the length check to consider which part to use.
                $value,
                $this->castInt((string)$length),
                // Return substring with $length from left side to mimic `RPAD()` behaviour of other platforms.
                // Note: `RPAD()` cuts the value from the left like LPAD(), which is brain melt. Therefore,
                // this is adopted here to be concise with this behaviour.
                $this->left($length, $value),
                // Concatenate `repeat + value` and fetch the substring with length from the right side, so we
                // cut off overlapping prefixed repeat placeholders.
                $this->left($length, $this->castVarchar($this->concat($value, $this->castVarchar($repeat))))
            ), $asIdentifier);
        }
        return $this->as(sprintf('RPAD(%s, %s, %s)', $value, $this->castInt((string)$length), $paddingValue), $asIdentifier);
    }

    /**
     * Creates IF-THEN-ELSE expression construct compatible with all supported database vendors.
     * No automatic quoting or escaping is done, which allows to build up nested expression statements.
     *
     * **Example:**
     * ```
     * $queryBuilder
     *   ->selectLiteral(
     *     $queryBuilder->expr()->if(
     *       $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
     *       $queryBuilder->quote('page-is-visible'),
     *       $queryBuilder->quote('page-is-not-visible'),
     *       'result_field_name'
     *     ),
     *   )
     *   ->from('pages');
     * ```
     *
     * **Result with MySQL:**
     * ```
     * SELECT (IF(`hidden` = 0, 'page-is-visible', 'page-is-not-visible')) AS `result_feld_name` FROM `pages`
     * ```
     */
    public function if(
        CompositeExpression|\Doctrine\DBAL\Query\Expression\CompositeExpression|\Stringable|string $condition,
        \Stringable|string $truePart,
        \Stringable|string $falsePart,
        \Stringable|string|null $as = null
    ): string {
        $platform = $this->connection->getDatabasePlatform();
        $pattern = match (true) {
            $platform instanceof DoctrineSQLitePlatform => 'IIF(%s, %s, %s)',
            $platform instanceof DoctrinePostgreSQLPlatform => 'CASE WHEN %s THEN %s ELSE %s END',
            $platform instanceof DoctrineMariaDBPlatform,
            $platform instanceof DoctrineMySQLPlatform => 'IF(%s, %s, %s)',
            default => throw new \RuntimeException(
                sprintf('Platform "%s" not supported for "%s"', $platform::class, __METHOD__),
                1721806463
            )
        };
        $expression = sprintf($pattern, $condition, $truePart, $falsePart);
        if ($as !== null) {
            $expression = $this->as(sprintf('(%s)', $expression), $as);
        }
        return $expression;
    }

    /**
     * Quotes a given input parameter.
     *
     * @param string $input The parameter to be quoted.
     */
    public function literal(string $input): string
    {
        return $this->connection->quote($input);
    }

    /**
     * Unquote a string literal. Used to unquote values for internal platform adjustments.
     *
     * @param string $value The value to be unquoted
     * @return string The unquoted value
     */
    protected function unquoteLiteral(string $value): string
    {
        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            $map = [
                "''" => "'",
            ];
            if ($this->connection->getDatabasePlatform() instanceof DoctrineMySQLPlatform) {
                // MySQL needs escaped backslashes for quoted value, which we need to revert in case of unquoting.
                $map['\\\\'] = '\\';
            }
            return str_replace(array_keys($map), array_values($map), substr($value, 1, -1));
        }
        return $value;
    }

    /**
     * Trim all possible identifier quotes from identifier.
     *
     * @see \Doctrine\DBAL\Schema\AbstractAsset::trimQuotes()
     */
    private function trimIdentifierQuotes(string $identifier): string
    {
        return str_replace(['`', '"', '[', ']'], '', $identifier);
    }
}
