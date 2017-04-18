renderingOptions.nextButtonLabel
--------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.SummaryPage.renderingOptions.nextButtonLabel

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Overwritable within form definition`
      Yes

:aspect:`form editor can write this property into the form definition (for prototype 'standard')`
      Yes

:aspect:`Mandatory`
      No

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 5

         SummaryPage:
           renderingOptions:
             _isTopLevelFormElement: true
             _isCompositeFormElement: false
             nextButtonLabel: 'next Page'
             previousButtonLabel: 'previous Page'

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      The label for the "next page" Button.