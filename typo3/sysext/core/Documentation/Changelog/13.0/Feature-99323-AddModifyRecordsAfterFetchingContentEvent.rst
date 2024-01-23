.. include:: /Includes.rst.txt

.. _feature-99323:

===========================================================================
Feature: #99323 - PSR-14 event for modifying records after fetching content
===========================================================================

See :issue:`99323`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Frontend\ContentObject\Event\ModifyRecordsAfterFetchingContentEvent`
has been introduced which serves as a more powerful replacement for the now
:doc:`removed <../13.0/Breaking-99323-RemovedHookForModifyingRecordsAfterFetchingContent>`
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content_content.php']['modifyDBRow']`
hook.

The event allows to modify the fetched records next to the possibility to
manipulate most of the options, such as `slide`. Listeners are also able
to set the final content and change the whole TypoScript configuration,
used for further processing.

This can be achieved with the following methods:

- :php:`getRecords()`
- :php:`getFinalContent()`
- :php:`getSlide()`
- :php:`getSlideCollect()`
- :php:`getSlideCollectReverse()`
- :php:`getSlideCollectFuzzy()`
- :php:`getConfiguration()`
- :php:`setRecords()`
- :php:`setFinalContent()`
- :php:`setSlide()`
- :php:`setSlideCollect()`
- :php:`setSlideCollectReverse()`
- :php:`setSlideCollectFuzzy()`
- :php:`setConfiguration()`

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration:

..  code-block:: php

    use TYPO3\CMS\Frontend\ContentObject\Event\ModifyRecordsAfterFetchingContentEvent;

    final class ModifyRecordsAfterFetchingContentEventListener
    {
        #[AsEventListener]
        public function __invoke(ModifyRecordsAfterFetchingContentEvent $event): void
        {
            if ($event->getConfiguration()['table'] !== 'tt_content') {
                return;
            }

            $records = array_reverse($event->getRecords());
            $event->setRecords($records);
        }
    }

Impact
======

Using the new PSR-14 event, it's now possible to modify the records
fetched by the "Content" ContentObject, before they are being further
processed, or even skip TYPO3's default processing of records by setting
an empty array for the records to be rendered.

Additionally, next to the final content, also most of the options and the
whole TypoScript configuration can be modified.

.. index:: PHP-API, ext:frontend
