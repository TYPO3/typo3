.. include:: /Includes.rst.txt

==========================================================
Feature: #98426 - New PSR-14 ModifyQueryForLiveSearchEvent
==========================================================

See :issue:`98426`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Controller\Event\AfterRecordSummaryForLocalizationEvent`
has been added to TYPO3 Core. This event is fired in the
:php:`\TYPO3\CMS\Backend\Controller\Page\RecordSummaryForLocalization` class
and allows extensions to modify the Payload in the :php:`JsonResponse`
in the :php:`getRecordLocalizeSummary` Method.

The event features the following methods:

- :php:`getColumns()`: Returns the current :php:`$columns` array
- :php:`getRecords()`: Returns the current :php:`$records` array
- :php:`setColumns()`: Sets the current :php:`$columns` array
- :php:`setRecords()`: Sets the current :php:`$records` array

Registration of the Event in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\EventListener\AfterRecordSummaryForLocalizationEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/after-record-summary-for-localization-event-listener'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Backend\Controller\Event\AfterRecordSummaryForLocalizationEvent;

    final class AfterRecordSummaryForLocalizationEventListener
    {
        public function __invoke(AfterRecordSummaryForLocalizationEvent $event): void
        {
            // Get current records
            $records = $event->getRecords();

            // remove or add $records available for translation

            // set new records
            $event->setRecords($records);

            // Get current columns
            $columns = $event->getColumns();

            // remove or add $columns available for translation

            // set new columns
            $event->setColumns($columns);
        }
    }

Impact
======

The :php:`getRecordLocalizeSummary` Method is called in the Translation Process, when displaying
Records and Columns to translate.
It is now possible to use a new PSR-14 event that can modifiy the
:php:`$columns` and :php:`$records` which are available for Translation.

.. index:: Backend, PHP-API, ext:backend
