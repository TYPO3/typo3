..  include:: /Includes.rst.txt

..  _feature-110331-1785369600:

==========================================================================
Feature: #110331 - Improved form template picker with icon and description
==========================================================================

See :issue:`110331`

Description
===========

The "Create new form" wizard in the EXT:form backend module has been improved.
The previous two-step flow — first choosing between *Blank* and *Predefined*
mode, then selecting a template from a dropdown — has been replaced by a single,
unified template picker.

All available form templates are now presented as a card-based grid using the
:html:`<typo3-backend-new-record-wizard>` component.  Each card displays the
template's label, an icon, and an optional short description.  Selecting a card
immediately advances the wizard to the next step.

To support the new UI, two optional keys have been added to the
`newFormTemplates.*` configuration:

`iconIdentifier`
    A TYPO3 icon identifier shown on the template card.
    Falls back to `form-page` when omitted.

`description`
    A short description text displayed below the template label on the card.
    Supports translation keys resolved against the configured
    `translationFiles`.

Example configuration in :file:`EXT:my_extension/Configuration/Form/formManager.yaml`:

..  code-block:: yaml

    formManager:
      selectablePrototypesConfiguration:
        100:
          identifier: standard
          label: formManager.selectablePrototypesConfiguration.standard.label
          newFormTemplates:
            100:
              templatePath: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/BlankForm.yaml'
              label: formManager.selectablePrototypesConfiguration.standard.newFormTemplates.blankForm.label
              description: formManager.selectablePrototypesConfiguration.standard.newFormTemplates.blankForm.description
              iconIdentifier: 'apps-pagetree-page-default'
            200:
              templatePath: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/SimpleContactForm.yaml'
              label: formManager.selectablePrototypesConfiguration.standard.newFormTemplates.simpleContactForm.label
              description: formManager.selectablePrototypesConfiguration.standard.newFormTemplates.simpleContactForm.description
              iconIdentifier: 'form-page'

Impact
======

Integrators can enrich form template entries with an `iconIdentifier` and
a `description` to improve the editor experience in the "Create new form"
wizard.  Both keys are optional; existing configurations continue to work
without changes.

..  index:: Backend, ext:form
