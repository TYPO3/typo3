.. include:: /Includes.rst.txt

=========================================================
Feature: #95083 - New PSR-14 ModifyClearCacheActionsEvent
=========================================================

See :issue:`95083`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent`
has been added to TYPO3 Core. This event is fired in the
:php:`\TYPO3\CMS\Backend\Backend\ToolbarItems\ClearCacheToolbarItem`
class and allows extensions to modify the clear cache actions, shown
in the TYPO3 Backend top toolbar.

The event can be used to change or remove existing clear cache
actions, as well as to add new actions. Therefore the event also
contains, next to the usual "getter" and "setter", the convenience
method :php:`add` for the :php:`cacheActions` and
:php:`cacheActionIdentifiers` arrays.

Registration of the Event in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\Toolbar\MyEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/toolbar/my-event-listener'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;

    class MyEventListener {

        public function __invoke(ModifyClearCacheActionsEvent $event): void
        {
            // do magic here
        }

    }

Impact
======

This event can be used to modify the clear cache actions, shown in the
TYPO3 Backend top toolbar.

.. index:: PHP-API, ext:backend
