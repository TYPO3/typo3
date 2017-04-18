renderingOptions.submitButtonLabel
----------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Form.renderingOptions.submitButtonLabel

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
         :emphasize-lines: 22

         Form:
           renderingOptions:
             translation:
               translationFile: 'EXT:form/Resources/Private/Language/locallang.xlf'
             templateRootPaths:
               10: 'EXT:form/Resources/Private/Frontend/Templates/'
             partialRootPaths:
               10: 'EXT:form/Resources/Private/Frontend/Partials/'
             layoutRootPaths:
               10: 'EXT:form/Resources/Private/Frontend/Layouts/'
             addQueryString: false
             argumentsToBeExcludedFromQueryString: {  }
             additionalParams: {  }
             controllerAction: perform
             httpMethod: post
             httpEnctype: multipart/form-data
             _isCompositeFormElement: false
             _isTopLevelFormElement: true
             honeypot:
               enable: true
               formElementToUse: Honeypot
             submitButtonLabel: Submit
             skipUnknownElements: true

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      The submit Button label.