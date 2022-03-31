.. include:: /Includes.rst.txt

==========================================================
Breaking: #93029 - Dropped deleted field from sys_refindex
==========================================================

See :issue:`93029`

Description
===========

The database field :sql:`deleted` has been removed from table
:sql:`sys_refindex`. Therefore, the table does no longer store
relations between soft deleted records.

Following properties and methods of class
:php:`TYPO3\CMS\Core\Database\ReferenceIndex` have been set to
protected:

* :php:`temp_flexRelations`
* :php:`relations` - not scanned by extension scanner
* :php:`hashVersion`
* :php:`getWorkspaceId()` - not scanned by extension scanner
* :php:`getRelations_procDB()`
* :php:`setReferenceValue_dbRels()`
* :php:`setReferenceValue_softreferences()`
* :php:`isReferenceField()` - not scanned by extension scanner

Following methods of class :php:`TYPO3\CMS\Core\Database\ReferenceIndex`
have been removed:

* :php:`generateRefIndexData()`
* :php:`createEntryData()`
* :php:`createEntryData_dbRels()`
* :php:`createEntryData_softreferences()`


Impact
======

Accessing the properties of class :php:`ReferenceIndex` or calling
dropped or protected methods will raise fatal PHP errors.

Querying the :sql:`deleted` field of table :sql:`sys_refindex` will raise a
doctrine dbal exception.


Affected Installations
======================

The hash sums of existing table rows change. The reference index
should be updated, typically by using the CLI command
:php:`bin/typo3 referenceindex:update`

Codewise, instances with extensions that query table :sql:`sys_refindex`
or use class :php:`ReferenceIndex` may be affected. The extension
scanner helps to find some usages.


Migration
=========

Use the CLI command :php:`bin/typo3 referenceindex:update` to update
the reference index.

The :sql:`sys_refindex.deleted` field should be dropped from database
queries.

When accessing class :php:`ReferenceIndex`, use the main API method
:php:`->updateRefIndexTable()`, plus a couple of other less often
used methods.

.. index:: Database, PHP-API, PartiallyScanned, ext:core
