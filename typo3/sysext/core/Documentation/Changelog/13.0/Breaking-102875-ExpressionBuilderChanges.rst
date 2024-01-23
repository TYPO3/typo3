.. include:: /Includes.rst.txt

.. _breaking-102875-1706013339:

=============================================
Breaking: #102875 - ExpressionBuilder changes
=============================================

See :issue:`102875`

Description
===========

Signature changes for following methods
---------------------------------------

*  :php:`ExpressionBuilder::literal(string $value)`: Value must be a string now.
*  :php:`ExpressionBuilder::trim()`: Only :php:`\Doctrine\DBAL\Platforms\TrimMode`
   enum for :php:`$position` argument.

Following class constants have been removed
-------------------------------------------

*   :php:`QUOTE_NOTHING`: Not used since already TYPO3 v12 and Doctrine DBAL 3.x.
*   :php:`QUOTE_IDENTIFIER`: Not used since already TYPO3 v12 and Doctrine DBAL 3.x.
*   :php:`QUOTE_PARAMETER`: Not used since already TYPO3 v12 and Doctrine DBAL 3.x.

Impact
======

Calling any of the mentioned methods with invalid type will result in a PHP
error.

Affected installations
======================

Only those installations that uses one of the mentioned methods with invalid type(s).

Migration
=========

:php:`ExpressionBuilder::literal()`
-----------------------------------

Extension author need to ensure that a string is passed to :php:`literal()`.

:php:`ExpressionBuilder::trim()`
--------------------------------

Extension author need to pass the Doctrine DBAL enum :php:`TrimMode` instead of
an integer.

TRIM_LEADING

..  csv-table:: Replacements
    :header: "integer", "enum"

    0, "TrimMode::UNSPECIFIED"
    1, "TrimMode::LEADING"
    2, "TrimMode::TRAILING"
    3, "TrimMode::BOTH"


..  code-block:: php
    :caption: EXT:my_extension/Classes/Domain/Repository/MyTableRepository.php

    use Doctrine\DBAL\Platforms\TrimMode;
    use TYPO3\CMS\Core\Database\Connection
    use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;

    // before
    $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
    $queryBuilder->expr()->comparison(
        $queryBuilder->expr()->trim($fieldName, 1),
        ExpressionBuilder::EQ,
        $queryBuilder->createNamedParameter('', Connection::PARAM_STR)
    );

    // after
    $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
    $queryBuilder->expr()->comparison(
        $queryBuilder->expr()->trim($fieldName, TrimMode::LEADING),
        ExpressionBuilder::EQ,
        $queryBuilder->createNamedParameter('', Connection::PARAM_STR)
    );

    // example for dual version compatible code
    $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
    $queryBuilder->expr()->comparison(
        $queryBuilder->expr()->trim($fieldName, TrimMode::LEADING),
        ExpressionBuilder::EQ,
        $queryBuilder->createNamedParameter('', Connection::PARAM_STR)
    );

..  tip::

    With Doctrine DBAL 3.x the :php:`TrimMode` was a class with class constants. Using
    these no code changes are needed for TYPO3 v12 and v13 compatible code. Only
    method call type hinting needs to be adjusted to use the enum instead of
    int.

.. index:: Database, PHP-API, NotScanned, ext:core
