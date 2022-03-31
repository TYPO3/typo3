
.. include:: /Includes.rst.txt

===================================================================================
Deprecation: #76259 - Deprecate method makeQueryArray of AbstractDatabaseRecordList
===================================================================================

See :issue:`76259`

Description
===========

The method :php:`AbstractDatabaseRecordList::makeQueryArray()` has been marked
as deprecated.

Impact
======

Using the method mentioned will trigger a deprecation log entry. The hook `makeQueryArray`
provided within this method is no longer called by the core.


Affected Installations
======================

Instances that use the method.


Migration
=========

Migrate your code to the Doctrine based replacement :php:`\TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList::getQueryBuilder`
and the associated hook `buildQueryParameters`.

.. index:: PHP-API, Backend
