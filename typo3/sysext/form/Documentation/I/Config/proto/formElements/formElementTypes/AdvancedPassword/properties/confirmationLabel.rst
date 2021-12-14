.. include:: /Includes.rst.txt
properties.confirmationLabel
----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.AdvancedPassword.properties.confirmationLabel

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Overwritable within form definition`
      Yes

:aspect:`form editor can write this property into the form definition (for prototype 'standard')`
      Yes

:aspect:`Mandatory`
      No

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 6

         AdvancedPassword:
           properties:
             containerClassAttribute: input
             elementClassAttribute: input-medium
             elementErrorClassAttribute: error
             confirmationLabel: ''
             confirmationClassAttribute: input-medium

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      The label for the password confirmation form element.
