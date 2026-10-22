..  include:: /Includes.rst.txt

..  _feature-111101-1792188000:

===============================================================
Feature: #111101 - PSR-14 event BeforeFinishersAreExecutedEvent
===============================================================

See :issue:`111101`

Description
===========

A new PSR-14 event
:php:`\TYPO3\CMS\Form\Event\BeforeFinishersAreExecutedEvent` has been
introduced. It is dispatched by
:php-short:`\TYPO3\CMS\Form\Domain\Runtime\FormRuntime` once a form has been
submitted successfully, after the last page has been validated and before the
finishers of the form are executed.

The event provides the following public properties:

*   :php:`$finishers`: The finishers that will be executed for this
    submission, in their order of execution. Listeners may remove, reorder or
    add finishers. The form definition itself is not modified.
*   :php:`$formRuntime`: The form runtime object (read-only).
*   :php:`$finisherContext`: The finisher context passed to every finisher
    (read-only). Variables added to its finisher variable provider are
    available to the finishers.

Example
=======

For the form with the identifier :yaml:`newsletterSignup`, a listener can skip
the built-in :php-short:`\TYPO3\CMS\Form\Domain\Finishers\EmailFinisher` when the submitted form contains a
:php:`sendConfirmationEmail` field set to :php:`no`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/MyBeforeFinishersAreExecutedEventListener.php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Domain\Finishers\EmailFinisher;
    use TYPO3\CMS\Form\Event\BeforeFinishersAreExecutedEvent;

    #[AsEventListener(identifier: 'my-extension/before-finishers-are-executed')]
    final readonly class MyBeforeFinishersAreExecutedEventListener
    {
        public function __invoke(BeforeFinishersAreExecutedEvent $event): void
        {
            if ($event->formRuntime->getIdentifier() !== 'newsletterSignup') {
                return;
            }

            $formValues = $event->finisherContext->getFormValues();
            if (($formValues['sendConfirmationEmail'] ?? 'yes') !== 'no') {
                return;
            }

            foreach ($event->finishers as $index => $finisher) {
                if (!$finisher instanceof EmailFinisher) {
                    continue;
                }

                unset($event->finishers[$index]);
                $event->finishers = array_values($event->finishers);
                return;
            }
        }
    }

Impact
======

With the new
:php-short:`\TYPO3\CMS\Form\Event\BeforeFinishersAreExecutedEvent`, it is now
possible to react to a successfully submitted form, for example to notify an
external system, and to adjust the finishers executed for a single submission
using the standard PSR-14 event listener mechanism.

..  index:: Frontend, PHP-API, ext:form
