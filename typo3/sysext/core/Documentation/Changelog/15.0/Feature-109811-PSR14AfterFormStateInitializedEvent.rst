..  include:: /Includes.rst.txt

..  _feature-109811-1759230000:

==============================================================
Feature: #109811 - PSR-14 event AfterFormStateInitializedEvent
==============================================================

See :issue:`109811`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Form\Event\AfterFormStateInitializedEvent`
has been introduced. It serves as an improved replacement for the now
:ref:`removed <breaking-109811-1759230001>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterFormStateInitialized']`.

The new event is dispatched by :php:`FormRuntime` after the :php:`FormState`
has been restored from the request. At this point both the form state
(submitted values) and the static form definition are available, which makes
it particularly suitable for enriching components that need runtime data (e.g.
configuring property mapping for file uploads).

The event provides the following public properties:

*   :php:`$formRuntime`: The form runtime object (read-only).
*   :php:`$request`: The current request (read-only).

Example
=======

An example event listener could look like this:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/MyAfterFormStateInitializedEventListener.php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\AfterFormStateInitializedEvent;

    #[AsEventListener(identifier: 'my-extension/after-form-state-initialized')]
    final readonly class MyAfterFormStateInitializedEventListener
    {
        public function __invoke(AfterFormStateInitializedEvent $event): void
        {
            // Access $event->formRuntime->getFormState() here
        }
    }

Impact
======

With the new :php-short:`\TYPO3\CMS\Form\Event\AfterFormStateInitializedEvent`,
it is now possible to react to the form state being fully initialized using the
standard PSR-14 event listener mechanism.

..  index:: Frontend, ext:form
