.. include:: /Includes.rst.txt
formEditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Page.formEditor.predefinedDefaults

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Recommended

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3-

         Page:
           formEditor:
             predefinedDefaults:
               renderingOptions:
                 previousButtonLabel: 'formEditor.elements.Page.editor.previousButtonLabel.value'
                 nextButtonLabel: 'formEditor.elements.Page.editor.nextButtonLabel.value'

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Defines predefined defaults for form element properties which are prefilled, if the form element is added to a form.
