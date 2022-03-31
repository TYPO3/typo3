.. include:: /Includes.rst.txt

============================================================
Feature: #84115 - Doctrine DBAL - notInSet() for expressions
============================================================

See :issue:`84115`

Description
===========

TYPO3's Database Abstraction Layer supports a wide range of
cross-RDBMS-functionality to limit SELECT statements via
the ExpressionBuilder.

When using :php:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder`
for comma-separated lists, the call :php:`inSet()` can be used to detect database rows
which include a value in a comma-separated list, such as
:sql:`pages.fe_group` where the UIDs of allowed frontend user groups
are stored.

The method :php:`notInSet()` has been added to TYPO3's DBAL ExpressionBuilder,
which works as the opposite functionality:
"Get all rows where a certain value is NOT in the list of comma-separated values".


Impact
======

It is now possible to use :php:`notInSet()` via Doctrine DBAL
Expression Builder for SQLite, MySQL/MariaDB, PostgreSQL and MSSQL Backends.

Example:

.. code-block:: php

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
    $result = $queryBuilder
        ->select('*')
        ->from('fe_users')
        ->where(
            $queryBuilder->expr()->notInSet('usergroup', '5')
        )
        ->execute();


This queries all frontend users which do not directly belong
to usergroup of with uid "5".

Please note that this functionality is for extension authors
and their usage should be thought-through properly, as queries such as "Show me all results where the usergroup has NO access to" isn't a use-case for `notInSet()`.

.. index:: Database, ext:core
