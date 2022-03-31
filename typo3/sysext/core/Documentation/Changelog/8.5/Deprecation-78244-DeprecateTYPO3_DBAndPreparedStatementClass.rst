.. include:: /Includes.rst.txt

=====================================================================
Deprecation: #78244 - Deprecate TYPO3_DB and Prepared Statement class
=====================================================================

See :issue:`78244`

Description
===========

The classes `TYPO3\CMS\Core\Database\DatabaseConnection` and  `TYPO3\CMS\Core\Database\PreparedStatement` have been marked as deprecated.
This classes have been succeeded by Doctrine DBAL in TYPO3 v8, and will be removed in TYPO3 v9.


Impact
======

Calling any methods of the classes above will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instances with references to `$GLOBALS['TYPO3_DB']` or use instances of the mentioned classes above.


Migration
=========

Use the `ConnectionPool` and the `QueryBuilder` classes to achieve future-proof and proper database abstraction for future TYPO3 versions.

.. index:: Database, PHP-API, Frontend, Backend, CLI
