.. include:: /Includes.rst.txt
properties.fluidAdditionalAttributes.pattern
--------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Date.properties.fluidAdditionalAttributes.pattern

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
         :emphasize-lines: 8

         Date:
           properties:
             containerClassAttribute: input
             elementClassAttribute:
             elementErrorClassAttribute: error
             displayFormat: d.m.Y
             fluidAdditionalAttributes:
               pattern: '([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])'

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Pattern to be matched by the form control's value.
