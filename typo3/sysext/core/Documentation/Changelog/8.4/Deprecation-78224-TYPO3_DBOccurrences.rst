.. include:: /Includes.rst.txt

==========================================
Deprecation: #78224 - TYPO3_DB occurrences
==========================================

See :issue:`78224`

Description
===========

The TYPO3_DB shorthand functionality has been removed for most of the TYPO3 Core PHP classes, excepted for the following locations:

* AbstractPlugin->databaseConnection (protected property)
* AbstractFunctionModule::getDatabaseConnection()
* BaseScriptClass::getDatabaseConnection()

For these occurrences extensions might extend the base functionality (e.g. for plugins or modules) and the call to the property and
protected methods still work.

Calling `$GLOBALS[TYPO3_DB]` is still possible but discouraged.


Impact
======

Calling any of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instances with references to TYPO3_DB or references to the occurrences mentioned above.


Migration
=========

Use the ConnectionPool and the QueryBuilder classes to achieve future-proof and proper database abstraction for future TYPO3
versions.

.. index:: PHP-API
