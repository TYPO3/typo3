.. include:: /Includes.rst.txt

.. _feature-104493-1722127314:

=============================================================================
Feature: #104493 - Add `castText()` expression support to `ExpressionBuilder`
=============================================================================

See :issue:`104493`

Description
===========

The TYPO3 :php:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder`
provides a new method to cast expression results to text like datatypes. This
is done to large :sql:`VARCHAR/CHAR` types using the :sql:`CAST/CONVERT` or similar
methods based on the used database engine.

..  note::

    This should not be mixed with :sql:`TEXT`, :sql:`CHAR` or :sql:`VARCHAR`
    data types for column (fields) definition used to describe the structure
    of a table.

`ExpressionBuilder::castText()`
-------------------------------

Creates a :sql:`CAST` expression.

..  code-block:: php
    :caption: Method signature

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

Impact
======

Extension authors can use the new expression method to build more advanced
queries without the requirement to deal with the correct implementation
for all supported database vendors - at least to some grade.

.. index:: Database, PHP-API, ext:core
