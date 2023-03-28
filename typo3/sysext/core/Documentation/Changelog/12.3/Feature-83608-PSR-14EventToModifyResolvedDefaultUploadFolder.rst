.. include:: /Includes.rst.txt

.. _feature-83608-1669634686:

=======================================================================
Feature: #83608 - PSR-14 event to modify resolved default upload folder
=======================================================================

See :issue:`83608`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\Resource\Event\AfterDefaultUploadFolderWasResolvedEvent`
has been added, which allows the default upload folder to be modified after it has
been resolved for the current page or user.

The new event can be used as a better alternative to the
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getDefaultUploadFolder']`
hook, serving the same purpose.

The event features the following methods:

- :php:`getUploadFolder()` returns the currently resolved :php:`$uploadFolder`
- :php:`setUploadFolder()` sets a new upload folder
- :php:`getPid()` returns the PID of the record we fetch the upload folder for
- :php:`getTable()` returns the table name of the record we fetch the upload folder for
- :php:`getFieldName()` returns the field name of the record we fetch the upload folder for

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Resource\EventListener\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/after-default-upload-folder-was-resolved-event-listener'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Resources/EventListener/MyEventListener.php

    namespace MyVendor\MyExtension\Resource\EventListener;

    use TYPO3\CMS\Core\Resource\Event\AfterDefaultUploadFolderWasResolvedEvent;

    final class MyEventListener
    {
        public function __invoke(AfterDefaultUploadFolderWasResolvedEvent $event): void
        {
            $event->setUploadFolder($event->getUploadFolder()->getStorage()->getFolder('/'));
        }
    }


Impact
======

As resolving the event was moved from :php:`BackendUserAuthentication` to its own
:php:`DefaultUploadFolderResolver` class, this event is now the preferred way
of modifying the default upload folder.

.. index:: Backend, PHP-API, ext:core
