formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.FileUpload.formEditor

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

         FileUpload:
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
                 identifier: allowedMimeTypes
                 templateName: Inspector-MultiSelectEditor
                 label: formEditor.elements.FileUpload.editor.allowedMimeTypes.label
                 propertyPath: properties.allowedMimeTypes
                 selectOptions:
                   10:
                     value: application/msword
                     label: formEditor.elements.FileUpload.editor.allowedMimeTypes.doc
                   20:
                     value: application/vnd.openxmlformats-officedocument.wordprocessingml.document
                     label: formEditor.elements.FileUpload.editor.allowedMimeTypes.docx
                   30:
                     value: application/msexcel
                     label: formEditor.elements.FileUpload.editor.allowedMimeTypes.xls
                   40:
                     value: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
                     label: formEditor.elements.FileUpload.editor.allowedMimeTypes.xlsx
                   50:
                     value: application/pdf
                     label: formEditor.elements.FileUpload.editor.allowedMimeTypes.pdf
                   60:
                     value: application/vnd.oasis.opendocument.text
                     label: formEditor.elements.FileUpload.editor.allowedMimeTypes.odt
                   70:
                     value: application/vnd.oasis.opendocument.spreadsheet-template
                     label: formEditor.elements.FileUpload.editor.allowedMimeTypes.ods
               400:
                 identifier: saveToFileMount
                 templateName: Inspector-SingleSelectEditor
                 label: formEditor.elements.FileUploadMixin.editor.saveToFileMount.label
                 propertyPath: properties.saveToFileMount
                 selectOptions:
                   10:
                     value: '1:/user_upload/'
                     label: '1:/user_upload/'
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
                 identifier: 'validators'
                 templateName: 'Inspector-ValidatorsEditor'
                 label: 'formEditor.elements.FileUploadMixin.editor.validators.label'
                 selectOptions:
                   10:
                     value: ''
                     label: 'formEditor.elements.FileUploadMixin.editor.validators.EmptyValue.label'
                   20:
                     value: 'FileSize'
                     label: 'formEditor.elements.FileUploadMixin.editor.validators.FileSize.label'
               9999:
                 identifier: removeButton
                 templateName: Inspector-RemoveElementEditor
             propertyCollections:
               validators:
                 10:
                   identifier: FileSize
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.FileUploadMixin.validators.FileSize.editor.header.label
                     200:
                       identifier: minimum
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.MinimumMaximumEditorsMixin.editor.minimum.label
                       propertyPath: options.minimum
                       propertyValidators:
                         10: FileSize
                     300:
                       identifier: maximum
                       templateName: Inspector-TextEditor
                       label: formEditor.elements.MinimumMaximumEditorsMixin.editor.maximum.label
                       propertyPath: options.maximum
                       propertyValidators:
                         10: FileSize
                     9999:
                       identifier: removeButton
                       templateName: Inspector-RemoveElementEditor
             predefinedDefaults:
               properties:
                 saveToFileMount: '1:/user_upload/'
                 allowedMimeTypes:
                   - application/pdf
             label: formEditor.elements.FileUpload.label
             group: custom
             groupSorting: 100
             iconIdentifier: t3-form-icon-file-upload
