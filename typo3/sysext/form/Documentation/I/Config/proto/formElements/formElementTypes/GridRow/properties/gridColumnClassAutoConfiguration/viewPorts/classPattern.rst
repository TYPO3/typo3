.. include:: /Includes.rst.txt
properties.gridColumnClassAutoConfiguration.viewPorts.[*].classPattern
----------------------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.GridRow.properties.gridColumnClassAutoConfiguration.viewPorts.<gridColumnClassAutoConfigurationViewPortIdentifier>.classPattern

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
         :emphasize-lines: 10, 12, 14, 16

         GridRow:
           properties:
             containerClassAttribute: input
             elementClassAttribute: row
             elementErrorClassAttribute: error
             gridColumnClassAutoConfiguration:
               gridSize: 12
               viewPorts:
                 xs:
                   classPattern: 'col-{@numbersOfColumnsToUse}'
                 sm:
                   classPattern: 'col-sm-{@numbersOfColumnsToUse}'
                 md:
                   classPattern: 'col-md-{@numbersOfColumnsToUse}'
                 lg:
                   classPattern: 'col-lg-{@numbersOfColumnsToUse}'

:aspect:`Related options`
      - :ref:`"properties.gridColumnClassAutoConfiguration"<typo3.cms.form.prototypes.\<prototypeIdentifier>.formelementsdefinition.\<formelementtypeidentifier>.properties.gridcolumnclassautoconfiguration>`

:aspect:`Description`
      Defines the CSS class pattern for the CSS grid system.
      Each viewport `classPattern` will be wrapped around a form element within a grid row.
      The `{@numbersOfColumnsToUse}` placeholder will be replaced by the number of columns which the respective form element should occupy.
      The number of columns which the respective form element should occupy has to defined within the respective form elements within a GridRow.
      If a form element has no number of columns defined, the ``{@numbersOfColumnsToUse}`` are calculated automatically.
