.. include:: /Includes.rst.txt
formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Email.formEditor

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

          Email:
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
                    10: NaiveEmailOrEmpty
                700:
                  identifier: gridColumnViewPortConfiguration
                  templateName: Inspector-GridColumnViewPortConfigurationEditor
                  label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.label
                  configurationOptions:
                    viewPorts:
                      10:
                        viewPortIdentifier: xs
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.xs.label
                      20:
                        viewPortIdentifier: sm
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.sm.label
                      30:
                        viewPortIdentifier: md
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.md.label
                      40:
                        viewPortIdentifier: lg
                        label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.lg.label
                    numbersOfColumnsToUse:
                      label: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.label
                      propertyPath: 'properties.gridColumnClassAutoConfiguration.viewPorts.{@viewPortIdentifier}.numbersOfColumnsToUse'
                      fieldExplanationText: formEditor.elements.FormElement.editor.gridColumnViewPortConfiguration.numbersOfColumnsToUse.fieldExplanationText
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
                    50:
                      value: EmailAddress
                      label: formEditor.elements.TextMixin.editor.validators.EmailAddress.label
                9999:
                  identifier: removeButton
                  templateName: Inspector-RemoveElementEditor
              predefinedDefaults:
                defaultValue: ''
                validators:
                  -
                    identifier: EmailAddress
              propertyCollections:
                validators:
                  40:
                    identifier: EmailAddress
                    editors:
                      100:
                        identifier: header
                        templateName: Inspector-CollectionElementHeaderEditor
                        label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
              label: formEditor.elements.Email.label
              group: html5
              groupSorting: 100
              iconIdentifier: form-email
