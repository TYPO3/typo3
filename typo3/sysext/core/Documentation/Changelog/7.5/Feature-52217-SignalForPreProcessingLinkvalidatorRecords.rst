
.. include:: ../../Includes.txt

=================================================================
Feature: #52217 - Signal for pre processing linkvalidator records
=================================================================

See :issue:`52217`

Description
===========

This signal allows for additional processing upon initialization of a specific record,
e.g. getting content data from plugin configuration in record.

Registering the signal: (in ext_localconf.php)

.. code-block:: php

    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Linkvalidator\LinkAnalyzer::class,
        'beforeAnalyzeRecord',
        \Vendor\Package\Slots\RecordAnalyzerSlot::class,
        'beforeAnalyzeRecord'
    );

..

The slot class:

.. code-block:: php

    namespace Vendor\Package\Slots;

    use TYPO3\CMS\Linkvalidator\LinkAnalyzer;

    class RecordAnalyzerSlot {

        /**
         * Receives a signal before the record is analyzed
         *
         * @param array $results Array of broken links
         * @param array $record Record to analyse
         * @param string $table Table name of the record
         * @param array $fields Array of fields to analyze
         * @param LinkAnalyzer $parentObject Parent object
         * @return array
         */
        public function beforeAnalyzeRecord($results, $record, $table, $fields, LinkAnalyzer $parentObject) {
            // Processing here
            return array(
                $results,
                $record
            );
        }
    }

..

Impact
======

Extensions may now perform any kind of processing for every record when validating content links.


.. index:: PHP-API, Backend, ext:linkvalidator
