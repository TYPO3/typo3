.. include:: /Includes.rst.txt

.. _feature-98426-1664381958:

=========================================================================
Feature: #98426 - New PSR-14 event AfterRecordSummaryForLocalizationEvent
=========================================================================

See :issue:`98426`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Controller\Event\AfterRecordSummaryForLocalizationEvent`
has been added to TYPO3 Core. This event is fired in the
:php:`\TYPO3\CMS\Backend\Controller\Page\RecordSummaryForLocalization` class
and allows extensions to modify the payload of the :php:`JsonResponse`
in the :php:`getRecordLocalizeSummary` method.

The event features the following methods:

- :php:`getColumns()`: Returns the current :php:`$columns` array
- :php:`getRecords()`: Returns the current :php:`$records` array
- :php:`setColumns()`: Sets the current :php:`$columns` array
- :php:`setRecords()`: Sets the current :php:`$records` array

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\EventListener\AfterRecordSummaryForLocalizationEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/after-record-summary-for-localization-event-listener'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Controller\Event\AfterRecordSummaryForLocalizationEvent;

    final class AfterRecordSummaryForLocalizationEventListener
    {
        public function __invoke(AfterRecordSummaryForLocalizationEvent $event): void
        {
            // Get current records
            $records = $event->getRecords();

            // Remove or add $records available for translation

            // Set new records
            $event->setRecords($records);

            // Get current columns
            $columns = $event->getColumns();

            // Remove or add $columns available for translation

            // Set new columns
            $event->setColumns($columns);
        }
    }

Impact
======

The :php:`getRecordLocalizeSummary` method is called in the translation process,
when displaying records and columns to translate.
It is now possible to use a new PSR-14 event that can modify the
:php:`$columns` and :php:`$records` which are available for translation.

.. index:: Backend, PHP-API, ext:backend
