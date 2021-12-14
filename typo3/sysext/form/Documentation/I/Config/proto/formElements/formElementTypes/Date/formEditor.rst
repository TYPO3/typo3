.. include:: /Includes.rst.txt
formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Date.formEditor

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

         DatePicker:
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
               500:
                 identifier: defaultValue
                 templateName: Inspector-TextEditor
                 label: formEditor.elements.TextMixin.editor.defaultValue.label
                 propertyPath: defaultValue
                 placeholder: formEditor.elements.Date.editor.defaultValue.placeholder
                 propertyValidators:
                   10: RFC3339FullDateOrEmpty
               550:
                 identifier: 'step'
                 templateName: 'Inspector-TextEditor'
                 label: 'formEditor.elements.Date.editor.step.label'
                 fieldExplanationText: 'formEditor.elements.Date.editor.step.fieldExplanationText'
                 propertyPath: 'properties.fluidAdditionalAttributes.step'
                 propertyValidators:
                   10: 'Integer'
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
                 configurationOptions:
                   validationErrorMessage:
                     label: formEditor.elements.FormElement.editor.requiredValidator.validationErrorMessage.label
                     propertyPath: properties.validationErrorMessages
                     fieldExplanationText: formEditor.elements.FormElement.editor.requiredValidator.validationErrorMessage.fieldExplanationText
                       errorCodes:
                         10: 1221560910
                         20: 1221560718
                         30: 1347992400
                         40: 1347992453
               900:
                 identifier: validators
                 templateName: Inspector-ValidatorsEditor
                 label: formEditor.elements.TextMixin.editor.validators.label
                 selectOptions:
                   10:
                     value: ''
                     label: formEditor.elements.TextMixin.editor.validators.EmptyValue.label
                   20:
                     value: DateRange
                     label: formEditor.elements.Date.editor.validators.DateRange.label
               9999:
                 identifier: removeButton
                 templateName: Inspector-RemoveElementEditor
             predefinedDefaults:
               defaultValue:
               properties:
                 fluidAdditionalAttributes:
                   min:
                   max:
                   step: 1
             propertyCollections:
               ...
             label: formEditor.elements.Date.label
             group: html5
             groupSorting: 500
             iconIdentifier: form-date-picker
