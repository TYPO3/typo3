.. include:: /Includes.rst.txt

===============================================
Feature: #95065 - New PSR-14 BootCompletedEvent
===============================================

See :issue:`95065`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\Core\Event\BootCompletedEvent` has been added to TYPO3
Core. This event is fired on every request when TYPO3 has been
fully booted, right after all configuration files have been added.

This new Event complements the :php:`\TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent` which
is executed after TCA configuration has been assembled.

Registration of the Event in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\Bootstrap\MyEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/my-listener'

.. code-block:: php

    class MyEventListener {
        public function __invoke(BootCompletedEvent $e): void
        {
            // do your magic
        }
    }


Impact
======

Use cases for this event is to alter or to boot up extensions'
code which needs to be executed at any time, and needs
TYPO3's full configuration including all loaded extensions.

.. index:: PHP-API, ext:core
