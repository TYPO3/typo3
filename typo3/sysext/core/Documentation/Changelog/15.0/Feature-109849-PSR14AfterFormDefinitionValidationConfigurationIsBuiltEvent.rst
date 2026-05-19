..  include:: /Includes.rst.txt

..  _feature-109849-1716115200:

=============================================================================
Feature: #109849 - PSR-14 Event after form definition validation config built
=============================================================================

See :issue:`109849`

Description
===========

A new PSR-14 event
:php:`\TYPO3\CMS\Form\Event\AfterFormDefinitionValidationConfigurationIsBuiltEvent`
has been introduced as a replacement for the now
:ref:`removed <breaking-109849-1716115200>` hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['buildFormDefinitionValidationConfiguration']`.

The event is dispatched by
:php:`\TYPO3\CMS\Form\Domain\Configuration\ConfigurationService` after the
form definition validation configuration has been built from the form editor
setup. It allows event listeners to add additional writable property paths for
custom form editor inspector editor implementations that do not declare their
writable property paths via the standard YAML configuration (e.g.
:yaml:`propertyPath`).

The event provides the following API:

*   :php:`getPrototypeName(): string` – The prototype name for which the
    configuration was built.
*   :php:`getConfiguration(): array` – The built validation configuration.
*   :php:`setConfiguration(array $configuration): void` – Replace the
    validation configuration.

Example
=======

An example event listener that adds an additional writable property path
for a custom form element type:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\AfterFormDefinitionValidationConfigurationIsBuiltEvent;

    #[AsEventListener(
        identifier: 'my-extension/after-form-definition-validation-configuration-is-built',
    )]
    final readonly class MyEventListener
    {
        public function __invoke(AfterFormDefinitionValidationConfigurationIsBuiltEvent $event): void
        {
            $configuration = $event->getConfiguration();
            $configuration['formElements']['MyCustomElement']['additionalPropertyPaths'][]
                = 'properties.my.custom.property';
            $event->setConfiguration($configuration);
        }
    }

Impact
======

With the new :php:`AfterFormDefinitionValidationConfigurationIsBuiltEvent`,
it is now possible to extend the form definition validation configuration
using the modern PSR-14 event listener API.

..  index:: Backend, ext:form
