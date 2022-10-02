.. include:: /Includes.rst.txt

.. _breaking-96553:

================================================
Breaking: #96553 - TYPO3 v12 system requirements
================================================

See :issue:`96553`

Description
===========

The minimum PHP version required to run TYPO3 version v12 has been defined as 8.1.

TYPO3 v12 supports these database products and versions:

* MySQL 8.0 or higher
* MariaDB 10.3 or higher
* PostgreSQL 10.0 or higher
* SQLite 3.8.3 or higher
* Support for Microsoft SQL Server in any version is discontinued

Impact
======

The TYPO3 Core codebase and extensions tailored for v12 and above can use
features implemented with PHP up to and including 8.1. Running TYPO3 v12 with older PHP
versions or database engines will trigger fatal errors.

Affected Installations
======================

Hosting a TYPO3 instance based on version 12 may require an update of the
PHP platform and the database engine.

Migration
=========

TYPO3 v11 supports PHP 8.1 and database engines required by v12. This allows upgrading
the platform in a first step and upgrading to TYPO3 v12 in a second step.

.. index:: Database, PHP-API, NotScanned, ext:core
