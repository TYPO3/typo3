formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.GridContainer.formEditor

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

         GridContainer:
           formEditor:
             editors:
               100:
                 identifier: header
                 templateName: Inspector-FormElementHeaderEditor
               200:
                 identifier: label
                 templateName: Inspector-TextEditor
                 label: formEditor.elements.GridContainer.editor.label.label
                 propertyPath: label
               9999:
                 identifier: removeButton
                 templateName: Inspector-RemoveElementEditor
             predefinedDefaults: {  }
             label: formEditor.elements.GridContainer.label
             group: container
             groupSorting: 200
             _isCompositeFormElement: true
             _isGridContainerFormElement: true
             iconIdentifier: t3-form-icon-gridcontainer
