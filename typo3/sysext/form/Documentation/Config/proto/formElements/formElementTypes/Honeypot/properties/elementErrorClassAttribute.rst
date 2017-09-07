properties.elementErrorClassAttribute
-------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Honeypot.properties.elementErrorClassAttribute

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Overwritable within form definition`
      Yes

:aspect:`form editor can write this property into the form definition (for prototype 'standard')`
      No

:aspect:`Mandatory`
      No

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 5

         Honeypot:
           properties:
             containerClassAttribute: input
             elementClassAttribute: ''
             elementErrorClassAttribute: error
             renderAsHiddenField: false
             styleAttribute: 'position:absolute; margin:0 0 0 -999em;'

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      A CSS class which is written to the form element if validation errors exists.