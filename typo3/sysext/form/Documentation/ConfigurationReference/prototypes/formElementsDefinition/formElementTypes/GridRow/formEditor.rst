formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.GridRow.formEditor

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

         GridRow:
           formEditor:
             editors:
               100:
                 identifier: header
                 templateName: Inspector-FormElementHeaderEditor
               200:
                 identifier: label
                 templateName: Inspector-TextEditor
                 label: formEditor.elements.GridRow.editor.label.label
                 propertyPath: label
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
               9999:
                 identifier: removeButton
                 templateName: Inspector-RemoveElementEditor
             predefinedDefaults: {  }
             label: formEditor.elements.GridRow.label
             group: container
             groupSorting: 300
             _isCompositeFormElement: true
             _isGridRowFormElement: true
             iconIdentifier: t3-form-icon-gridrow
