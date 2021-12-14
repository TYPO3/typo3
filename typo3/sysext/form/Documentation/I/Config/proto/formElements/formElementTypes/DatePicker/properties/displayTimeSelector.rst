.. include:: /Includes.rst.txt
properties.displayTimeSelector
------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.DatePicker.properties.displayTimeSelector

:aspect:`Data type`
      bool

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
         :emphasize-lines: 11

         DatePicker:
           properties:
             containerClassAttribute: input
             elementClassAttribute: 'small form-control'
             elementErrorClassAttribute: error
             timeSelectorClassAttribute: mini
             timeSelectorHourLabel: ''
             timeSelectorMinuteLabel: ''
             dateFormat: Y-m-d
             enableDatePicker: true
             displayTimeSelector: false

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      If set to true, an additional timeselector will be shown.
