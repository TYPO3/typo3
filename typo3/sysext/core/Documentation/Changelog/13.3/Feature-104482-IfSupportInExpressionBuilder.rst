.. include:: /Includes.rst.txt

.. _feature-104482-1721939108:

========================================================
Feature: #104482 - Add if() support to ExpressionBuilder
========================================================

See :issue:`104482`

Description
===========

The TYPO3 :php:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder`
provides a new method to phrase "if-then-else" expressions. Those are translated
into :sql:`IF` or :sql:`CASE` statements depending on the used database engine.

:php:`ExpressionBuilder::if()`
------------------------------

Creates an IF-THEN-ELSE expression.

..  code-block:: php
    :caption: Method signature

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
     * SELECT (IF(`hidden` = 0, 'page-is-visible', 'page-is-not-visible')) AS `result_field_name` FROM `pages`
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

Impact
======

Extension authors can use the new expression method to build more advanced
queries without the requirement to deal with the correct implementation for
all supported database vendors.

..  note::

    No automatic quoting or escaping is done for the condition and true/false
    part. Extension authors need to ensure proper quoting for each part or use
    API calls doing the quoting, for example the TYPO3 CompositeExpression or
    ExpressionBuilder calls.

.. index:: Database, PHP-API, ext:core
