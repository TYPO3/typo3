formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Textarea.formEditor

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

         Textarea:
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
                 compatibilityPropertyPath: properties.placeholder
                 doNotSetIfPropertyValueIsEmpty: true
               500:
                 identifier: defaultValue
                 templateName: Inspector-TextEditor
                 label: formEditor.elements.TextMixin.editor.defaultValue.label
                 propertyPath: defaultValue
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
                  20:
                    value: Alphanumeric
                    label: formEditor.elements.TextMixin.editor.validators.Alphanumeric.label
                  30:
                    value: Text
                    label: formEditor.elements.TextMixin.editor.validators.Text.label
                  40:
                    value: StringLength
                    label: formEditor.elements.TextMixin.editor.validators.StringLength.label
                  60:
                    value: Integer
                    label: formEditor.elements.TextMixin.editor.validators.Integer.label
                  70:
                    value: Float
                    label: formEditor.elements.TextMixin.editor.validators.Float.label
                  80:
                    value: NumberRange
                    label: formEditor.elements.TextMixin.editor.validators.NumberRange.label
                  90:
                    value: RegularExpression
                    label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label
               9999:
                 identifier: removeButton
                 templateName: Inspector-RemoveElementEditor
             predefinedDefaults:
               defaultValue: ''
             propertyCollections:
               validators:
                 10:
                   identifier: Alphanumeric
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.TextMixin.validators.Alphanumeric.editor.header.label
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                 20:
                   identifier: Text
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.TextMixin.validators.Text.editor.header.label
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                 30:
                   identifier: StringLength
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.TextMixin.validators.StringLength.editor.header.label
                     200:
                       identifier: minimum
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                       propertyPath: options.minimum
                       propertyValidators:
                         10: Integer
                       additionalElementPropertyPaths:
                         10: properties.fluidAdditionalAttributes.minlength
                     300:
                       identifier: maximum
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                       propertyPath: options.maximum
                       propertyValidators:
                         10: Integer
                       additionalElementPropertyPaths:
                         10: properties.fluidAdditionalAttributes.maxlength
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                 40:
                   identifier: EmailAddress
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.TextMixin.validators.EmailAddress.editor.header.label
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                 50:
                   identifier: Integer
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.TextMixin.validators.Integer.editor.header.label
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
                 60:
                   identifier: Float
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.TextMixin.validators.Float.editor.header.label
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
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
                 80:
                   identifier: RegularExpression
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.TextMixin.validators.RegularExpression.editor.header.label
                     200:
                       identifier: regex
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.label
                       fieldExplanationText: formEditor.elements.TextMixin.validators.RegularExpression.editor.regex.fieldExplanationText
                       propertyPath: options.regularExpression
                       propertyValidators:
                         10: NotEmpty
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
             label: formEditor.elements.Textarea.label
             group: input
             groupSorting: 200
             iconIdentifier: t3-form-icon-textarea
