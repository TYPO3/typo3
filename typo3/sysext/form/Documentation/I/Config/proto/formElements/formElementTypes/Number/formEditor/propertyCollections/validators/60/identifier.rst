.. include:: /Includes.rst.txt
formEditor.propertyCollections.validators.60.identifier
-------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Number.formEditor.propertyCollections.validators.60.identifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 6

         Number:
           formEditor:
             propertyCollections:
               validators:
                 60:
                   identifier: Number
                   editors:
                     100:
                       identifier: header
                       templateName: Inspector-CollectionElementHeaderEditor
                       label: formEditor.elements.TextMixin.validators.Number.editor.header.label

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`
      - :ref:`"\<validatorIdentifier>"<typo3.cms.form.prototypes.\<prototypeidentifier>.validatorsdefinition.\<validatoridentifier>>`

:aspect:`Description`
      Identifies the validator which should be attached to the form element. Must be equal to a existing ``<validatorIdentifier>``.
