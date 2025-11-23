..  include:: /Includes.rst.txt

..  _feature-107568-1759326362:

==============================================================
Feature: #107568 - PSR-14 event before renderable is validated
==============================================================

See :issue:`107568`

Description
===========

A new PSR-14 event :php-short:`\TYPO3\CMS\Form\Event\BeforeRenderableIsValidatedEvent`
has been introduced. It serves as an improved replacement for the now
:ref:`removed <breaking-107568-1759325068>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterSubmit']`.

The new event is dispatched right before a renderable is validated.

The event provides the following public properties:

*   :php:`$value`: The submitted value of the renderable.
*   :php:`$formRuntime`: The form runtime object (read-only).
*   :php:`$renderable`: The renderable (read-only).
*   :php:`$request`: The current request (read-only).

Example
=======

An example event listener could look like this:

..  code-block:: php
    :caption: Example event listener class

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Error\Error;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Core\Localization\TranslationService;
    use TYPO3\CMS\Form\Event\BeforeRenderableIsValidatedEvent;

    final class BeforeRenderableIsValidatedEventListener
    {
        #[AsEventListener('my-extension/before-renderable-is-validated')]
        public function __invoke(BeforeRenderableIsValidatedEvent $event): void
        {
            $renderable = $event->renderable;
            if ($renderable->getType() !== 'AdvancedPassword') {
                return;
            }

            $elementValue = $event->value;
            if ($elementValue['password'] !== $elementValue['confirmation']) {
                $processingRule = $renderable
                    ->getRootForm()
                    ->getProcessingRule($renderable->getIdentifier());

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

With the new :php-short:`\TYPO3\CMS\Form\Event\BeforeRenderableIsValidatedEvent`,
it is now possible to modify or validate the value of a renderable element
before TYPO3 Core performs its built-in validation logic. This allows
extensions to inject custom validation rules or preprocessing steps before
standard validation runs.

..  index:: Backend, ext:form
