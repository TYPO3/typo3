.. include:: /Includes.rst.txt

.. _breaking-102518-1701035329:

========================================================
Breaking: #102518 - Database engine version requirements
========================================================

See :issue:`102518`

Description
===========

TYPO3 v13 supports these database products and versions:

* MySQL 8.0 or higher
* MariaDB 10.4.3 or higher
* PostgresSQL 10.0 or higher
* SQLite 3.8.3 or higher

Impact
======

Environments with older MariaDB database engines will reporting a unsupported
database version and stop working properly with the upcoming Doctrine DBAL v4
upgrade.

Affected installations
======================

Hosting a TYPO3 instance based on version 13 may require an update of the
MariaDB database engine.


Migration
=========

TYPO3 v12 supports MariaDB 10.4.3 and higher database engines required by v13.
This allows upgrading the platform in a first step and upgrading to TYPO3 v13
in a second step..

.. index:: Database, PHP-API, NotScanned, ext:core
