..  include:: /Includes.rst.txt

..  _feature-108049-1733919226:

==================================================
Feature: #108049 - Modernized translation workflow
==================================================

See :issue:`108049`

Description
===========

A modernized and extensible translation workflow has been introduced in the
TYPO3 backend. The new architecture replaces the previous monolithic
localization implementation with a step-based wizard system that guides
editors through the translation process. Previously available only in the
Page module, the translation wizard is now the default interface for all
translation operations across backend modules.

The backend now consistently opens the new translation wizard whenever users
initiate a translation operation, providing a unified experience across all
modules. The wizard guides users through the translation process in multiple
steps, automatically advancing when no user input is required. This streamlined
approach ensures users only interact with steps that require configuration or
confirmation.

Users can choose between two translation modes: **Translate** (creates a
connected translation) and **Copy** (creates an independent copy in free mode).
To maintain consistency, the wizard will only offer the translation mode that
matches existing translations for a record, preventing mixed modes.

API for extension developers
-----------------------------

..  note::
    The localization handler and finisher APIs are currently marked as
    :php:`@internal` and may change before the LTS release. While it is
    technically possible to implement custom localization handlers and finishers,
    the APIs are still being evaluated and refined. Extension developers should
    be aware that breaking changes to the interfaces may occur in minor releases
    until the APIs are stabilized for LTS.

    We are actively seeking feedback from extension developers to ensure the APIs
    meet real-world requirements and can be stabilized for the LTS release. If you
    implement custom handlers or finishers, please share your experience and
    suggestions with the TYPO3 community.

The new :php:`LocalizationHandlerRegistry` provides a flexible architecture
for registering and managing different translation strategies. Handlers
implement the :php:`LocalizationHandlerInterface` and are automatically
registered via autoconfiguration with the :yaml:`backend.localization.handler`
tag. This makes the localization system extensible, allowing custom handlers to
be added for specialized translation workflows (e.g., integration with translation
services, AI-powered translation, or custom business logic).

The :php:`LocalizationFinisherInterface` defines how the wizard completes after
a successful translation operation. Finishers can be customized by localization
handlers to provide the most appropriate completion behavior for their specific
workflow, such as redirecting to a specific page or reloading the current view.

Custom localization handlers
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Extensions can provide custom localization handlers by implementing the
:php:`LocalizationHandlerInterface`:

..  code-block:: php

    namespace Vendor\MyExtension\Localization;

    use TYPO3\CMS\Backend\Localization\LocalizationHandlerInterface;
    use TYPO3\CMS\Backend\Localization\LocalizationMode;
    use TYPO3\CMS\Backend\Localization\LocalizationResult;

    final class MyCustomHandler implements LocalizationHandlerInterface
    {
        public function getIdentifier(): string
        {
            return 'my-custom-handler';
        }

        public function getLabel(): string
        {
            return 'my_extension.messages:handler.label';
        }

        public function getDescription(): string
        {
            return 'my_extension.messages:handler.description';
        }

        public function getIconIdentifier(): string
        {
            return 'my-extension-icon';
        }

        public function isAvailable(LocalizationInstructions $instructions): bool
        {
            // Return true if this handler should be available for the given context
            return $instructions->mainRecordType === 'my_table';
        }

        public function processLocalization(
            LocalizationInstructions $instructions
        ): LocalizationResult {
            // Implement your custom localization logic here
            // Return a LocalizationResult with success status and finisher
        }
    }

The handler will be automatically registered via autoconfiguration when it
implements :php:`LocalizationHandlerInterface`. No manual service configuration
is required

Custom finishers
~~~~~~~~~~~~~~~~

Extensions can create custom finishers by implementing the
:php:`LocalizationFinisherInterface` and providing a corresponding JavaScript
module to handle the frontend logic:

..  code-block:: php

    namespace Vendor\MyExtension\Localization\Finisher;

    use TYPO3\CMS\Backend\Localization\Finisher\LocalizationFinisherInterface;

    final class MyCustomFinisher implements LocalizationFinisherInterface
    {
        public function getType(): string
        {
            return 'my-custom-finisher';
        }

        public function getModule(): string
        {
            return '@vendor/my-extension/localization/finisher/my-custom-finisher.js';
        }

        public function getData(): array
        {
            return [
                'customData' => 'value',
            ];
        }

        public function getLabels(): array
        {
            return [
                'successMessage' => 'my_extension.messages:finisher.success',
            ];
        }

        public function jsonSerialize(): array
        {
            return [
                'type' => $this->getType(),
                'module' => $this->getModule(),
                'data' => $this->getData(),
                'labels' => $this->getLabels(),
            ];
        }
    }

The corresponding JavaScript module implements the finisher logic. The finisher
is executed in the final step of the wizard and can define custom rendering and
behavior. Users can skip the finisher step if no interaction is required.

..  important::
    If your finisher provides custom action buttons, bind the main action to the
    :js:`execute()` method. This ensures the same action is performed both when
    users click your custom button and when they click "Finalize" in the wizard's
    bottom action bar.

..  code-block:: typescript

    import LocalizationFinisher from '@typo3/backend/localization/localization-finisher';
    import { html, type TemplateResult } from 'lit';

    class MyCustomFinisher extends LocalizationFinisher {
      public render(): TemplateResult {
        // Define what is rendered in the finisher step
        // Can return custom UI elements, messages, actions, etc.
        return html`
          <p>${this.finisher.labels.successMessage}</p>
          <button @click=${() => this.execute()}>
            Perform Action
          </button>
        `;
      }

      public async execute(): Promise<void> {
        // Execute finisher logic (e.g., redirect, reload, show notification)
        // This is called when the wizard completes or when the user clicks
        // the custom action button above
        const customData = this.finisher.data.customData;
        console.log('Finisher executing with data:', customData);

        // Perform your custom finisher action here
        // For example: redirect, reload, show notification, etc.
      }
    }

    export default MyCustomFinisher;

Impact
======

The new translation workflow provides a consistent and intuitive user experience
across all backend modules. Editors benefit from clear step-by-step guidance
through the translation process, with the wizard automatically adapting to the
context and showing only relevant options.

Extension developers can register custom localization handlers to integrate
specialized translation workflows, such as connections to external translation
services or AI-powered translation tools.

..  index:: Backend, JavaScript, ext:backend
