renderingOptions._isHiddenFormElement
-------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Honeypot.renderingOptions._isHiddenFormElement

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Frontend

:aspect:`Overwritable within form definition`
      Yes

:aspect:`form editor can write this property into the form definition (for prototype 'standard')`
      No

:aspect:`Mandatory`
      No

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

         Honeypot:
           renderingOptions:
             _isHiddenFormElement: true

:aspect:`Description`
      Internal control setting to define that the form element is not visible within the summary page and emails.