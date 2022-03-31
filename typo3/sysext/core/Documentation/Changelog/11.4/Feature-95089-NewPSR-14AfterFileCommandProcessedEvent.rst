.. include:: /Includes.rst.txt

===========================================================
Feature: #95089 - New PSR-14 AfterFileCommandProcessedEvent
===========================================================

See :issue:`95089`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\Resource\Event\AfterFileCommandProcessedEvent`
has been added to TYPO3 Core. This event is fired in the
:php:`\TYPO3\CMS\Core\Utility\File\ExtendedFileUtility`
class and allows extensions to execute additional tasks, after a file
operation has been performed.

The event features the following methods:

- :php:`getCommand()`: Returns the command array while the array key is the performed action and the value is the command data ("cmdArr")
- :php:`getResult()`: Returns the operation result, which could e.g. be an uploaded or changed :php:`File` or a :php:`boolean` for the "delete" action
- :php:`getConflictMode()`: The conflict mode for the performed operation, e.g. "rename" or "cancel"

Registration of the Event in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\File\MyEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/file/my-event-listener'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Core\Resource\Event\AfterFileCommandProcessedEvent;

    class MyEventListener {

        public function __invoke(AfterFileCommandProcessedEvent $event): void
        {
            // do magic here
        }

    }

Impact
======

This event can be used to perform additional tasks for specific file commands.
For example, trigger a custom indexer after a file has been uploaded.

.. index:: PHP-API, ext:core
