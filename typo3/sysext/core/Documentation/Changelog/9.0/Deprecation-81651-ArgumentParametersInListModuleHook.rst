.. include:: /Includes.rst.txt

=============================================================
Deprecation: #81651 - Argument parameters in list module hook
=============================================================

See :issue:`81651`

Description
===========

The parameter array :php:`$parameters` of :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][DatabaseRecordList::class]['buildQueryParameters']`
has been marked as deprecated.


Impact
======

Changing the array content array within a hook triggers a deprecation log entry.


Affected Installations
======================

Any installation using third-party extension that use this array to modify the query.


Migration
=========

Use new argument :php:`$queryBuilder` that hands over the query builder instance
to modify the list module query.

.. index:: Backend, Database, PHP-API, NotScanned
