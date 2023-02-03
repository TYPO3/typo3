.. include:: /Includes.rst.txt

.. _feature-99430-1672129914:

=================================================================
Feature: #99430 - Add event after record publishing in workspaces
=================================================================

See :issue:`99430`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Workspaces\Event\AfterRecordPublishedEvent`
has been added to allow extension developers to react on record publishing
in workspaces.

The new event is fired after a record has been published in a workspace and
provides the following methods:

-   :php:`getTable()`: The record's table name
-   :php:`getRecordId()`: The record's UID
-   :php:`getWorkspaceId()`: The workspace the record has been published in

Example
=======

Registration of the :php:`AfterRecordPublishedEvent` in your extension's
:file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Workspaces\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/after-record-published'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Workspaces/MyEventListener.php

    namespace MyVendor\MyExtension\Workspaces;

    use TYPO3\CMS\Workspaces\Event\AfterRecordPublishedEvent;

    final class MyEventListener {
        public function __invoke(AfterRecordPublishedEvent $event): void
        {
            // Do your magic here
        }
    }

Impact
======

With the new PSR-14 event :php:`AfterRecordPublishedEvent` it is possible to
execute custom functionality after a record has been published in a workspace.

.. index:: PHP-API, ext:workspaces
