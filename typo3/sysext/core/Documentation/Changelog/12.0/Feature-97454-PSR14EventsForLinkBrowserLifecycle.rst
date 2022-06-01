.. include:: /Includes.rst.txt

===================================================================
Feature: #97454 - PSR-14 Events for modifying link browser behavior
===================================================================

See :issue:`97454`

Description
===========

Two new PSR-14 Events :php:`\TYPO3\CMS\Recordlist\Event\ModifyLinkHandlersEvent` and
:php:`\TYPO3\CMS\Recordlist\Event\ModifyAllowedItemsEvent` have been introduced which
serves as a direct replacement for the now removed
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LinkBrowser']['hooks']` array.
:doc:`hooks <../12.0/Breaking-97454-RemoveLinkBrowserHooks>`.
The first is triggered before link handlers are executed, allowing listeners
to modify the set of handlers that will be used.  The second


Example
=======

Registration of the Event in your extension's :file:`Services.yaml`:

.. code-block:: yaml

    MyVendor\MyPackage\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/recordlist/link-handlers'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Recordlist\Event\ModifyLinkHandlersEvent;

    final class MyEventListener
    {
        public function __invoke(ModifyLinkHandlersEvent $event): void
        {
            $handler = $event->getHandler('url.');
            $handler['label'] = 'My custom label';
            $event->setHandler('url.', $handler);
        }
    }

Impact
======

It's now possible to modify link handlers behavior using the new PSR-14
:php:`ModifyLinkHandlersEvent` and :php:`ModifyAllowedItemsEvent`.

.. index:: Backend, PHP-API, ext:backend
