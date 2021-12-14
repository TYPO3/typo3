.. include:: /Includes.rst.txt
properties.gridColumnClassAutoConfiguration.viewPorts
-----------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.GridRow.properties.gridColumnClassAutoConfiguration.viewPorts

:aspect:`Data type`
      array

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
         :emphasize-lines: 8-

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
      Each configuration key within `properties.gridColumnClassAutoConfiguration.viewPorts` represents an viewport of the CSS grid system (bootstrap by default).
