.. include:: /Includes.rst.txt

.. _feature-107568-1759326362:

==============================================================
Feature: #107568 - PSR-14 Event before renderable is validated
==============================================================

See :issue:`107568`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Form\Event\BeforeRenderableIsValidatedEvent`
has been introduced which serves as an improved replacement for the now
:ref:`removed <breaking-107568-1759325068>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterSubmit']`.

The new event is dispatched just right before a renderable is validated.

The event provides the following public properties:

* :php:`$value`: The submitted value of the renderable
* :php:`$formRuntime`: The form runtime object (readonly)
* :php:`$renderable`: The renderable (readonly)
* :php:`$request`: The current request (readonly)

Example
=======

An example event listener could look like:

..  code-block:: php

    use TYPO3\CMS\Form\Event\BeforeRenderableIsValidatedEvent;

    class MyEventListener {

        #[AsEventListener(
            identifier: 'my-extension/before-renderable-is-validated',
        )]
        public function __invoke(BeforeRenderableIsValidatedEvent $event): void
        {
            $renderable = $event->renderable;
            if ($renderable->getType() !== 'AdvancedPassword') {
                return;
            }

            $elementValue = $event->value;
            if ($elementValue['password'] !== $elementValue['confirmation']) {
                $processingRule = $renderable->getRootForm()->getProcessingRule($renderable->getIdentifier());
                $processingRule->getProcessingMessages()->addError(
                    GeneralUtility::makeInstance(
                        Error::class,
                        GeneralUtility::makeInstance(TranslationService::class)->translate('validation.error.1556283177', null, 'EXT:form/Resources/Private/Language/locallang.xlf'),
                        1556283177
                    )
                );
            }
            $event->value = $elementValue['password'];
        }
    }

Impact
======

With the new PSR-14 :php:`BeforeRenderableIsValidatedEvent`, it's now
possible to prevent the deletion of a renderable and to add custom logic
based on the deletion.

.. index:: Backend, ext:form
