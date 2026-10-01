.. include:: /Includes.rst.txt

.. _feature-110815-1790590629:

==========================================================
Feature: #110815 - PSR-14 event after a record is rendered
==========================================================

See :issue:`110815`

Description
===========

The new PSR-14 event
:php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterRecordIsRenderedEvent`
is dispatched by the :typoscript:`CONTENT` and :typoscript:`RECORDS`
content objects after each single record has been rendered. It allows
modifying the rendered content of the record, for example to wrap it in
additional markup.

It is the counterpart to
:php:`\TYPO3\CMS\Fluid\Event\ModifyRenderedRecordEvent`, which is
dispatched for records rendered with the :html:`<f:render.record>` and
:html:`<f:render.contentArea>` ViewHelpers.

The event provides the following methods:

`getRenderedRecord()`
    Returns the rendered content of the record.

`setRenderedRecord()`
    Replaces the rendered content of the record. Make sure to return
    escaped content if necessary.

`getRecord()`
    Returns the rendered record as
    :php:`\TYPO3\CMS\Core\Domain\RecordInterface`, like
    :php-short:`\TYPO3\CMS\Fluid\Event\ModifyRenderedRecordEvent` does. This is usually a resolved
    record. If the row cannot be resolved, for example because a custom
    :typoscript:`select.selectFields` omits system fields such as the
    language or workspace fields, a
    :php:`\TYPO3\CMS\Core\Domain\RawRecord` holding the unprocessed row
    is provided instead.

`getRequest()`
    Returns the current request.

The event is not dispatched for tables without TCA, or if a custom
:typoscript:`select.selectFields` omits the type field of the table, since
no record object can be created then. The rendered content of such records
is returned unmodified.

Example
=======

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/WrapRenderedRecord.php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Frontend\ContentObject\Event\AfterRecordIsRenderedEvent;

    final class WrapRenderedRecord
    {
        #[AsEventListener]
        public function __invoke(AfterRecordIsRenderedEvent $event): void
        {
            if ($event->getRecord()->getMainType() !== 'tt_content') {
                return;
            }
            $event->setRenderedRecord(
                '<div class="my-record">' . $event->getRenderedRecord() . '</div>'
            );
        }
    }

Impact
======

Listeners can modify the output of every record rendered by the
:typoscript:`CONTENT` and :typoscript:`RECORDS` content objects, for
example the content elements of a page rendered with
:typoscript:`styles.content.get`.

.. index:: Frontend, PHP-API, TypoScript, ext:frontend
