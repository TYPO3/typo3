properties.dateFormat
---------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.DatePicker.properties.dateFormat

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Overwritable within form definition`
      Yes

:aspect:`form editor can write this property into the form definition (for prototype 'standard')`
      Yes

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

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
      The datepicker time format.
      The following date formats are allowed:

      **Day**

      ================ ==================================================================
      Format character Description
      ================ ==================================================================
      d                Day of the month, two digits with leading zeros
      D                A textual representation of a day, three letters
      j                Day of the month without leading zeros
      l                A full textual representation of the day of the week
      ================ ==================================================================

      **Month**

      ================ ==================================================================
      Format character Description
      ================ ==================================================================
      F                A full textual representation of a month, such as January or March
      m                Numeric representation of a month, with leading zeros
      M                A short textual representation of a month, three letters
      n                Numeric representation of a month, without leading zeros
      ================ ==================================================================

      **Year**

      ================ ==================================================================
      Format character Description
      ================ ==================================================================
      Y                A full numeric representation of a year, four digits
      y                A two digit representation of a year
      ================ ==================================================================