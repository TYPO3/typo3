.. include:: /Includes.rst.txt

.. _feature-97230:

===========================================================================
Feature: #97230 - PSR-14 event for modifying image manipulation preview URL
===========================================================================

See :issue:`97230`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Form\Event\ModifyImageManipulationPreviewUrlEvent`
has been introduced which serves as a direct replacement for the now removed
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend/Form/Element/ImageManipulationElement']['previewUrl']`
hook.

It can be used to modify the preview URL within the image manipulation element,
used e.g. for the :php:`crop` field of the :sql:`sys_file_reference` table.

As soon as a preview URL is set, the image manipulation element will display
a corresponding button in the footer of the modal window, next to the
:guilabel:`Cancel` and :guilabel:`Accept` buttons. On click, the preview
URL will be opened in a new window.

.. note::

    The element's crop variants will always be appended to the preview URL
    as JSON-encoded string, using the `cropVariants` parameter.

Next to the :php:`getPreviewUrl()` and :php:`setPreviewUrl()` the new
PSR-14 event feature the following methods:

- :php:`getDatabaseRow()`: Returns the whole database row for the corresponding record
- :php:`getFieldConfiguration()`: Returns the processed field configuration
- :php:`getFile()`: Returns the resolved file object

Example
=======

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\Backend\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/backend/modify-imagemanipulation-previewurl'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Form\Event\ModifyImageManipulationPreviewUrlEvent;

    final class MyEventListener
    {
        public function __invoke(ModifyImageManipulationPreviewUrlEvent $event): void
        {
            $event->setPreviewUrl('https://example.com/some/preview/url');
        }
    }

Impact
======

It's now possible to modify the preview URL for the image manipulation
element, using the new PSR-14 event :php:`ModifyImageManipulationPreviewUrlEvent`.

.. index:: Backend, PHP-API, ext:backend
