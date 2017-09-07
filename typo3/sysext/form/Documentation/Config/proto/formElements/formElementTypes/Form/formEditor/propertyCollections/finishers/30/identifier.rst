formEditor.propertyCollections.finishers.30.identifier
------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Form.formEditor.propertyCollections.finishers.30.identifier

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

         Form:
           formEditor:
             propertyCollections:
               finishers:
                 30:
                   identifier: Redirect

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`
      - :ref:`"\<finisherIdentifier>"<typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>>`

:aspect:`Description`
      Identifies the finisher which should be attached to the form definition. Must be equal to a existing ``<finisherIdentifier>``.
