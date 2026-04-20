..  include:: /Includes.rst.txt

..  _feature-105649-1743710535:

======================================================
Feature: #105649 - New PSR-14 CustomFileSelectorsEvent
======================================================

See :issue:`105649`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Form\Event\CustomFileSelectorsEvent`
has been added. It is dispatched in
:php-short:`\TYPO3\CMS\Backend\Form\Container\FilesControlContainer`
during the rendering of selectors for relations to `sys_file_references`.

To modify the selectors used to add files, the following methods are
available:

*   :php:`getSelectors()`: Get all selectors
*   :php:`setSelectors()`: Set all selectors
*   :php:`getJavascriptModules()`: Get all JavaScript modules
*   :php:`setJavascriptModules()`: Set all JavaScript modules
*   :php:`getTableName()`: Get the table name of the current record
*   :php:`getFieldName()`: Get the field name of the element
*   :php:`getDatabaseRow()`: Get the raw database row
*   :php:`getFieldConfig()`: Get the TCA configuration of the current field
*   :php:`getFileExtensionFilter()`: Get the allowed and disallowed file
    extensions
*   :php:`getFormFieldIdentifier()`: Get the DOM object ID used in the form

Example
-------

The corresponding event listener class:

..  code-block:: php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Backend\Form\Event\CustomFileSelectorsEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    #[AsEventListener(identifier: 'my-extension/custom-file-selector')]
    final class CustomFileSelectorEventListener
    {
        public function __construct(
            private CustomDamFileSelector $damFileSelector,
        ) {}

        public function __invoke(CustomFileSelectorsEvent $event): void
        {
            $result = $this->damFileSelector->renderFileSelector(
                $event->getFormFieldIdentifier(),
            );
            $event->setSelectors(array_merge(
                $event->getSelectors(),
                $result['control'],
            ));
            $event->setJavascriptModules(array_merge(
                $event->getJavascriptModules(),
                $result['javaScriptModule'],
            ));
        }
    }

Impact
======

It is now possible to modify file selectors using the new PSR-14 event
:php-short:`\TYPO3\CMS\Backend\Form\Event\CustomFileSelectorsEvent`. This is
especially useful for integrating a DAM system.

..  index:: Backend, PHP-API, ext:backend
