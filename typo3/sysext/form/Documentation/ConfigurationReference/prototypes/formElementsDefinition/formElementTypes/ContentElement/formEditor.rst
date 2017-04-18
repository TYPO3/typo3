formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.ContentElement.formEditor

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

         ContentElement:
           formEditor:
             editors:
               100:
                 identifier: header
                 templateName: Inspector-FormElementHeaderEditor
               300:
                 identifier: contentElement
                 templateName: Inspector-Typo3WinBrowserEditor
                 label: formEditor.elements.ContentElement.editor.contentElement.label
                 buttonLabel: formEditor.elements.ContentElement.editor.contentElement.buttonLabel
                 browsableType: tt_content
                 propertyPath: properties.contentElementUid
                 propertyValidatorsMode: OR
                 propertyValidators:
                   10: Integer
                   20: FormElementIdentifierWithinCurlyBracesExclusive
               9999:
                 identifier: removeButton
                 templateName: Inspector-RemoveElementEditor
             predefinedDefaults:
               properties:
                 contentElementUid: ''
             label: formEditor.elements.ContentElement.label
             group: custom
             groupSorting: 700
             iconIdentifier: t3-form-icon-content-element
