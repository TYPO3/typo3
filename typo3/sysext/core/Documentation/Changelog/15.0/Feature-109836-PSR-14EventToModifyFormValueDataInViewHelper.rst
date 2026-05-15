..  include:: /Includes.rst.txt

..  _feature-109836-1786049025:

========================================================================
Feature: #109836 - PSR-14 event to modify form value data in view helper
========================================================================

See :issue:`109836`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Form\Event\ModifyFormValueForRenderingEvent`
has been introduced. This event is dispatched inside
:php-short:`\TYPO3\CMS\Form\ViewHelpers\RenderFormValueViewHelper` before the
data is assigned to the variable provider.

This allows developers to introduce additional data processing for complex
individual form fields.

Example
=======

The corresponding event listener class:

..  code-block:: php
    :caption: Example event listener class

    <?php

    namespace MyVendor\MyExtension\Form\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\ModifyFormValueForRenderingEvent;

    final class ModifyFormValueForRenderingEventListener
    {
        #[AsEventListener('my_extension/form/modify-form-value-for-rendering')]
        public function __invoke(ModifyFormValueForRenderingEvent $event): void
        {
            $data = $event->getData();
            if (($data['element'] ?? null) instanceof MyCustomSelect) {
                if ($option = $this->customRepository->findOneByValue($data['value'])) {
                    $data['processedValue'] = $option->getLabel();
                    $event->setData($data);
                }
            }
        }
    }

Impact
======

With this change, it is possible to do the required manipulation in a single
EventListener class instead of requiring to override multiple Fluid templates.

..  index:: PHP-API, ext:form
