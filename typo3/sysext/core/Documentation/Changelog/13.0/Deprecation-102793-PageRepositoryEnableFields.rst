.. include:: /Includes.rst.txt

.. _deprecation-102793-1704798252:

===================================================
Deprecation: #102793 - PageRepository->enableFields
===================================================

See :issue:`102793`

Description
===========

One of the common PHP APIs used in TYPO3 Core for fetching records is
:php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository`. The method
:php:`enableFields()` is used to enhance a database query with additional
restrictions such as filtering out versioned records from workspaces, hidden
database entries or scheduled database entries.

This method has been marked as deprecated in favor of a new method
:php:`getDefaultConstraints()`.

Impact
======

Calling the method will trigger a PHP deprecation warning.

Affected installations
======================

TYPO3 installations with custom extensions using the method :php:`enableFields()`
of the :php:`PageRepository` class.


Migration
=========

A new method called :php:`getDefaultConstraints()` has been introduced
which supersedes the old method. The new method returns an array of
:php:`CompositeExpression` objects, which can be used instead.

Before (TYPO3 v12)
------------------

.. code-block:: php
   :emphasize-lines: 5,10

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable($tableName);

    $constraints = GeneralUtility::makeInstance(PageRepository::class)
        ->enableFields($tableName);

    $queryBuilder
        ->select('*')
        ->from($tableName);
        ->where(QueryHelper::stripLogicalOperatorPrefix($constraints);

    $queryBuilder->execute();

After (TYPO3 v13)
-----------------

.. code-block:: php
   :emphasize-lines: 5,11-13

    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable($tableName);

    $constraints = GeneralUtility::makeInstance(PageRepository::class)
        ->getDefaultConstraints($tableName);

    $queryBuilder
        ->select('*')
        ->from($tableName);

    if ($constraints !== []) {
        $queryBuilder->where(...$constraints);
    }

    $queryBuilder->execute();

.. index:: Database, Frontend, PHP-API, NotScanned, ext:core
