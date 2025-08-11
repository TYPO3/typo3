..  include:: /Includes.rst.txt

..  _feature-107256-1754918566:

==================================================================
Feature: #107256 - PSR-14 Event to modify options in EmailFinisher
==================================================================

See :issue:`107256`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Form\Event\BeforeEmailFinisherInitializedEvent`
has been introduced. This event is dispatched before the :php:`EmailFinisher` is
initialized and allows listeners to modify the finisher options dynamically.

This enables developers to customize email behavior programmatically, such as:

-   Setting alternative recipients based on frontend user permissions
-   Modifying the email subject or content dynamically
-   Replacing recipients with developer email addresses in test environments
-   Adding or removing CC/BCC recipients conditionally
-   Customizing reply-to addresses

The event provides access to both the finisher context (read-only) and the
options array, allowing for comprehensive manipulation of the email configuration.

To modify the :php:`EmailFinisher` options, the following methods are available:

-   :php:`getFinisherContext()`: Returns the :php:`FinisherContext` containing form runtime and request information
-   :php:`getOptions()`: Returns the current finisher options array
-   :php:`setOptions()`: Allows setting the modified options array

Example
=======

The corresponding event listener class:

..  code-block:: php

    <?php

    namespace Vendor\MyPackage\Form\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\BeforeEmailFinisherInitializedEvent;

    final class BeforeEmailFinisherInitializedEventListener
    {
        #[AsEventListener('my-package/form/modify-email-finisher-options')]
        public function __invoke(BeforeEmailFinisherInitializedEvent $event): void
        {
            $options = $event->getOptions();
            $context = $event->getFinisherContext();

            // Overwrite recipients based on FormContext
            if ($context->getFormRuntime()->getFormDefinition()->getIdentifier() === 'my-form-123') {
                $options['recipients'] = ['user@example.org' => 'John Doe'];
            }

            // Modify subject dynamically
            $options['subject'] = 'Custom subject: ' . ($options['subject'] ?? '');

            // Clear CC and BCC recipients
            $options['replyToRecipients'] = [];
            $options['blindCarbonCopyRecipients'] = [];

            $event->setOptions($options);
        }
    }


Impact
======

It's now possible to dynamically modify :php:`EmailFinisher` options before
email processing begins, using the new PSR-14 event :php:`BeforeEmailFinisherInitializedEvent`.
This provides developers with comprehensive control over email configuration
without needing to extend or override the :php:`EmailFinisher` class.

.. index:: PHP-API, ext:form
