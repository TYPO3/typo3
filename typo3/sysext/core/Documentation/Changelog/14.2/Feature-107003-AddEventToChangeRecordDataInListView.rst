..  include:: /Includes.rst.txt

..  _feature-107003-1751223220:

===============================================================
Feature: #107003 - Add event to change record data in list view
===============================================================

See :issue:`107003`

Description
===========

A new PSR-14 event
:php:`\TYPO3\CMS\Backend\RecordList\Event\AfterRecordListRowPreparedEvent`
has been added. This event is dispatched in
:php-short:`TYPO3\CMS\Backend\RecordList\DatabaseRecordList` and allows
extensions to modify the data used to render a single record in the list
view.

The event allows the following properties to be modified:

*   `data`: The row fields as an array. The following fields are available:

    *   `_SELECTOR_`: The checkbox element
    *   `icon`: The icon
    *   `__label`: Special field that contains the header
    *   `_CONTROL_`: The row action buttons
    *   `_LOCALIZATION_`: The current language
    *   `_LOCALIZATION_b`: The translated language
    *   `rowDescription`: The row description
    *   `header`: The header. This field is used only if `__label` is not
        set. Use `__label` instead.
    *   `uid`: The record UID (read-only)

*   `tagAttributes`: The HTML tag attributes of the row. The following
    attributes are available:

    *   `class`
    *   `data-table`
    *   `title`

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\RecordList\Event\AfterRecordListRowPreparedEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    #[AsEventListener('my-package/backend/my-listener-name')]
    final class MyEventListener
    {
        public function __invoke(AfterRecordListRowPreparedEvent $event): void
        {
            $data = $event->getData();
            $tagAttributes = $event->getTagAttributes();

            // Modify the row data and tag attributes here.

            $event->setData($data);
            $event->setTagAttributes($tagAttributes);
        }
    }

Impact
======

The new PSR-14 event can be used, for example, to modify the title link in
the record list.

..  index:: PHP-API, ext:backend
