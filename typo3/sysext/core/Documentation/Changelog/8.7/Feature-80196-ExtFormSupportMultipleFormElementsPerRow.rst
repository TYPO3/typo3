.. include:: ../../Includes.txt

===================================================================
Feature: #80196 - EXT:form - support multiple form elements per row
===================================================================

See :issue:`80196`


Description
===========

Two new form element types have been added to the form framework:

* GridContainer
* GridRow

Using these 'container' form elements will enable you to define multiple form elements per row.

Example:

.. code-block:: yaml

    type: Form
    identifier: example-form-gridcontainer
    label: 'Form Grid Container'
    prototypeName: standard
    renderables:
      -
        type: Page
        identifier: page-1
        label: Page
        renderables:
          -
            type: GridContainer
            identifier: gridcontainer-2
            label: 'Grid: Container'
            renderables:
              -
                type: GridRow
                identifier: gridrow-2
                label: 'Grid: Row'
                renderables:
                  -
                    type: SingleSelect
                    identifier: singleselect-1
                    label: 'Single select'
                    properties:
                      gridColumnClassAutoConfiguration:
                        viewPorts:
                          xs:
                            numbersOfColumnsToUse: 12
                          lg:
                            numbersOfColumnsToUse: 2
                  -
                    type: Text
                    identifier: text-1
                    label: Text
                    properties:
                      gridColumnClassAutoConfiguration:
                        viewPorts:
                          xs:
                            numbersOfColumnsToUse: 6
                          lg:
                            numbersOfColumnsToUse: 5
                  -
                    type: MultiSelect
                    identifier: multiselect-1
                    label: 'Multi select'
                    properties:
                      gridColumnClassAutoConfiguration:
                        viewPorts:
                          xs:
                            numbersOfColumnsToUse: 6
                          sm:
                            numbersOfColumnsToUse: 5
          -
            type: GridContainer
            identifier: gridcontainer-1
            label: 'Grid: Container'
            renderables:
              -
                type: GridRow
                identifier: gridrow-1
                label: 'Grid: Row'
                renderables:
                  -
                    type: Password
                    identifier: password-1
                    label: Password

Per default, the resulting markup is compatible to Twitter Bootstrap.

The following options are available now:

.. code-block:: yaml

    GridContainer:
      ...
      properties:
        columnClassAutoConfiguration:
          gridSize: 12
          viewPorts:
            xs:
              classPattern: 'col-xs-{@numbersOfColumnsToUse}'
            sm:
              classPattern: 'col-sm-{@numbersOfColumnsToUse}'
            md:
              classPattern: 'col-md-{@numbersOfColumnsToUse}'
            lg:
              classPattern: 'col-lg-{@numbersOfColumnsToUse}'

and

.. code-block:: yaml

    <formElementIdentifier>:
      ...
      properties:
        gridColumnClassAutoConfiguration:
          viewPorts:
            xs:
              numbersOfColumnsToUse: 12
            ...
            lg:
              numbersOfColumnsToUse: 2


**GridContainer.properties.columnClassAutoConfiguration**

The example form definition shown above generates the following HTML markup

.. code-block:: html

    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-3 col-md-4 col-lg-2">
                ...
            </div>
            <div class="col-xs-6 col-sm-3 col-md-4 col-lg-5">
                ...
            </div>
            <div class="col-xs-6 col-sm-5 col-md-4 col-lg-5">
                ...
            </div>
        </div>
    </div>


**GridContainer.properties.columnClassAutoConfiguration.gridSize**

Total amount of grid columns (default: 12).


**GridContainer.properties.columnClassAutoConfiguration.viewPorts.<viewPortName>.classPattern**

This pattern will be used to generate the HTML class attribute values for each viewport.
The wildcard '{@numbersOfColumnsToUse}' will be replaced with the calculated grid column numbers.
At the end, all 'classPattern' items for each viewport will be merged together
and written into the class attribute of each form element (all form elements within a 'GridRow').

The calculation depends on the option 'gridSize', the amount of the form elements within the
'GridRow' form element and the optional option 'gridColumnClassAutoConfiguration' from the
form element configurations.


**<formElementIdentifier>.properties.gridColumnClassAutoConfiguration (otional)**

Each form elements within a 'GridRow' element can define the number of grid columns
to use on a 'per viewport' base.


**<formElementIdentifier>.properties.gridColumnClassAutoConfiguration.viewPorts.<viewPortName>**

The array keys '<viewPortName>' must match with the array keys '<viewPortName>'
from the configuration 'GridContainer.properties.columnClassAutoConfiguration.viewPorts.<viewPortName>'


**<formElementIdentifier>.properties.gridColumnClassAutoConfiguration.viewPorts.<viewPortName>.numbersOfColumnsToUse**

The number of grid columns to be used by this element for the viewport '<viewPortName>'.

This number goes hard to the '{@numbersOfColumnsToUse}' wildcard from the configuration
'GridContainer.properties.columnClassAutoConfiguration.viewPorts.<viewPortName>.classPattern'

If nothing is set, the {@numbersOfColumnsToUse} will be calculated automatically.


Impact
======

You are now able to add multiple form elements per row via the API and the form editor.


.. index:: Backend, Frontend, ext:form
