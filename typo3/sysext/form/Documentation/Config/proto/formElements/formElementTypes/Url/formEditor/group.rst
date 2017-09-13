formEditor.group
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Url.formEditor.group

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Recommended

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3-

         Url:
           formEditor:
             group: html5

:aspect:`Description`
      Define within which group within the ``form editor`` "new Element" modal the form element should be shown.
      The ``group`` value must be equal to an array key within ``formElementGroups``.