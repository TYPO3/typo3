.. include:: /Includes.rst.txt
properties.value
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Checkbox.properties.value

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
         :emphasize-lines: 6

         Checkbox:
           properties:
             containerClassAttribute: 'input checkbox'
             elementClassAttribute: add-on
             elementErrorClassAttribute: error
             value: 1

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      The value of the checkbox which should be sent to the server.
