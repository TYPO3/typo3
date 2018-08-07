.. include:: ../../Includes.txt

===============================================================
Important: #85044 - Filter disallowed properties in form editor
===============================================================

See :issue:`85044`

Description
===========

The form editor save and preview actions now check the submitted form definition against configured possibilities within the form editor setup.

If a form element property is defined in the form editor setup then it means that the form element property can be written by the form editor.
A form element property can be written if the property path is defined within the following form editor properties:

* :yaml:`formElementsDefinition.<formElementType>.formEditor.editors.<index>.propertyPath`
* :yaml:`formElementsDefinition.<formElementType>.formEditor.editors.<index>.*.propertyPath`
* :yaml:`formElementsDefinition.<formElementType>.formEditor.editors.<index>.additionalElementPropertyPaths`
* :yaml:`formElementsDefinition.<formElementType>.formEditor.propertyCollections.<finishers|validators>.<index>.editors.<index>.additionalElementPropertyPaths`

If a form editor property :yaml:`templateName` is "Inspector-PropertyGridEditor" or "Inspector-MultiSelectEditor" or "Inspector-ValidationErrorMessageEditor"
it means that the form editor property :yaml:`propertyPath` is interpreted as a so called "multiValueProperty".
A "multiValueProperty" can contain any subproperties relative to the value from :yaml:`propertyPath` which are valid.
If :yaml:`formElementsDefinition.<formElementType>.formEditor.editors.<index>.templateName` = "Inspector-PropertyGridEditor" and :yaml:`formElementsDefinition.<formElementType>.formEditor.editors.<index>.propertyPath` = "options.xxx"
then (for example) "options.xxx.yyy" is a valid property path to write.

If a form elements finisher|validator property is defined in the form editor setup then it means that the form elements finisher|validator property can be written by the form editor.
A form elements finisher|validator property can be written if the property path is defined within the following form editor properties:

* :yaml:`formElementsDefinition.<formElementType>.formEditor.propertyCollections.<finishers|validators>.<index>.editors.<index>.propertyPath`
* :yaml:`formElementsDefinition.<formElementType>.formEditor.propertyCollections.<finishers|validators>.<index>.editors.<index>.*.propertyPath`

If a form elements finisher|validator property :yaml:`templateName` is "Inspector-PropertyGridEditor" or "Inspector-MultiSelectEditor" or "Inspector-ValidationErrorMessageEditor"
it means that the form editor property :yaml:`propertyPath` is interpreted as a so called "multiValueProperty".
A "multiValueProperty" can contain any subproperties relative to the value from :yaml:`propertyPath` which are valid.
If :yaml:`formElementsDefinition.<formElementType>.formEditor.propertyCollections.<finishers|validators>.<index>.editors.<index>.templateName` = "Inspector-PropertyGridEditor"
and :yaml:`formElementsDefinition.<formElementType>.formEditor.propertyCollections.<finishers|validators>.<index>.editors.<index>.propertyPath` = "options.xxx"
that (for example) "options.xxx.yyy" is a valid property path to write.

If you use a custom form editor JavaScript "inspector editor" implementation (see https://docs.typo3.org/typo3cms/extensions/form/Concepts/FormEditor/Index.html#inspector)
which does not define the writable property paths by one of the above described inspector editor properties (e.g :yaml:`propertyPath`) within the form setup,
you must provide the writable property paths with a hook. Otherwise the editor will fail when saving.


Connect to the hook:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['buildFormDefinitionValidationConfiguration'][] = \Vendor\YourNamespace\YourClass::class;

Use the hook:

The hook must return an array with a set of ValidationDto objects.

.. code-block:: php

    /**
     * @param \TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto $validationDto
     * @return array
     */
    public function addAdditionalPropertyPaths(\TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto $validationDto): array
    {
        // Create a ValidationDto object for the form element type "Form" (:yaml:`formElementsDefinition.<formElementType>`).
        $formValidationDto = $validationDto->withFormElementType('Form');
        // Create a ValidationDto object for the finishers for the form element type "Form".
        $formFinishersValidationDto = $formValidationDto->withPropertyCollectionName('finishers');

        // Create a ValidationDto object for the form element type "Text" (:yaml:`formElementsDefinition.<formElementType>`).
        $textValidationDto = $validationDto->withFormElementType('Text');
        // Create a ValidationDto object for the validators for the form element type "Text".
        $textValidatorsValidationDto = $textValidationDto->withPropertyCollectionName('validators');

        // Create a ValidationDto object for the form element type "Date" (:yaml:`formElementsDefinition.<formElementType>`).
        $dateValidationDto = $validationDto->withFormElementType('Date');

        $propertyPaths = [
            // Register the property :yaml:`renderingOptions.my.custom.property` for the form element type "Form".
            // This property can now be written by the form editor.
            $formValidationDto->withPropertyPath('renderingOptions.my.custom.property'),

            // Register the property :yaml:`options.custom.property` for the finisher "MyCustomFinisher" for the form element type "Form".
            // "MyCustomFinisher" must be equal to the identifier property from
            // your custom inspector editor (:yaml:`formElementsDefinition.Form.formEditor.propertyCollections.finishers.<index>.editors.<index>.identifier`)
            // This property can now be written by the form editor.
            $formFinishersValidationDto->withPropertyCollectionElementIdentifier('MyCustomFinisher')->withPropertyPath('options.custom.property'),

            // Register the properties :yaml:`properties.my.custom.property` and :yaml:`properties.my.other.custom.property` for the form element type "Text".
            // This property can now be written by the form editor.
            $textValidationDto->withPropertyPath('properties.my.custom.property'),
            $textValidationDto->withPropertyPath('properties.my.other.custom.property'),

            // Register the property :yaml:`options.custom.property` for the validator "CustomValidator" for the form element type "Text".
            // "CustomValidator" must be equal to the identifier property from
            // your custom inspector editor (:yaml:`formElementsDefinition.Text.formEditor.propertyCollections.validators.<index>.editors.<index>.identifier`)
            // This property can now be written by the form editor.
            $textValidatorsValidationDto->withPropertyCollectionElementIdentifier('CustomValidator')->withPropertyPath('options.custom.property'),

            $textValidatorsValidationDto->withPropertyCollectionElementIdentifier('AnotherCustomValidator')->withPropertyPath('options.other.custom.property'),

            $dateValidationDto->withPropertyPath('properties.custom.property'),
            // ..
        ];

        return $propertyPaths;
    }


.. index:: Backend, ext:form
