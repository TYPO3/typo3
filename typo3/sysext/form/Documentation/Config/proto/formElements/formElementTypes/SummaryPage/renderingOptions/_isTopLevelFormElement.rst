renderingOptions._isTopLevelFormElement
---------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.SummaryPage.renderingOptions._isTopLevelFormElement

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Frontend

:aspect:`Overwritable within form definition`
      Yes

:aspect:`form editor can write this property into the form definition (for prototype 'standard')`
      No

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 4

         SummaryPage:
           renderingOptions:
             _isTopLevelFormElement: true
             _isCompositeFormElement: false
             nextButtonLabel: 'next Page'
             previousButtonLabel: 'previous Page'

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Internal control setting to define that the form element must not have a parent form element.