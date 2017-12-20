.. include:: ../../Includes.txt

========================================================
Deprecation: #79122 - Deprecate method getRecordsByField
========================================================

See :issue:`79122`

Description
===========

The method :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField()` has been deprecated and should not be used any longer.


Impact
======

Calling the deprecated :php:`getRecordsByField()` method will trigger a deprecation log entry.


Affected Installations
======================

Any installation using the mentioned method :php:`getRecordsByField()`.


Migration
=========

Use the :php:`ConnectionPool` and the :php:`QueryBuilder` classes directly to query the database from your code.

.. index:: Backend, PHP-API
