.. include:: /Includes.rst.txt

======================================================================
Feature: #81651 - Query builder object as argument in list module hook
======================================================================

See :issue:`81651`

Description
===========

A new parameter :php:`$queryBuilder` has been added to
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][DatabaseRecordList::class]['buildQueryParameters']` hook.
The QueryBuilder object can be used to modify the main list module query.
The QueryBuilder instance is passed by reference, it allows any query modification.


Impact
======

The old :php:`$parameters` array has been marked as deprecated.
Existing hooks must be adjusted.

.. index:: Backend, Database, PHP-API
