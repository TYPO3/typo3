.. include:: /Includes.rst.txt
properties.renderAsHiddenField
------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Honeypot.properties.renderAsHiddenField

:aspect:`Data type`
      bool

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
         :emphasize-lines: 6

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
      By default the honeypot will be rendered as a regular text form element (input type "text"). ``renderAsHiddenField`` renders the honeypot as a hidden form element (input type "hidden").
