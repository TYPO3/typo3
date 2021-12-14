.. include:: /Includes.rst.txt
formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.RadioButton.formEditor

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

         RadioButton:
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
                 identifier: options
                 templateName: Inspector-PropertyGridEditor
                 label: formEditor.elements.SelectionMixin.editor.options.label
                 propertyPath: properties.options
                 isSortable: true
                 enableAddRow: true
                 enableDeleteRow: true
                 removeLastAvailableRowFlashMessageTitle: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageTitle
                 removeLastAvailableRowFlashMessageMessage: formEditor.elements.SelectionMixin.editor.options.removeLastAvailableRowFlashMessageMessage
                 shouldShowPreselectedValueColumn: single
                 multiSelection: false
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
               9999:
                 identifier: removeButton
                 templateName: Inspector-RemoveElementEditor
             predefinedDefaults:
               properties:
                 options: {  }
             label: formEditor.elements.RadioButton.label
             group: select
             groupSorting: 300
             iconIdentifier: form-radio-button
