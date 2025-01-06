.. include:: /Includes.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition:

========================
[formElementsDefinition]
========================


.. _prototypes.prototypeIdentifier.formelementsdefinition-properties:

Properties
==========

.. _prototypes.prototypeIdentifier.formelementsdefinition-properties.formelementsdefinition:

[formElementsDefinition]
------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:

         prototypes:
           <prototypeIdentifier>:
             formElementsDefinition:
               [...]

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`

:aspect:`Description`
      Array which defines the available form elements. Every key within this array is called the ``<formElementTypeIdentifier>``.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier:

<formElementTypeIdentifier>
---------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:

         prototypes:
           standard:
             Form:
               [...]
             Page:
               [...]
             SummaryPage:
               [...]
             Fieldset:
               [...]
             GridRow:
               [...]
             Text:
               [...]
             Password:
               [...]
             AdvancedPassword:
               [...]
             Textarea:
               [...]
             Honeypot:
               [...]
             Hidden:
               [...]
             Email:
               [...]
             Telephone:
               [...]
             Url:
               [...]
             Number:
               [...]
             Date:
               [...]
             Checkbox:
               [...]
             MultiCheckbox:
               [...]
             MultiSelect:
               [...]
             RadioButton:
               [...]
             SingleSelect:
               [...]
             DatePicker:
               [...]
             StaticText:
               [...]
             ContentElement:
               [...]
             FileUpload:
               [...]
             ImageUpload:
               [...]

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`

:aspect:`Description`
      This array key identifies a form element. This identifier could be used to attach a form element to a form.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-commonproperties:

Common <formElementTypeIdentifier> properties
=============================================


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.defaultValue:

defaultValue
------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.defaultValue

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Description`
      If set this string/ array will be used as default value of the form
      element. Array is in place for multi value elements (e.g. the
      ``MultiSelect`` form element).


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.implementationclassname:

implementationClassName
-----------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.implementationClassName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`

:aspect:`Description`
      Classname which implements the form element.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.renderingoptions.translation.translationfiles:

renderingOptions.translation.translationFiles
---------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.renderingOptions.translation.translationFiles

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"Translate form definition"<concepts-frontendrendering-translation-formdefinition>`

:aspect:`Description`
      Filesystem path(s) to translation files which should be searched for form element property translations.
      If ``translationFiles`` is undefined, - :ref:`"prototypes.prototypeIdentifier.formElementsDefinition.Form.renderingOptions.translation.translationFiles"<prototypes.prototypeIdentifier.formelementsdefinition.form.renderingoptions.translation.translationfiles>` will be used.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.renderingOptions.translation.translatePropertyValueIfEmpty:

renderingOptions.translation.translatePropertyValueIfEmpty
----------------------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.renderingoptions.translation.translatepropertyvalueifempty

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      true

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"Translate form definition"<concepts-frontendrendering-translation-formdefinition>`

:aspect:`Description`
      If set to ``false``, the form element property translation will be skipped if the form element property value is empty.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.renderingoptions.templatename:

renderingOptions.templateName
-----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.renderingOptions.templateName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"templateName"<apireference-frontendrendering-fluidformrenderer-options-templatename>`

:aspect:`Description`
      Set ``templateName`` to define a custom template name which should be used instead of the ``<formElementTypeIdentifier>``.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.properties:

properties
----------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.properties

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"Translate form definition"<concepts-frontendrendering-translation-formdefinition>`

:aspect:`Description`
      Array with form element specific properties.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.properties.elementDescription:

properties.elementDescription
-----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.properties.elementDescription

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Undefined

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"Translate form definition"<concepts-frontendrendering-translation-formdefinition>`

:aspect:`Description`
      Set a description of the form element. By default, it is displayed
      below the form element.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.properties.fluidadditionalattributes:

properties.fluidAdditionalAttributes
------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.properties.fluidAdditionalAttributes

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"Translate form definition"<concepts-frontendrendering-translation-formdefinition>`

:aspect:`Description`
      The values within this array are directly used within the form element ViewHelper's property ``additionalAttributes``.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.properties.gridcolumnclassautoconfiguration:

properties.gridColumnClassAutoConfiguration
-------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.properties.gridColumnClassAutoConfiguration

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Undefined

:aspect:`Related options`
      - :ref:`"GridRow viewPorts"<prototypes.prototypeIdentifier.formelementsdefinition.gridrow.properties.gridcolumnclassautoconfiguration.viewports>`

:aspect:`Description`
        If the form element lies within a GridRow you can define the number of columns which the form element should occupy.
        Each ``viewPorts`` configuration key has to match with on ofe the defined viewports within ``prototypes.<prototypeIdentifier>.formElementsDefinition.GridRow.properties.gridColumnClassAutoConfiguration.viewPorts``

              .. code-block:: yaml
                 :linenos:

                  gridColumnClassAutoConfiguration:
                    viewPorts:
                      lg:
                        numbersOfColumnsToUse: '2'
                      md:
                        numbersOfColumnsToUse: '3'
                      sm:
                        numbersOfColumnsToUse: '4'
                      xs:
                        numbersOfColumnsToUse: '5'

.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.label:

label
-----

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"Translate form definition"<concepts-frontendrendering-translation-formdefinition>`

:aspect:`Description`
      The label of the form element.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor:

formEditor
----------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No (but recommended)

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Array with configurations for the ``form editor``


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.predefineddefaults:

formEditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.predefinedDefaults

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      Defines predefined defaults for form element properties which are prefilled, if the form element is added to a form.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.propertycollections:

formEditor.propertyCollections
------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Array with configurations for ``property collections`` for the form element.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.propertycollections.validators:

formEditor.propertyCollections.validators
-----------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.validators

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Array with configurations for available validators for a form element.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.propertycollections.validators.*.identifier:

formEditor.propertyCollections.validators.[*].identifier
--------------------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.validators.[*].identifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`
      - :ref:`"\<validatorIdentifier>"<prototypes.prototypeIdentifier.validatorsdefinition.validatoridentifier>`

:aspect:`Description`
      Identifies the validator which should be attached to the form element. Must be equal to an existing ``<validatorIdentifier>``.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.propertycollections.validators.*.editors:

formEditor.propertyCollections.validators.[*].editors
-----------------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.validators.[*].editors

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Array with available ``inspector editors`` for this validator.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.propertycollections.finishers:

formEditor.propertyCollections.finishers
----------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.finishers

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Array with configurations for available finisher for a form definition.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.propertycollections.finishers.*.identifier:

formEditor.propertyCollections.finishers.[*].identifier
-------------------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.finishers.[*].identifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`
      - :ref:`"\<finisherIdentifier>"<prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier>`

:aspect:`Description`
      Identifies the finisher which should be attached to the form definition. Must be equal to an existing ``<finisherIdentifier>``.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.propertycollections.finishers.*.editors:

formEditor.propertyCollections.finishers.[*].editors
----------------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.finishers.[*].editors

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Array with available ``inspector editors`` for this finisher.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.label:

formEditor.label
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      This label will be shown within the "new element" Modal.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.group:

formEditor.group
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.group

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Related options`
      - :ref:`prototypes.prototypeIdentifier.formEditor.formElementGroups <prototypes.prototypeIdentifier.formeditor.formelementgroups>`

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

:aspect:`Description`
      Define within which group within the ``form editor`` "new Element" modal the form element should be shown.
      The ``group`` value must be equal to an array key within ``formElementGroups``.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.groupsorting:

formEditor.groupSorting
-----------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.groupSorting

:aspect:`Data type`
      int

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      The position within the ``formEditor.group`` for this form element.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.iconidentifier:

formEditor.iconIdentifier
-------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.iconIdentifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      An icon identifier which must be registered through the :php:`\TYPO3\CMS\Core\Imaging\IconRegistry`.
      This icon will be shown within

      - :ref:`"Inspector [FormElementHeaderEditor]"<prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.formelementheadereditor>`.
      - :ref:`"Abstract view formelement templates"<apireference-formeditor-stage-commonabstractformelementtemplates>`.
      - ``Tree`` component.
      - "new element" Modal


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formEditor.editors-tree:

formEditor.editors
------------------

.. toctree::

    formEditor/Index


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier-concreteconfigurations:

Concrete configurations
=======================

.. toctree::

    formElementTypes/AdvancedPassword
    formElementTypes/Checkbox
    formElementTypes/ContentElement
    formElementTypes/Date
    formElementTypes/DatePicker
    formElementTypes/Email
    formElementTypes/Fieldset
    formElementTypes/FileUpload
    formElementTypes/GridRow
    formElementTypes/Hidden
    formElementTypes/Honeypot
    formElementTypes/ImageUpload
    formElementTypes/MultiCheckbox
    formElementTypes/MultiSelect
    formElementTypes/Number
    formElementTypes/Page
    formElementTypes/Password
    formElementTypes/RadioButton
    formElementTypes/SingleSelect
    formElementTypes/StaticText
    formElementTypes/SummaryPage
    formElementTypes/Telephone
    formElementTypes/Text
    formElementTypes/Textarea
    formElementTypes/Url
