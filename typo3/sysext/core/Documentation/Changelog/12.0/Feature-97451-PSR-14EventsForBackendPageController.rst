.. include:: /Includes.rst.txt

.. _feature-97451:

==================================================================
Feature: #97451 - PSR-14 events for modifying backend page content
==================================================================

See :issue:`97451`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent` has
been introduced which serves as a direct replacement for the now removed
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess']`,
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['renderPreProcess']`, and
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['renderPostProcess']`
:doc:`hooks <../12.0/Breaking-97451-RemoveBackendControllerPageHooks>`.

The new event triggers after the page is rendered and includes
the rendered page body. Listeners may overwrite the page string if desired.

Example
=======

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/backend/after-backend-controller-render'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;

    final class MyEventListener
    {
        public function __invoke(AfterBackendPageRenderEvent $event): void
        {
            $content = $event->getContent() . ' I was here';
            $event->setContent($content);
        }
    }

Impact
======

It's now possible to modify the backend page using the new PSR-14 event :php:`AfterBackendPageRenderEvent`.

.. index:: Backend, PHP-API, ext:backend
