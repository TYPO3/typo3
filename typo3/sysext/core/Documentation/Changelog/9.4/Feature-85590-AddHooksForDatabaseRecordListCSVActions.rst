.. include:: ../../Includes.txt

==============================================================
Feature: #85590 - Add hooks for DatabaseRecordList CSV actions
==============================================================

See :issue:`85590`

Description
===========

It is now possible to customize the csv output in the DatabaseRecordList with hooks.

The following two hooks were implemented:

- customizeCsvHeader for the header
- customizeCsvRow for a single row


Example:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][DatabaseRecordList::class]['customizeCsvRow'][] = \Vendor\ExtName\Hooks\CsvExport::class . '->customizeCsvRow';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][DatabaseRecordList::class]['customizeCsvHeader'][] = \Vendor\ExtName\Hooks\CsvExport::class . '->customizeCsvHeader';

.. index:: Backend, PHP-API
