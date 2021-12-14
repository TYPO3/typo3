.. include:: /Includes.rst.txt
formEditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.Date.formEditor.predefinedDefaults

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Recommended

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3-

         Date:
           formEditor:
             predefinedDefaults:
               defaultValue:
               properties:
                 fluidAdditionalAttributes:
                   min:
                   max:
                   step: 1

:aspect:`Good to know`
      The properties ``defaultValue``, ``properties.fluidAdditionalAttributes.min``,
      ``properties.fluidAdditionalAttributes.max`` must have the format 'Y-m-d' which represents the RFC 3339
      'full-date' format.

      Read more: https://www.w3.org/TR/2011/WD-html-markup-20110405/input.date.html

:aspect:`Description`
      Defines predefined defaults for form element properties which are prefilled, if the form element is added to a form.
