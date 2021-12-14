.. include:: /Includes.rst.txt

formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Page.formEditor

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

         Page:
           formEditor:
             editors:
               100:
                 identifier: header
                 templateName: Inspector-FormElementHeaderEditor
               200:
                 identifier: label
                 templateName: Inspector-TextEditor
                 label: formEditor.elements.Page.editor.label.label
                 propertyPath: label
               300:
                 identifier: 'previousButtonLabel'
                 templateName: 'Inspector-TextEditor'
                 label: 'formEditor.elements.Page.editor.previousButtonLabel.label'
                 propertyPath: 'renderingOptions.previousButtonLabel'
               400:
                 identifier: 'nextButtonLabel'
                 templateName: 'Inspector-TextEditor'
                 label: 'formEditor.elements.Page.editor.nextButtonLabel.label'
                 propertyPath: 'renderingOptions.nextButtonLabel'
               9999:
                 identifier: removeButton
                 templateName: Inspector-RemoveElementEditor
             predefinedDefaults:
               renderingOptions:
                 previousButtonLabel: 'formEditor.elements.Page.editor.previousButtonLabel.value'
                 nextButtonLabel: 'formEditor.elements.Page.editor.nextButtonLabel.value'
             label: formEditor.elements.Page.label
             group: page
             groupSorting: 100
             _isTopLevelFormElement: true
             _isCompositeFormElement: true
             iconIdentifier: form-page
