.. include:: /Includes.rst.txt
formEditor._isCompositeFormElement
----------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Page.formEditor._isCompositeFormElement

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3-

         Page:
           formEditor:
             _isCompositeFormElement: true

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Internal control setting to define that the form element contains child form elements.
