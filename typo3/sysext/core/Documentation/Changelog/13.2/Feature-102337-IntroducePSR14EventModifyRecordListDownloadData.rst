.. include:: /Includes.rst.txt

.. _feature-102337-1715591178:

=====================================================================
Feature: #102337 - PSR-14 event for modifying record list export data
=====================================================================

See :issue:`102337`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadIsExecutedEvent`
has been introduced to modify the result of a download / export initiated in
the :guilabel:`Web > List` module.

This replaces the
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvHeader']`
and
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvRow']`,
hooks, which have been :ref:`deprecated <deprecation-102337-1715591179>`.

The event allows body and header sections of the data dump to be modified,
so that they can e.g. be used to redact specific data for GDPR compliance,
transform / translate specific data, trigger creation of archives or web hooks,
log export access and more.

The event offers the following methods:

- :php:`getHeaderRow()`: Return the current header row of the dataset.
- :php:`setHeaderRow()`: Sets the modified header row of the dataset.
- :php:`getRecords()`: Returns the current body rows of the dataset.
- :php:`setRecords()`: Sets the modified body rows of the dataset.
- :php:`getRequest()`: Returns the PSR request context.
- :php:`getTable()`: Returns the name of the database table of the dataset.
- :php:`getFormat()`: Returns the format of the download action (CSV/JSON).
- :php:`getFilename()`: Returns the name of the download filename (for browser output).
- :php:`getId()`: Returns the page UID of the download origin.
- :php:`getModTSconfig()`: Returns the active module TSconfig of the download origin.
- :php:`getColumnsToRender()`: Returns the list of header columns for the triggered download.
- :php:`isHideTranslations()`: Returns whether translations are hidden or not.

Example
=======

The corresponding event listener class:

..  code-block:: php

    <?php

    declare(strict_types=1);

    namespace Vendor\MyPackage\Core\EventListener;

    use TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadIsExecutedEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    #[AsEventListener(identifier: 'my-package/record-list-download-data')]
    final readonly class DataListener
    {
        public function __invoke(BeforeRecordDownloadIsExecutedEvent $event): void
        {
            // List of redactable fields.
            $gdprFields = ['title', 'author'];

            $headerRow = $event->getHeaderRow();
            $records = $event->getRecords();

            // Iterate header to mark redacted fields...
            foreach ($headerRow as $headerRowKey => $headerRowValue) {
                if (in_array($headerRowKey, $gdprFields, true)) {
                    $headerRow[$headerRowKey] .= ' (REDACTED)';
                }
            }

            // Redact actual content...
            foreach ($records as $uid => $record) {
                foreach ($gdprFields as $gdprField) {
                    if (isset($record[$gdprField])) {
                        $records[$uid][$gdprField] = '(REDACTED)';
                    }
                }
            }

            $event->setHeaderRow($headerRow);
            $event->setRecords($records);
        }
    }

Migration
=========

The functionality of both hooks :php:`customizeCsvHeader` and
:php:`customizeCsvRow` are now handled by the new PSR-14 event.

Migrating :php:`customizeCsvHeader`
-----------------------------------

The prior hook parameter/variable :php:`fields` is now available via
:php:`$event->getColumnsToRender()`. The actual record data
(previously :php:`$this->recordList`, submitted to the hook as its object
reference) is accessible via :php:`$event->getHeaderRow()`.

Migrating :php:`customizeCsvRow`
--------------------------------

The prior hook parameters/variables have the following substitutes:

- :php:`databaseRow` is now available via :php:`$event->getRecords()` (see note below).
- :php:`tableName` is now available via :php:`$event->getTable()`.
- :php:`pageId` is now available via :php:`$event->getId()`.

The actual record data
(previously :php:`$this->recordList`, submitted to the hook as its object
reference) is accessible via :php:`$event->getRecords()`.

Please note that the hook was previously executed once per row retrieved
from the database. The PSR-14 event however - due to performance reasons -
is only executed for the full record list after database retrieval,
thus allowing post-processing on the whole dataset.

Impact
======

Using the PSR-14 event :php:`BeforeRecordDownloadIsExecutedEvent` it is
now possible to modify all of the data available when downloading / exporting
a list of records via the :guilabel:`Web > List` module.


.. index:: Backend, PHP-API, ext:core
