.. include:: /Includes.rst.txt

.. _feature-99118:

=====================================================================
Feature: #99118 - PSR-14 event to define whether files are selectable
=====================================================================

See :issue:`99118`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\ElementBrowser\Event\IsFileSelectableEvent`
has been introduced. It allows to define whether a file can be selected in the
file browser. Previously, this was only possible by overriding the
:php:`\TYPO3\CMS\Backend\ElementBrowser\FileBrowser->fileIsSelectableInFileList()`
method via an XCLASS.

The event features the following methods:

- :php:`getFile()`: Returns the :php:`\TYPO3\CMS\Core\Resource\FileInterface` in question
- :php:`isFileSelectable()`: Whether the file is allowed to be selected
- :php:`allowFileSelection()`: Allow selection of the file in question
- :php:`denyFileSelection()`: Deny selection of the file in question

..  note::

    The :php:`fileIsSelectableInFileList()` method allowed to access the image
    dimensions (`width` and `height`) via the second parameter :php:`$imgInfo`.
    Those information however can be retrieved directly from the :php:`FileInterface`
    in a more convenient way using the :php:`getProperty()` method. Therefore,
    the new Event does not provide this parameter explicitly.

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Backend\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/backend/modify-file-is-selectable'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Backend/MyEventListener.php

    namespace MyVendor\MyExtension\Backend;

    use TYPO3\CMS\Backend\ElementBrowser\Event\IsFileSelectableEvent;

    final class MyEventListener {

        public function __invoke(IsFileSelectableEvent $event): void
        {
            // Deny selection of "png" images
            if ($event->getFile()->getExtension() === 'png') {
                $event->denyFileSelection();
            }
        }
    }

Impact
======

It is now possible to decide whether a file can be selected in the
file browser, using an improved PSR-14 approach instead
of cross classing.

.. index:: Backend, ext:backend
