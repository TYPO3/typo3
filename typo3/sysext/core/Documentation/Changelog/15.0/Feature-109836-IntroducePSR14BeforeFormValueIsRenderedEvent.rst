..  include:: /Includes.rst.txt

..  _feature-109836-1786049025:

=============================================================
Feature: #109836 - PSR-14 event before form value is rendered
=============================================================

See :issue:`109836`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Form\Event\BeforeFormValueIsRenderedEvent`
has been introduced. It is dispatched by
:php-short:`\TYPO3\CMS\Form\ViewHelpers\RenderFormValueViewHelper` right before
the value of a single form element is rendered, which means before the data is
assigned to the Fluid variable provider.

The event carries the following properties:

*   :php:`$data`: The data array that is assigned to the Fluid variable
    (:php:`element`, :php:`value`, :php:`processedValue`, :php:`isMultiValue`,
    or :php:`element` and :php:`isSection` for sections). It can be modified,
    and additional keys can be added for use in custom templates.
*   :php:`$element`: The form element being rendered (read-only).
*   :php:`$formRuntime`: The current form runtime (read-only), which gives
    access to the form definition and to the values of all other form elements.

This allows developers to introduce additional data processing for complex
individual form fields.

Example
=======

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Form/EventListener/BeforeFormValueIsRenderedEventListener.php

    <?php

    namespace MyVendor\MyExtension\Form\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\BeforeFormValueIsRenderedEvent;

    final class BeforeFormValueIsRenderedEventListener
    {
        #[AsEventListener('my_extension/form/before-form-value-is-rendered')]
        public function __invoke(BeforeFormValueIsRenderedEvent $event): void
        {
            if (!$event->element instanceof MyCustomSelect) {
                return;
            }
            if ($option = $this->customRepository->findOneByValue($event->data['value'])) {
                $event->data['processedValue'] = $option->getLabel();
            }
        }
    }

Impact
======

With this change, it is possible to do the required manipulation in a single
EventListener class instead of requiring to override multiple Fluid templates.

..  index:: PHP-API, ext:form
