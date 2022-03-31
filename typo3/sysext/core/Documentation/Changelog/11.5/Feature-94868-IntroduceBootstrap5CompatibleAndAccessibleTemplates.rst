.. include:: /Includes.rst.txt

===========================================================================
Feature: #94868 - Introduce Bootstrap 5 compatible and accessible templates
===========================================================================

See :issue:`94868`

Description
===========

Until now, CSS classes of the frontend templates and partials of the Form
Framework were not consistently included in the form configuration. So far
some classes were present in the form configuration, others were hardcoded
only in the Fluid templates.

This situation has now been fixed for the Bootstrap 5 compatible template
variants stored in :file:`EXT:form/Resources/Private/Frontend/Version2`.
All CSS classes are consistently defined in the form configuration.

This simplifies the integration of the frontend. The change makes it easier for
integrators to make upgrades of the frontend framework. In most cases, it is
now no longer necessary to override a Fluid template for changes to classes.
Instead, it is only necessary to add the appropriate CSS classes to the
form configuration.

In order not to be breaking, by default the templates are still rendered
as they used to be.

To use the new Bootstrap 5 compatible templates the form rendering option
:yaml:`templateVariant` must be set from :yaml:`version1` to :yaml:`version2` in your form setup:

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              formElementsDefinition:
                Form:
                  renderingOptions:
                    templateVariant: version2

The CSS classes for the Bootstrap 5 compatible templates are defined
in the variant with the name :yaml:`template-variant` for each form element.
This is an example of the text element:

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              formElementsDefinition:
                Text:
                  variants:
                    -
                      identifier: template-variant
                      condition: 'getRootFormProperty("renderingOptions.templateVariant") == "version2"'
                      properties:
                        containerClassAttribute: 'form-element form-element-text mb-3'
                        elementClassAttribute: form-control
                        labelClassAttribute: form-label

To be able to access the configuration of the root element (type "Form")
in conditions, a new function :php:`getRootFormProperty()` has been introduced,
which can be used to access the properties of the "Form" element.
In the context of the "template-variant" variants this is used to determine
the template variant defined on the "Form" element in order to change the
CSS configuration properties or to add new ones.

In the course of Bootstrap 5 compatibility two new breakpoints "xl" and
"xxl" were added to the grid configuration which are also available in the
form editor.

.. index:: Frontend, ext:form
