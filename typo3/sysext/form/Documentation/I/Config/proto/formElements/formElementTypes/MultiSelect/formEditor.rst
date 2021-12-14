.. include:: /Includes.rst.txt
formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.MultiSelect.formEditor

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

         MultiSelect:
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
               250:
                 identifier: inactiveOption
                 templateName: Inspector-TextEditor
                 label: formEditor.elements.SelectionMixin.editor.inactiveOption.label
                 propertyPath: properties.prependOptionLabel
                 fieldExplanationText: formEditor.elements.SelectionMixin.editor.inactiveOption.fieldExplanationText
                 doNotSetIfPropertyValueIsEmpty: true
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
                 shouldShowPreselectedValueColumn: multiple
                 multiSelection: true
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
                 label: formEditor.elements.MultiSelectionMixin.editor.validators.label
                 selectOptions:
                   10:
                     value: ''
                     label: formEditor.elements.MultiSelectionMixin.editor.validators.EmptyValue.label
                   20:
                     value: Count
                     label: formEditor.elements.MultiSelectionMixin.editor.validators.Count.label
               9999:
                 identifier: removeButton
                 templateName: Inspector-RemoveElementEditor
             predefinedDefaults:
               properties:
                 options: {  }
             propertyCollections:
               validators:
                 10:
                   identifier: Count
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.MultiSelectionMixin.validators.Count.editor.header.label
                     200:
                       identifier: minimum
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                       propertyPath: options.minimum
                       propertyValidators:
                         10: Integer
                     300:
                       identifier: maximum
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                       propertyPath: options.maximum
                       propertyValidators:
                         10: Integer
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
             label: formEditor.elements.MultiSelect.label
             group: select
             groupSorting: 500
             iconIdentifier: form-multi-select
