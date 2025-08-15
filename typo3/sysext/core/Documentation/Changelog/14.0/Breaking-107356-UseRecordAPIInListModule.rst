.. include:: /Includes.rst.txt

.. _breaking-107356:

=================================================
Breaking: #107356 - Use Record API in List Module
=================================================

See :issue:`107356`

Description
===========

The List module has been refactored to use the Record API internally instead
of accessing raw database arrays. Various public methods and class signatures
have been updated to use strict typing and the Record API, which introduces
breaking changes.

The following public method signatures have changed:

- :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList->renderListRow()` now expects a :php:`RecordInterface` object instead of an array as the second parameter
- :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList->makeControl()` now expects a :php:`RecordInterface` object instead of an array as the second parameter
- :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList->makeCheckbox()` now expects a :php:`RecordInterface` object instead of an array as the second parameter
- :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList->languageFlag()` now expects a :php:`RecordInterface` object instead of an array as the second parameter
- :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList->makeLocalizationPanel()` now expects a :php:`RecordInterface` object instead of an array as the second parameter
- :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList->linkWrapItems()` now expects a :php:`RecordInterface` object instead of an array as the fourth parameter
- :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList->getPreviewUriBuilder()` now expects a :php:`RecordInterface` object instead of an array as the second parameter
- :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList->isRecordDeletePlaceholder()` now expects a :php:`RecordInterface` object instead of an array
- :php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList->isRowListingConditionFulfilled()` first parameter :php:`$table` has been dropped and now expects a :php:`RecordInterface` object instead of an array

These changes enable the List module to work with structured Record objects instead
of raw array data, providing better type safety and enabling further modernization
of the codebase.

Impact
======

Code that calls these methods directly will need to be updated to pass
:php:`RecordInterface` objects instead of arrays.

Affected Installations
======================

All installations with extensions that:

- Extend / XCLASS :php:`DatabaseRecordList` and override the affected methods
- Call the affected methods directly with array parameters

Migration
=========

For calling code, ensure you pass Record objects instead of arrays:

.. code-block:: php

   // Before
   $databaseRecordList->renderListRow($table, $rowArray, $indent, $translations, $enabled);

   // After
   $record = $recordFactory->createResolvedRecordFromDatabaseRow($table, $rowArray);
   $databaseRecordList->renderListRow($table, $record, $indent, $translations, $enabled);

.. index:: Backend, PHP-API, FullyScanned, ext:backend
