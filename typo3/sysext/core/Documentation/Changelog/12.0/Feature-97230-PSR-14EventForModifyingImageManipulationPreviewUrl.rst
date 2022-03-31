.. include:: /Includes.rst.txt

===========================================================================
Feature: #97230 - PSR-14 Event for modifying image manipulation preview url
===========================================================================

See :issue:`97230`

Description
===========

A new PSR-14 Event :php:`\TYPO3\CMS\Backend\Form\Event\ModifyImageManipulationPreviewUrlEvent`
has been introduced which serves as a direct replacement for the now removed
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend/Form/Element/ImageManipulationElement']['previewUrl']`
hook.

It can be used to modify the preview url within the image manipulation element,
used e.g. for the :php:`crop` field of the :sql:`sys_file_reference` table.

As soon as a preview url is set, the image manipulation element will display
a corresponding button in the footer of the modal window, next to the
:guilabel:`Cancel` and :guilabel:`Accept` buttons. On click, the preview
url will be opened in a new window.

.. note::

    The elements crop variants will always be appended to the preview url
    as json encoded string, using the `cropVariants` parameter.

Next to the :php:`getPreviewUrl()` and :php:`setPreviewUrl()` does the new
PSR-14 Event feature the following methods:

- :php:`getDatabaseRow()`: Returns the whole database row for the corresponding record
- :php:`getFieldConfiguration()`: Returns the processed field configuration
- :php:`getFile()`: Returns the resolved file object

Example
=======

Registration of the Event in your extensions' :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\Backend\MyEventListener:
    tags:
      - name: event.listener
        identifier: 'my-package/backend/modify-imagemanipulation-previewurl'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Backend\Form\Event\ModifyImageManipulationPreviewUrlEvent

    final class ModifyLinkExplanationEventListener
    {
        public function __invoke(ModifyImageManipulationPreviewUrlEvent $event): void
        {
            $event->setPreviewUrl('https://example.com/some/preview/url');
        }
    }

Impact
======

It's now possible to modify the preview url for the image manipulation
element, using the new PSR-14 :php:`ModifyImageManipulationPreviewUrlEvent`.

.. index:: Backend, PHP-API, ext:backend
