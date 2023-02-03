.. include:: /Includes.rst.txt

.. _feature-99312:

=======================================================================
Feature: #99312 - PSR-14 Event for fetching YouTube/Vimeo preview image
=======================================================================

See :issue:`99312`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\Resource\OnlineMedia\Event\AfterVideoPreviewFetchedEvent`
has been introduced. The purpose of this event is to modify the preview file
of online media previews (like YouTube and Vimeo).
If, for example, a processed file is bad (blank or outdated), this event can be
used to modify and/or update the preview file.

The event features the following methods:

-   :php:`getFile()`: Returns the :php:`\TYPO3\CMS\Core\Resource\File` in question
-   :php:`getOnlineMediaId()`: Returns the video ID
-   :php:`getPreviewImageFilename()`: Returns the filename of the preview image
-   :php:`setPreviewImageFilename()`: Set the filename for the preview image

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\EventListener\ExampleEventListener:
        tags:
          - name: event.listener
            identifier: 'exampleEventListener'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ExampleEventListener.php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Resource\OnlineMedia\Event\AfterVideoPreviewFetchedEvent;

    final class ExampleEventListener
    {
        public function __invoke(AfterVideoPreviewFetchedEvent $event): void
        {
            $event->setPreviewImageFilename(
                '/var/www/websites/typo3temp/assets/online_media/new-preview-image.jpg'
            );
            // An extension could use this to fetch new images again.
        }
    }

Impact
======

It is now possible to change the filename for the preview image of a YouTube
or Vimeo thumbnail image.

.. index:: Backend, Frontend, ext:core
