.. include:: /Includes.rst.txt

.. _deprecation-96972:

=======================================================
Deprecation: #96972 - Deprecate QueryBuilder::execute()
=======================================================

See :issue:`96972`

Description
===========

`doctrine/dbal` deprecated the union return-type method :php:`QueryBuilder->execute()` in favour
of single return-typed :php:`QueryBuilder->executeQuery()` and :php:`QueryBuilder->executeStatement()`
in `doctrine/dbal v3.1.x`. This makes it more obvious which return type is expected and further helps
static code analyzer tools like `phpstan` to recognize return types properly. TYPO3 already provides a
facade class around the `doctrine/dbal` :php:`QueryBuilder`, which has been changed to provide the new
methods in the Core facade class with a corresponding backport.

Thus :php:`QueryBuilder->execute()` is marked as deprecated in TYPO3 v12 and will be removed in v13 to
encourage extension developers to use the cleaner methods and decrease issues with static code analysers.

Impact
======

The method :php:`execute()` is also used for Extbase query execution and as Upgrade Wizard method, thus
the extension scanner is not configured to scan for this method to avoid a lot of noisy weak matches.

:php:`QueryBuilder->execute()` will trigger a PHP :php:`E_USER_DEPRECATED` error when called.

Affected Installations
======================

In general, instances with extensions that uses the deprecated :php:`QueryBuilder->execute()` method.

Migration
=========

Extensions should use the proper methods :php:`QueryBuilder->executeQuery()` and :php:`QueryBuilder->executeStatement()`
instead of the generic :php:`QueryBuilder->execute()`. Through the backport to TYPO3 v11 extensions can change to deprecation
less code but keep supporting two major Core version at the same time.

- :php:`QueryBuilder::executeStatement()`: use this for INSERT, DELETE or UPDATE queries (expecting `int` as return value).
- :php:`QueryBuilder::executeQuery()`: use this for SELECT and COUNT queries (expecting ResultSet as return value).

As a thumb rule you can say that queries which expects a result set should use :php:`QueryBuilder::executeQuery()`.
Queries which return the number of affected rows should use :php:`QueryBuilder::executeStatement()`.

For example, following select query:

..  code-block:: php

    $rows = $queryBuilder
      ->select(...)
      ->from(...)
      ->execute()
      ->fetchAllAssociative();

should be replaced with:

..  code-block:: php

    $rows = $queryBuilder
      ->select(...)
      ->from(...)
      ->executeQuery()
      ->fetchAllAssociative();

As another example, given delete query:

..  code-block:: php

    $deletedRows = $queryBuilder
      ->delete(...)
      ->execute();

should be replaced with:

..  code-block:: php

    $deletedRows = $queryBuilder
      ->delete(...)
      ->executeStatement();

.. index:: Database, NotScanned, ext:core
