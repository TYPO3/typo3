.. include:: /Includes.rst.txt

.. _feature-103309-1709741435:

===================================================================
Feature: #103309 - Add more expression methods to ExpressionBuilder
===================================================================

See :issue:`103309`

Description
===========

The TYPO3 :php:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder`
provides a relatively conservative set of database query expressions since a
couple of TYPO3 and Doctrine DBAL versions now.

Additional expression methods are now available to build more advanced database
queries that ensure compatibility across supported database vendors.

.. contents::
    :local:


:php:`ExpressionBuilder::as()`
------------------------------

Creates a statement to append a field alias to a value, identifier or sub-expression.

..  note::

    Some :php:`ExpressionBuilder` methods provides a argument to directly add
    the expression alias to reduce some nesting. This method can be used for
    custom expressions and avoids recurring conditional quoting and alias appending.

..  code-block:: php
    :caption: Method signature

    /**
     * @param string $expression Value, identifier or expression which
     *                           should be aliased.
     * @param string $asIdentifier Used to add a field identifier alias
     *                             (`AS`) if non-empty string (optional).
     *
     * @return string   Returns aliased expression.
     */
    public function as(
        string $expression,
        string $asIdentifier = '',
    ): string {}

    // use TYPO3\CMS\Core\Database\Connection;
    // use TYPO3\CMS\Core\Database\ConnectionPool;
    // use TYPO3\CMS\Core\Utility\GeneralUtility;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('some_table');
    $expressionBuilder = $queryBuilder->expr();

    $queryBuilder->selectLiteral(
      $queryBuilder->quoteIdentifier('uid'),
      $expressionBuilder->as('(1 + 1 + 1)', 'calculated_field'),
    );

    $queryBuilder->selectLiteral(
      $queryBuilder->quoteIdentifier('uid'),
      $expressionBuilder->as(
        $expressionBuilder->concat(
            $expressionBuilder->literal('1'),
            $expressionBuilder->literal(' '),
            $expressionBuilder->literal('1'),
        ),
        'concatenated_value'
      ),
    );


:php:`ExpressionBuilder::concat()`
----------------------------------

Can be used to concatenate values, row field values or expression results into
a single string value.

..  note::

    The created expression is built on the proper platform specific and preferred
    concatenation method, for example :sql:`string || string || string || ...`
    for SQLite and :sql:`CONCAT(...string)` for other database vendors.

..  code-block:: php
    :caption: Method signature

    /**
     * @param string ...$parts      Unquoted value or expression parts to
     *                              concatenate with each other
     * @return string  Returns the concatenation expression compatible with
     *                 the database connection platform.
     */
    public function concat(string ...$parts): string {}

..  code-block:: php
    :caption: Usage example

    // use TYPO3\CMS\Core\Database\Connection;
    // use TYPO3\CMS\Core\Database\ConnectionPool;
    // use TYPO3\CMS\Core\Utility\GeneralUtility;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('pages');
    $expressionBuilder = $queryBuilder->expr();
    $result = $queryBuilder
        ->select('uid', 'pid', 'title')
        ->addSelectLiteral(
            $expressionBuilder->concat(
                $queryBuilder->quoteIdentifier('title'),
                $queryBuilder->quote(' - ['),
                $queryBuilder->quoteIdentifier('uid'),
                $queryBuilder->quote('|'),
                $queryBuilder->quoteIdentifier('pid'),
                $queryBuilder->quote(']'),
            ) . ' AS ' . $queryBuilder->quoteIdentifier('page_title_info')
        )
        ->where(
            $expressionBuilder->eq(
                'pid',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ),
        )
        ->executeQuery();

    while ($row = $result->fetchAssociative()) {
        // $row = array{
        //  'uid' => 1,
        //  'pid' => 0,
        //  'title' => 'Site Root Page',
        //  'page_title_info' => 'Site Root Page - [1|0]',
        // }
    }

..  warning::

    Be aware to properly quote values, identifiers and sub-expressions.
    No automatic quoting will be applied.

:php:`ExpressionBuilder::castVarchar()`
---------------------------------------

Can be used to create an expression which converts a value, row field value or
the result of an expression to varchar type with dynamic length.

..  note::

    Use the platform specific preferred way for casting to dynamic length
    character type, which means :sql:`CAST("value" AS VARCHAR(<LENGTH>))`
    or :sql:`CAST("value" AS CHAR(<LENGTH>))` is used, except PostgreSQL.
    For PostgreSQL the :sql:`"value"::INTEGER` cast notation is used.

..  code-block:: php
    :caption: Method signature

    /**
     * @param string    $value          Unquoted value or expression,
     *                                  which should be casted.
     * @param int       $length         Dynamic varchar field length.
     * @param string    $asIdentifier   Used to add a field identifier alias
     *                                  (`AS`) if non-empty string (optional).
     * @return string   Returns the cast expression compatible for the database platform.
     */
    public function castVarchar(
        string $value,
        int $length = 255,
        string $asIdentifier = '',
    ): string {}

..  code-block:: php
    :caption: Usage example

    // use TYPO3\CMS\Core\Database\ConnectionPool;
    // use TYPO3\CMS\Core\Utility\GeneralUtility;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('some_table');

    $fieldVarcharCastExpression = $queryBuilder->expr()->castVarchar(
        $queryBuilder->quote('123'), // integer as string
        255,                         // convert to varchar(255) field - dynamic length
        'new_field_identifier',
    );

    $fieldExpressionCastExpression = $queryBuilder->expr()->castVarchar(
        '(100 + 200)',           // calculate a integer value
        100,                     // dynamic varchar(100) field
        'new_field_identifier',
    );

..  warning::

    Be aware to properly quote values, identifiers and sub-expressions.
    No automatic quoting will be applied.

:php:`ExpressionBuilder::castInt()`
-----------------------------------

Can be used to create an expression which converts a value, row field value or
the result of an expression to signed integer type.

..  note::

    Use the platform specific preferred way for casting to dynamic length
    character type, which means :sql:`CAST("value" AS INTEGER)` for most database vendors
    except PostgreSQL. For PostgreSQL the :sql:`"value"::INTEGER` cast notation
    is used.

..  code-block:: php
    :caption: Method signature

    /**
     * @param string    $value         Quoted value or expression result which
     *                                 should be casted to integer type.
     * @param string    $asIdentifier  Used to add a field identifier alias
     *                                 (`AS`) if non-empty string (optional).
     * @return string   Returns the integer cast expression compatible with the
     *                  connection database platform.
     */
    public function castInt(string $value, string $asIdentifier = ''): string {}

..  code-block:: php
    :caption: Usage example

    // use TYPO3\CMS\Core\Database\ConnectionPool;
    // use TYPO3\CMS\Core\Utility\GeneralUtility;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('pages');
    $queryBuilder
        ->select('uid')
        ->from('pages');

    // simple value (quoted) to be used as sub-expression
    $expression1 = $queryBuilder->expr()->castInt(
        $queryBuilder->quote('123'),
    );

    // simple value (quoted) to return as select field
    $queryBuilder->addSelectLiteral(
        $queryBuilder->expr()->castInt(
            $queryBuilder->quote('123'),
            'virtual_field',
        ),
    );

    $expression3 = queryBuilder->expr()->castInt(
      $queryBuilder->quoteIdentifier('uid'),
    );

    // expression to be used as sub-expression
    $expression4 = $queryBuilder->expr()->castInt(
        $queryBuilder->expr()->castVarchar('(1 * 10)'),
    );

    // expression to return as select field
    $queryBuilder->addSelectLiteral(
        $queryBuilder->expr()->castInt(
            $queryBuilder->expr()->castVarchar('(1 * 10)'),
            'virtual_field',
        ),
    );

..  warning::

    Be aware to properly quote values, identifiers and sub-expressions.
    No automatic quoting will be applied.

:php:`ExpressionBuilder::repeat()`
----------------------------------

Create a statement to generate a value repeating defined :php:`$value` for
:php:`$numberOfRepeats` times. This method can be used to provide the
repeat number as a sub-expression or calculation.

..  note::

    :sql:`REPEAT(string, number)` is used to build this expression for all database
    vendors except SQLite for which the compatible replacement construct expression
    :sql:`REPLACE(PRINTF('%.' || <valueOrStatement> || 'c', '/'),'/', <repeatValue>)`
    is used, based on :sql:`REPLACE()` and the built-in :sql:`printf()`.

..  code-block:: php
    :caption: Method signature

    /**
     * @param int|string    $numberOfRepeats    Statement or value defining
     *                                          how often the $value should
     *                                          be repeated. Proper quoting
     *                                          must be ensured.
     * @param string        $value              Value which should be repeated.
     *                                          Proper quoting must be ensured.
     * @param string        $asIdentifier       Provide `AS` identifier if not
     *                                          empty.
     * @return string   Returns the platform compatible statement to create the
     *                  x-times repeated value.
     */
    public function repeat(
        int|string $numberOfRepeats,
        string $value,
        string $asIdentifier = '',
    ): string {}

..  code-block:: php
    :caption: Usage example

    // use TYPO3\CMS\Core\Database\ConnectionPool;
    // use TYPO3\CMS\Core\Utility\GeneralUtility;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('some_table');

    $expression1 = $queryBuilder->expr()->repeat(
        10,
        $queryBuilder->quote('.'),
    );

    $expression2 = $queryBuilder->expr()->repeat(
        20,
        $queryBuilder->quote('0'),
        $queryBuilder->quoteIdentifier('aliased_field'),
    );

    $expression3 = $queryBuilder->expr()->repeat(
        20,
        $queryBuilder->quoteIdentifier('table_field'),
        $queryBuilder->quoteIdentifier('aliased_field'),
    );

    $expression4 = $queryBuilder->expr()->repeat(
        $queryBuilder->expr()->castInt(
            $queryBuilder->quoteIdentifier('repeat_count_field')
        ),
        $queryBuilder->quoteIdentifier('table_field'),
        $queryBuilder->quoteIdentifier('aliased_field'),
    );

    $expression5 = $queryBuilder->expr()->repeat(
        '(7 + 3)',
        $queryBuilder->quote('.'),
    );

    $expression6 = $queryBuilder->expr()->repeat(
      '(7 + 3)',
      $queryBuilder->concat(
        $queryBuilder->quote(''),
        $queryBuilder->quote('.'),
        $queryBuilder->quote(''),
      ),
      'virtual_field_name',
    );

..  warning::

    Be aware to properly quote values, identifiers and sub-expressions.
    No automatic quoting will be applied.

:php:`ExpressionBuilder::space()`
---------------------------------

Create statement containing :php:`$numberOfSpaces` spaces.

..  note::

    The :sql:`SPACE(number)` expression is used for MariaDB and MySQL and
    :php:`ExpressionBuilder::repeat()` expression as fallback for PostgreSQL
    and SQLite.

..  code-block:: php
    :caption: Method signature

    /**
     * @param int|string    $numberOfSpaces Expression or value defining how
     *                                      many spaces should be created.
     * @param string        $asIdentifier   Provide result as identifier field
     *                                      (AS), not added if empty string.
     * @return string   Returns the platform compatible statement to create the
     *                  x-times repeated space(s).
     */
    public function space(
        string $numberOfSpaces,
        string $asIdentifier = '',
    ): string {}

..  code-block:: php
    :caption: Usage example

    // use TYPO3\CMS\Core\Database\ConnectionPool;
    // use TYPO3\CMS\Core\Utility\GeneralUtility;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('some_table');

    $expression1 = $queryBuilder->expr()->space(
        '10'
    );

    $expression2 = $queryBuilder->expr()->space(
        '20',
        $queryBuilder->quoteIdentifier('aliased_field'),
    );

    $expression3 = $queryBuilder->expr()->space(
        '(210)'
    );

    $expression3 = $queryBuilder->expr()->space(
        '(210)',
        $queryBuilder->quoteIdentifier('aliased_field'),
    );

    $expression5 = $queryBuilder->expr()->space(
        $queryBuilder->expr()->castInt(
            $queryBuilder->quoteIdentifier('table_repeat_number_field'),
        ),
    );

    $expression6 = $queryBuilder->expr()->space(
        $queryBuilder->expr()->castInt(
            $queryBuilder->quoteIdentifier('table_repeat_number_field'),
        ),
        $queryBuilder->quoteIdentifier('aliased_field'),
    );

..  warning::

    Be aware to properly quote values, identifiers and sub-expressions.
    No automatic quoting will be applied.

:php:`ExpressionBuilder::left()`
--------------------------------

Extract :php:`$length` character of :php:`$value` from the left side.

..  note::

    Creates a :sql:`LEFT(string, number_of_chars)` expression for all supported
    database vendors except SQLite, where :sql:`substring(string, integer[, integer])`
    is used to provide a compatible expression.

..  code-block:: php
    :caption: Method signature

    /**
     * @param int|string    $length         Integer value or expression
     *                                      providing the length as integer.
     * @param string        $value          Value, identifier or expression
     *                                      defining the value to extract from
     *                                      the left.
     * @param string        $asIdentifier   Provide `AS` identifier if not empty.
     * @return string   Return the expression to extract defined substring
     *                  from the right side.
     */
    public function left(
        int|string $length,
        string $value,
        string $asIdentifier = '',
    ): string {}

..  code-block:: php
    :caption: Usage example

    // use TYPO3\CMS\Core\Database\ConnectionPool;
    // use TYPO3\CMS\Core\Utility\GeneralUtility;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('some_table');

    $expression1 = $queryBuilder->expr()->left(
        6,
        $queryBuilder->quote('some-string'),
    );

    $expression2 = $queryBuilder->expr()->left(
        '6',
        $queryBuilder->quote('some-string'),
    );

    $expression3 = $queryBuilder->expr()->left(
        $queryBuilder->castInt('(23)'),
        $queryBuilder->quote('some-string'),
    );

    $expression4 = $queryBuilder->expr()->left(
        $queryBuilder->castInt('(23)'),
        $queryBuilder->quoteIdentifier('table_field_name'),
    );

..  tip::

    For other sub string operations, :php:`\Doctrine\DBAL\Platforms\AbstractPlatform::getSubstringExpression()`
    can be used. Synopsis: :php:`getSubstringExpression(string $string, string $start, ?string $length = null): string`.

:php:`ExpressionBuilder::right()`
---------------------------------

Extract :php:`$length` character of :php:`$value` from the right side.

..  note::

    Creates a :sql:`RIGHT(string, number_of_chars)` expression for all supported
    database vendors except SQLite, where :sql:`substring(string, integer[, integer])`
    is used to provide a compatible expression.

..  code-block:: php
    :caption: Method signature

    /**
     * @param int|string    $length         Integer value or expression
     *                                      providing the length as integer.
     * @param string        $value          Value, identifier or expression
     *                                      defining the value to extract from
     *                                      the right.
     * @param string        $asIdentifier   Provide `AS` identifier if not empty.
     *
     * @return string   Return the expression to extract defined substring
     *                  from the right side.
     */
    public function right(
        int|string $length,
        string $value,
        string $asIdentifier = '',
    ): string {}

..  code-block:: php
    :caption: Usage example

    // use TYPO3\CMS\Core\Database\ConnectionPool;
    // use TYPO3\CMS\Core\Utility\GeneralUtility;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('some_table');

    $expression1 = $queryBuilder->expr()->right(
        6,
        $queryBuilder->quote('some-string'),
    );

    $expression2 = $queryBuilder->expr()->right(
        '6',
        $queryBuilder->quote('some-string'),
    );

    $expression3 = $queryBuilder->expr()->right(
        $queryBuilder->castInt('(23)'),
        $queryBuilder->quote('some-string'),
    );

    $expression4 = $queryBuilder->expr()->right(
        $queryBuilder->castInt('(23)'),
        $queryBuilder->quoteIdentifier('table_field_name'),
    );

..  warning::

    Be aware to properly quote values, identifiers and sub-expressions.
    No automatic quoting will be applied.

:php:`ExpressionBuilder::leftPad()`
-----------------------------------

Left-pad the value or sub-expression result with $paddingValue, to a total
length of $length.

..  note::

    SQLite does not support :sql:`LPAD(string, integer, string)`, therefore a
    more complex compatible replacement expression construct is created.

..  code-block:: php
    :caption: Method signature

    /**
     * @param string        $value          Value, identifier or expression
     *                                      defining the value which should
     *                                      be left padded.
     * @param int|string    $length         Value, identifier or expression
     *                                      defining the padding length to
     *                                      fill up on the left or crop.
     * @param string        $paddingValue   Padding character used to fill
     *                                      up if characters are missing on
     *                                      the left side.
     * @param string        $asIdentifier   Used to add a field identifier alias
     *                                      (`AS`) if non-empty string (optional).
     * @return string   Returns database connection platform compatible
     *                  left-pad expression.
     */
    public function leftPad(
        string $value,
        int|string $length,
        string $paddingValue,
        string $asIdentifier = '',
    ): string {}

..  code-block:: php
    :caption: Usage example

    // use TYPO3\CMS\Core\Database\ConnectionPool;
    // use TYPO3\CMS\Core\Utility\GeneralUtility;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('some_table');

    $expression1 = $queryBuilder->expr()->leftPad(
        $queryBuilder->quote('123'),
        10,
        '0',
    );

    $expression2 = $queryBuilder->expr()->leftPad(
        $queryBuilder->expr()->castVarchar($queryBuilder->quoteIdentifier('uid')),
        10,
        '0',
    );

    $expression3 = $queryBuilder->expr()->leftPad(
        $queryBuilder->expr()->concat(
            $queryBuilder->quote('1'),
            $queryBuilder->quote('2'),
            $queryBuilder->quote('3'),
        ),
        10,
        '0',
    );

    $expression4 = $queryBuilder->expr()->leftPad(
        $queryBuilder->castVarchar('( 1123 )'),
        10,
        '0',
    );

    $expression5 = $queryBuilder->expr()->leftPad(
        $queryBuilder->castVarchar('( 1123 )'),
        10,
        '0',
        'virtual_field',
    );

..  warning::

    Be aware to properly quote values, identifiers and sub-expressions.
    No automatic quoting will be applied.

:php:`ExpressionBuilder::rightPad()`
------------------------------------

Right-pad the value or sub-expression result with :php:`$paddingValue`, to a
total length of :php:`$length`.

..  note::

    SQLite does not support :sql:`RPAD(string, integer, string)`, therefore a
    complexer compatible replacement expression construct is created.

..  code-block:: php
    :caption: Method signature

    /**
     * @param string        $value          Value, identifier or expression
     *                                      defining the value which should be
     *                                      right padded.
     * @param int|string    $length         Value, identifier or expression
     *                                      defining the padding length to
     *                                      fill up on the right or crop.
     * @param string        $paddingValue   Padding character used to fill up
     *                                      if characters are missing on the
     *                                      right side.
     * @param string        $asIdentifier   Used to add a field identifier alias
     *                                      (`AS`) if non-empty string (optional).
     * @return string   Returns database connection platform compatible
     *                  right-pad expression.
     */
    public function rightPad(
        string $value,
        int|string $length,
        string $paddingValue,
        string $asIdentifier = '',
    ): string {}

..  code-block:: php
    :caption: Usage example

    // use TYPO3\CMS\Core\Database\ConnectionPool;
    // use TYPO3\CMS\Core\Utility\GeneralUtility;
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('some_table');

    $expression1 = $queryBuilder->expr()->rightPad(
        $queryBuilder->quote('123'),
        10,
        '0',
    );

    $expression2 = $queryBuilder->expr()->rightPad(
        $queryBuilder->expr()->castVarchar($queryBuilder->quoteIdentifier('uid')),
        10,
        '0',
     );

    $expression3 = $queryBuilder->expr()->rightPad(
        $queryBuilder->expr()->concat(
            $queryBuilder->quote('1'),
            $queryBuilder->quote('2'),
            $queryBuilder->quote('3'),
        ),
        10,
        '0',
    );

    $expression4 = $queryBuilder->expr()->rightPad(
        $queryBuilder->castVarchar('( 1123 )'),
        10,
        '0',
    );

    $expression5 = $queryBuilder->expr()->rightPad(
        $queryBuilder->quote('123'),
        10,
        '0',
        'virtual_field',
    );

..  warning::

    Be aware to properly quote values, identifiers and sub-expressions.
    No automatic quoting will be applied.


Impact
======

Extension authors can use the new expression methods to build more advanced
queries without the requirement to deal with the correct implementation for
all supported database vendors.

.. index:: Database, PHP-API, ext:core
