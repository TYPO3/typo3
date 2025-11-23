..  include:: /Includes.rst.txt

..  _breaking-107356:

=================================================
Breaking: #107356 - Use Record API in List Module
=================================================

See :issue:`107356`

Description
===========

The :guilabel:`Content > List` backend module has been refactored to use the
:php-short:`\TYPO3\CMS\Backend\Record\RecordInterface` and related Record API
internally instead of working with raw database row arrays.

This modernization introduces stricter typing and improves data consistency.
As a result, several public method signatures have been updated.

The following public methods in
:php-short:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList` have changed
their signatures:

- :php:`renderListRow()` now expects a :php:`RecordInterface` object instead
  of an array as the second parameter.
- :php:`makeControl()` now expects a :php:`RecordInterface` object instead of
  an array as the second parameter.
- :php:`makeCheckbox()` now expects a :php:`RecordInterface` object instead of
  an array as the second parameter.
- :php:`languageFlag()` now expects a :php:`RecordInterface` object instead of
  an array as the second parameter.
- :php:`makeLocalizationPanel()` now expects a :php:`RecordInterface` object
  instead of an array as the second parameter.
- :php:`linkWrapItems()` now expects a :php:`RecordInterface` object instead
  of an array as the fourth parameter.
- :php:`getPreviewUriBuilder()` now expects a :php:`RecordInterface` object
  instead of an array as the second parameter.
- :php:`isRecordDeletePlaceholder()` now expects a :php:`RecordInterface`
  object instead of an array.
- :php:`isRowListingConditionFulfilled()` has dropped the first parameter
  :php:`$table` and now expects a :php:`RecordInterface` object instead of an
  array.

These changes enable the List module to operate on structured Record objects,
providing better type safety, consistency, and a foundation for further
modernization of the backend record handling.

Impact
======

Code that calls these methods directly must be updated to pass
:php-short:`\TYPO3\CMS\Backend\Record\RecordInterface` objects instead of
database row arrays.

Affected installations
======================

TYPO3 installations with custom extensions that:

- Extend or XCLASS
  :php-short:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList` and override
  any of the affected methods.
- Call the affected methods directly with array-based record data.

Migration
=========

When calling affected methods, use the Record API to create a Record object
from a database row:

**Before:**

..  code-block:: php
    :caption: Migrating from array-based record handling to the Record API (before)

    $databaseRecordList->renderListRow($table, $rowArray, $indent, $translations, $enabled);

**After:**

..  code-block:: php
    :caption: Migrating from array-based record handling to the Record API (after)

    use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
    use TYPO3\CMS\Backend\Record\RecordFactory;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $recordFactory = GeneralUtility::makeInstance(RecordFactory::class);
    $record = $recordFactory->createResolvedRecordFromDatabaseRow($table, $rowArray);
    $databaseRecordList->renderListRow($table, $record, $indent, $translations, $enabled);

..  index:: Backend, PHP-API, FullyScanned, ext:backend
