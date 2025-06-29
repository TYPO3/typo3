..  include:: /Includes.rst.txt

..  _feature-107003-1751223220:

===============================================================
Feature: #107003 - Add event to change record data in list view
===============================================================

See :issue:`107003`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\RecordList\Event\AfterRecordListRowPreparedEvent`
has been added. This event is fired in the
:php:`\TYPO3\CMS\Backend\RecordList\DatabaseRecordList` and allows extensions
to change the data for rendering a single record in the list view.

The event allows to adjust the following properties:

- `data`: The fields of the row as array. These are the available fields:

  - `_SELECTOR_`: The checkbox element
  - `icon`: The icon
  - `__label`: Special field that contains the header
  - `_CONTROL_`: The action buttons of the row
  - `_LOCALIZATION_`: The current language
  - `_LOCALIZATION_b`: The translated language
  - `rowDescription`: The row description
  - `header`: The header (This field is only used if `__label` is not set. Use `__label` instead)
  - `uid`: The record uid (readonly)

- `tagAttributes`: The html tag attributes of the row. These attributes are available:

  - `class`
  - `data-table`
  - `title`

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Backend\RecordList\Event\AfterRecordListRowPreparedEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    #[AsEventListener('my-package/backend/my-listener-name')]
    class MyEventListener
    {
        public function __invoke(AfterRecordListRowPreparedEvent $event): void
        {
            $data = $event->getData();
            $tagAttributes = $event->getTagAttributes();
            // do magic here
            $event->setData($data);
            $event->setTagAttributes($tagAttributes);
        }
    }


Impact
======

The new PSR-14 event can be used to for example modify
the title link in the record list.

..  index:: PHP-API, ext:backend
