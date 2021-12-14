.. include:: /Includes.rst.txt
formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Number.formEditor

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Recommended

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2-

          Number:
            formEditor:
              editors:
                100:
                  identifier: header
                  templateName: Inspector-FormElementHeaderEditor
                200:
                  identifier: label
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.label.label
                  propertyPath: label
               230:
                  identifier: elementDescription
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.FormElement.editor.elementDescription.label
                  propertyPath: properties.elementDescription
                400:
                  identifier: placeholder
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.placeholder.label
                  propertyPath: properties.fluidAdditionalAttributes.placeholder
                  doNotSetIfPropertyValueIsEmpty: true
                500:
                  identifier: defaultValue
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.defaultValue.label
                  propertyPath: defaultValue
                  propertyValidators:
                    10: IntegerOrEmpty
                700:
                  identifier: step
                  templateName: Inspector-TextEditor
                  label: formEditor.elements.TextMixin.editor.step.label
                  propertyPath: properties.fluidAdditionalAttributes.step
                  propertyValidators:
                    10: Integer
                800:
                  identifier: requiredValidator
                  templateName: Inspector-RequiredValidatorEditor
                  label: formEditor.elements.FormElement.editor.requiredValidator.label
                  validatorIdentifier: NotEmpty
                  propertyPath: properties.fluidAdditionalAttributes.required
                  propertyValue: required
                900:
                  identifier: validators
                  templateName: Inspector-ValidatorsEditor
                  label: formEditor.elements.TextMixin.editor.validators.label
                  selectOptions:
                    10:
                      value: ''
                      label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                    60:
                      value: Number
                      label: formEditor.elements.Number.editor.validators.Number.label
                    80:
                      value: NumberRange
                      label: formEditor.elements.TextMixin.editor.validators.NumberRange.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                defaultValue: ''
                properties:
                  fluidAdditionalAttributes:
                    step: 1
                validators:
                  -
                    identifier: Number
              propertyCollections:
                validators:
                  60:
                    identifier: Number
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.Number.editor.header.label
                  70:
                    identifier: NumberRange
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.NumberRange.editor.header.label
                      200:
                        identifier: minimum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                        propertyPath: options.minimum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.min
                      300:
                        identifier: maximum
                        templateName: Inspector-TextEditor
                        label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                        propertyPath: options.maximum
                        propertyValidators:
                          10: Integer
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.max
                      9999:
                        identifier: removeButton
                        templateName: Inspector-RemoveElementEditor
              label: formEditor.elements.Number.label
              group: html5
              groupSorting: 400
              iconIdentifier: form-number
