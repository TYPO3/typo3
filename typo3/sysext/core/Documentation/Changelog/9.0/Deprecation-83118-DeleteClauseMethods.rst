.. include:: ../../Includes.txt

=====================================================
Deprecation: #83118 - DeleteClause methods deprecated
=====================================================

See :issue:`83118`

Description
===========

The PHP methods :php:`PageRepository::deleteClause()` and :php:`BackendUtility::deleteClause()` have been
marked as deprecated, as all database queries are now put through Doctrine DBAL's restriction functionality.


Impact
======

Calling one of the two methods above will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 extension using any of the two methods above.


Migration
=========

Migrate to Doctrine DBAL and use the new Database API (ConnectionPool, QueryBuilder) to access the database
with the :php:`DeletedRestriction` class.

.. index:: PHP-API, Database, Frontend, FullyScanned