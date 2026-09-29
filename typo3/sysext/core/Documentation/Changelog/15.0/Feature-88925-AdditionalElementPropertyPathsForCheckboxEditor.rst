..  include:: /Includes.rst.txt

..  _feature-88925-1790683200:

=====================================================================
Feature: #88925 - Additional element property paths in CheckboxEditor
=====================================================================

See :issue:`88925`

Description
===========

The inspector editor `Inspector-CheckboxEditor` of the form editor now
supports the options `additionalElementPropertyPaths` and
`doNotSetIfPropertyValueIsEmpty`, which have been available for the
`Inspector-TextEditor` so far.

The state of the checkbox is written as `true` or `false` to all property
paths configured in `additionalElementPropertyPaths`, in addition to the
property configured in `propertyPath`. If `doNotSetIfPropertyValueIsEmpty`
is enabled, these additional properties are removed instead of being set to
`false` when the checkbox is unchecked.

This is useful for custom validators or form elements whose options are
configured with a checkbox and are needed as element property as well, for
example to pass them to the frontend.

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Form/MyFormSet/config.yaml

    prototypes:
      standard:
        formElementsDefinition:
          Text:
            formEditor:
              propertyCollections:
                validators:
                  100:
                    identifier: MyValidator
                    editors:
                      200:
                        identifier: strict
                        templateName: Inspector-CheckboxEditor
                        label: 'Strict mode'
                        propertyPath: options.strict
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.data-strict
                        doNotSetIfPropertyValueIsEmpty: true

Impact
======

Custom configurations of the `Inspector-CheckboxEditor` can write the state
of the checkbox to additional properties of the form element.

..  index:: Backend, JavaScript, ext:form
