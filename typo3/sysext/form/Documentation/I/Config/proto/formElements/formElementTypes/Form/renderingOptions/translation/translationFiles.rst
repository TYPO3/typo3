.. include:: /Includes.rst.txt
renderingOptions.translation.translationFiles
---------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Form.renderingOptions.translation.translationFiles

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

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

         Form:
           renderingOptions:
             translation:
               translationFiles:
                 10: 'EXT:form/Resources/Private/Language/locallang.xlf'
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

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Filesystem path(s) to translation files which should be searched for form element property translations.
      If ``translationFiles`` is undefined, - :ref:`"TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formElementsDefinition.Form.renderingOptions.translation.translationFiles"<typo3.cms.form.prototypes.\<prototypeIdentifier>.formelementsdefinition.form.renderingoptions.translation.translationfiles>` will be used.
