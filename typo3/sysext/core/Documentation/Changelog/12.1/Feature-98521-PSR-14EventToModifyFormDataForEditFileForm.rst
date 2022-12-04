.. include:: /Includes.rst.txt

.. _feature-98521-1664890745:

=====================================================================
Feature: #98521 - PSR-14 event to modify form data for edit file form
=====================================================================

See :issue:`98521`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Filelist\Event\ModifyEditFileFormDataEvent`
has been added, which allows to modify the form data used to render the
file edit form in the :guilabel:`File > Filelist` module using
:ref:`FormEngine data compiling <t3coreapi:FormEngine-DataCompiling>`.

The new event can be used as an improved alternative for the removed
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook']`
hook.

The event features the following methods:

- :php:`getFormData()`: Returns the current :php:`$formData` array
- :php:`setFormData()`: Sets the :php:`$formData` array
- :php:`getFile()`: Returns the corresponding :php:`\TYPO3\CMS\Core\Resource\FileInterface`
- :php:`getRequest()`: Returns the full PSR-7 :php:`\Psr\Http\Message\ServerRequestInterface`

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\EventListener\ModifyEditFileFormDataEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/modify-edit-file-form-data-event-listener'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Filelist\Event\ModifyEditFileFormDataEvent;

    final class ModifyEditFileFormDataEventListener
    {
        public function __invoke(ModifyEditFileFormDataEvent $event): void
        {
            // Get current form data
            $formData = $event->getFormData();

            // Change TCA "renderType" based on the file extension
            $fileExtension = $event->getFile()->getExtension();
            if ($fileExtension === 'ts') {
                $formData['processedTca']['columns']['data']['config']['renderType'] = 'tsRenderer';
            }

            // Set updated form data
            $event->setFormData($formData);
        }
    }

Impact
======

It is now possible to modify the whole :php:`$formData` array used to generate
the edit file form in the :guilabel:`File > Filelist` module, while having the
resolved :php:`FileInterface` and the current PSR-7 :php:`ServerRequestInterface`
available.

.. index:: Backend, PHP-API, ext:filelist
