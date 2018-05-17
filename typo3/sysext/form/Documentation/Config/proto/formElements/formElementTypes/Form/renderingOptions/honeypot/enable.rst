renderingOptions.honeypot.enable
--------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Form.renderingOptions.honeypot.enable

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
         :emphasize-lines: 20

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

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Enable or disable the honeypot feature.

.. attention::

   If you want to use a (static) site caching - for example EXT:staticfilecache -
   you should disable the automatic inclusion of the honeypot.

   Within your form definition:

   .. code-block:: yaml

      type: Form
      identifier: fooForm
      label: 'foo'
      renderingOptions:
        honeypot:
          enable: false
      renderables:
        ...

   Within your form setup:

   .. code-block:: yaml

      TYPO3:
        CMS:
          Form:
            prototypes:
              standard:
                formElementsDefinition:
                  Form:
                    renderingOptions:
                      honeypot:
                        enable: false

   See forge issue `#83212 <https://forge.typo3.org/issues/83212>`_ for more
   information.