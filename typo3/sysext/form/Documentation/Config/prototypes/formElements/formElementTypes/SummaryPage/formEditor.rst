formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.SummaryPage.formEditor

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

         SummaryPage:
           formEditor:
             editors:
               100:
                 identifier: header
                 templateName: Inspector-FormElementHeaderEditor
               200:
                 identifier: label
                 templateName: Inspector-TextEditor
                 label: formEditor.elements.SummaryPage.editor.label.label
                 propertyPath: label
               300:
                 identifier: 'previousButtonLabel'
                 templateName: 'Inspector-TextEditor'
                 label: 'formEditor.elements.SummaryPage.editor.previousButtonLabel.label'
                 propertyPath: 'renderingOptions.previousButtonLabel'
               400:
                 identifier: 'nextButtonLabel'
                 templateName: 'Inspector-TextEditor'
                 label: 'formEditor.elements.SummaryPage.editor.nextButtonLabel.label'
                 propertyPath: 'renderingOptions.nextButtonLabel'
               9999:
                 identifier: removeButton
                 templateName: Inspector-RemoveElementEditor
             predefinedDefaults:
               renderingOptions:
                 previousButtonLabel: 'formEditor.elements.SummaryPage.editor.previousButtonLabel.value'
                 nextButtonLabel: 'formEditor.elements.SummaryPage.editor.nextButtonLabel.value'
             label: formEditor.elements.SummaryPage.label
             group: page
             groupSorting: 200
             _isTopLevelFormElement: true
             _isCompositeFormElement: false
             iconIdentifier: t3-form-icon-summary-page
