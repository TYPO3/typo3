.. include:: /Includes.rst.txt

.. _breaking-102779-1704721008:

=================================================
Breaking: #102779 - TYPO3 v13 System Requirements
=================================================

See :issue:`102779`

Description
===========

The minimum PHP version required to run TYPO3 version v13 has been defined as 8.2.

TYPO3 v13 supports these database products and versions:

* MySQL 8.0.17 or higher
* MariaDB 10.4.3 or higher
* PostgresSQL 10.0 or higher
* SQLite 3.8.3 or higher


Impact
======

The TYPO3 Core codebase and extensions tailored for v13 and above can use
features implemented with PHP up to and including 8.2. Running TYPO3 v13 with
older PHP versions or database engines will trigger fatal errors.


Affected installations
======================

Hosting a TYPO3 instance based on version 13 may require an update of the
PHP platform and the database engine.


Migration
=========

TYPO3 v11 / v12 supports PHP 8.2 and database engines required by v13. This
allows upgrading the platform in a first step and upgrading to TYPO3 v13 in a
second step.

.. index:: PHP-API, NotScanned, ext:core
