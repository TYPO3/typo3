.. include:: /Includes.rst.txt

==============================================================================
Feature: #79262 - Add possibility to create TRIM expression with Doctrine DBAL
==============================================================================

See :issue:`79262`

Description
===========

The possibility to create TRIM expressions using Doctrine DBAL has been integrated.
However, when using this in comparisons, ExpressionBuilder::comparison() has to be
invoked explicitly - otherwise the created TRIM expression would be quoted if e.g.
used with ExpressionBuilder::eq().

.. code-block:: php

    $queryBuilder->expr()->comparison(
        $queryBuilder->expr()->trim($fieldName),
        ExpressionBuilder::EQ,
        $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
    );

The call to :php:`$queryBuilder->expr()-trim()` can be one of the following:

* :php:`trim('fieldName')`
  results in :sql:`TRIM("tableName"."fieldName")`

* :php:`trim('fieldName', AbstractPlatform::TRIM_LEADING, 'x')`
  results in :sql:`TRIM(LEADING "x" FROM "tableName"."fieldName")`

* :php:`trim('fieldName', AbstractPlatform::TRIM_TRAILING, 'x')`
  results in :sql:`TRIM(TRAILING "x" FROM "tableName"."fieldName")`

* :php:`trim('fieldName', AbstractPlatform::TRIM_BOTH, 'x')`
  results in :sql:`TRIM(BOTH "x" FROM "tableName"."fieldName")`

.. index:: Database, PHP-API
