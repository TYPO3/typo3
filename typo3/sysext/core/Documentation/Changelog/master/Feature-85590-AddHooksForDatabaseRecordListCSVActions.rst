.. include:: ../../Includes.txt

==============================================================
Feature: #85590 - Add hooks for DatabaseRecordList CSV actions
==============================================================

See :issue:`85590`

Description
===========

Now it is possible to customize the csv output in the DatabaseRecordList with a hook.

The following two hooks were implemented:
- customizeCsvHeader for the header
- customizeCsvRow for a single row


Impact
======

Now you can influence the output of the CSV files in the DatabaseRecordList with hooks. For this you can
register the hooks in an extension.

Example:

.. code-block:: php

$hookName = DatabaseRecordList::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$hookName]['customizeCsvRow'][] = \Vendor\ExtName\Hooks\CsvTest::class . '->customizeCsvRow';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$hookName]['customizeCsvHeader'][] = \Vendor\ExtName\Hooks\CsvTest::class . '->customizeCsvHeader';

.. index:: Backend