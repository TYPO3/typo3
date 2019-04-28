formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.DatePicker.formEditor

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
               300:
                 identifier: dateFormat
                 templateName: Inspector-TextEditor
                 label: formEditor.elements.DatePicker.editor.dateFormat.label
                 propertyPath: properties.dateFormat
               400:
                 identifier: enableDatePicker
                 templateName: Inspector-CheckboxEditor
                 label: formEditor.elements.DatePicker.editor.enableDatePicker.label
                 propertyPath: properties.enableDatePicker
               500:
                 identifier: displayTimeSelector
                 templateName: Inspector-CheckboxEditor
                 label: formEditor.elements.DatePicker.editor.displayTimeSelector.label
                 propertyPath: properties.displayTimeSelector
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
                 label: formEditor.elements.DatePicker.editor.validators.label
                 selectOptions:
                   10:
                     value: ''
                     label: formEditor.elements.DatePicker.editor.validators.EmptyValue.label
                   20:
                     value: DateTime
                     label: formEditor.elements.DatePicker.editor.validators.DateTime.label
               9999:
                 identifier: removeButton
                 templateName: Inspector-RemoveElementEditor
             predefinedDefaults:
               properties:
                 dateFormat: Y-m-d
                 enableDatePicker: true
                 displayTimeSelector: false
             label: formEditor.elements.DatePicker.label
             group: custom
             groupSorting: 200
             iconIdentifier: t3-form-icon-date-picker
             propertyCollections:
               validators:
                 10:
                   identifier: DateTime
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.DatePicker.validators.DateTime.editor.header.label
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
