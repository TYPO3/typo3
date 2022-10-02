.. include:: /Includes.rst.txt

.. _breaking-96287:

===================================
Breaking: #96287 - Doctrine DBAL v3
===================================

See :issue:`96287`

Description
===========

TYPO3 v12.0 has updated its Database Abstraction package based on Doctrine
DBAL to the next major version Doctrine DBAL v3.

Impact
======

Doctrine DBAL 3 has undergone major refactorings internally by separating
Doctrine's internal driver logic from PHP's native PDO functionality.

See https://www.doctrine-project.org/2021/03/29/dbal-2.13.html and
https://www.doctrine-project.org/2020/11/17/dbal-3.0.0.html
for more details.

In addition, most database APIs which TYPO3 provides as wrappers around
the existing functionality is already available in TYPO3 v11 and
continue to work in TYPO3 v12.

Affected Installations
======================

TYPO3 installations with custom third-party extensions using TYPO3's
Database Abstraction functionality, or extensions using
the Doctrine DBAL API directly.

Migration
=========

Read Doctrine's migration paths (see links above) to migrate any existing
code.

The main change for 95% of the developers are, that queries and database result-sets
now have more explicit APIs when querying the database.

Examples:

..  code-block:: php

    $result = $queryBuilder
      ->select(...)
      ->from(...)
      // use executeQuery() instead of execute()
      ->executeQuery();

:php:`$result` is now of type :php:`\Doctrine\DBAL\Result`, and not of type
:php:`\Doctrine\DBAL\Statement` anymore, which allows to fetch rows / columns via
new and more speaking methods:

* :php:`->fetchAllAssociative()` instead of :php:`->fetchAll()`
* :php:`->fetchAssociative()` - instead of :php:`->fetch()`
* :php:`->fetchOne()` - instead of :php:`->fetchColumn(0)`

The method :php:`executeQuery` - available in the QueryBuilder and
the Connection class is now in for select/count queries and returns a Result
object directly, whereas :php:`executeStatement()` is used for insert / update / delete
statements, returning an integer - the number of affected rows.

Use both methods instead of the previous :php:`execute()` method,
which is still available for backwards-compatibility.

.. index:: Database, NotScanned, ext:core
