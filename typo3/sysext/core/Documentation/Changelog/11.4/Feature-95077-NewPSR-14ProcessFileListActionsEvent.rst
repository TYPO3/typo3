.. include:: /Includes.rst.txt

========================================================
Feature: #95077 - New PSR-14 ProcessFileListActionsEvent
========================================================

See :issue:`95077`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\Configuration\Event\ProcessFileListActionsEvent` has been added to
TYPO3 Core. This event is fired after generating the actions for the
files and folders listing in the :guilabel:`File > Filelist` module.

Registration of the Event in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\FileList\MyEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/filelist/my-event-listener'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

    class MyEventListener {

        public function __invoke(ProcessFileListActionsEvent $event): void
        {
            // do your magic
        }

    }

Impact
======

This event can be used to manipulate the icons, used for the edit control
section in the files and folders listing within the :guilabel:`File > Filelist`
module.

.. index:: PHP-API, ext:filelist
