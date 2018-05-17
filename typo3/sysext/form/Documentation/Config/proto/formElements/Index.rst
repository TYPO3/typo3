.. include:: ../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition:

========================
[formElementsDefinition]
========================


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition-properties:

Properties
==========

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.*:

[formElementsDefinition]
------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition

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


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>:

<formElementTypeIdentifier>
---------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>

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
             GridContainer:
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


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>-commonproperties:

Common <formElementTypeIdentifier> properties
=============================================


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.defaultValue:

defaultValue
------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.defaultValue

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


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.implementationclassname:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.implementationClassName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`

:aspect:`Description`
      Classname which implements the form element.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.renderingoptions.translation.translationfile:

renderingOptions.translation.translationFile
--------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.renderingOptions.translation.translationFile

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"Translate form definition"<concepts-frontendrendering-translation-formdefinition>`

:aspect:`Description`
      Filesystem path(s) to translation files which should be searched for form element property translations.
      If ``translationFile`` is undefined, - :ref:`"TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formElementsDefinition.Form.renderingOptions.translation.translationFile"<typo3.cms.form.prototypes.\<prototypeIdentifier>.formelementsdefinition.form.renderingoptions.translation.translationfile>` will be used.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.renderingOptions.translation.translatePropertyValueIfEmpty:

renderingOptions.translation.translatePropertyValueIfEmpty
----------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.renderingoptions.translation.translatepropertyvalueifempty

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


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.renderingoptions.templatename:

renderingOptions.templateName
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.renderingOptions.templateName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"templateName"<apireference-frontendrendering-fluidformrenderer-options-templatename>`

:aspect:`Description`
      Set ``templateName`` to define a custom template name which should be used instead of the ``<formElementTypeIdentifier>``.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.properties:

properties
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.properties

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"Translate form definition"<concepts-frontendrendering-translation-formdefinition>`

:aspect:`Description`
      Array with form element specific properties.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.properties.fluidadditionalattributes:

properties.fluidAdditionalAttributes
------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.properties.fluidAdditionalAttributes

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"Translate form definition"<concepts-frontendrendering-translation-formdefinition>`

:aspect:`Description`
      The values within this array goes directely into the fluid form element viewhelpers property ``additionalAttributes``.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.properties.gridcolumnclassautoconfiguration:

properties.gridColumnClassAutoConfiguration
-------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.properties.gridColumnClassAutoConfiguration

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Undefined

:aspect:`Related options`
      - :ref:`"GridRow viewPorts"<typo3.cms.form.prototypes.\<prototypeIdentifier>.formelementsdefinition.gridrow.properties.gridcolumnclassautoconfiguration.viewports>`

:aspect:`Description`
        If the form element lies within a GridRow you can define the number of columns which the form element should occupy.
        Each ``viewPorts`` configuration key has to match with on ofe the defined viewports within ``TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.GridRow.properties.gridColumnClassAutoConfiguration.viewPorts``

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

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.label:

label
-----

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom form element implementations"<concepts-frontendrendering-codecomponents-customformelementimplementations>`
      - :ref:`"Translate form definition"<concepts-frontendrendering-translation-formdefinition>`

:aspect:`Description`
      The label for the form element.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor:

formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No (but recommended)

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Array with configurations for the ``form editor``


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.predefineddefaults:

formEditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.predefinedDefaults

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      Defines predefined defaults for form element properties which are prefilled, if the form element is added to a form.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.propertycollections:

formEditor.propertyCollections
------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Array with configurations for ``property collections`` for the form element.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.propertycollections.validators:

formEditor.propertyCollections.validators
-----------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.validators

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Array with configurations for available validators for a form element.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.propertycollections.validators.*.identifier:

formEditor.propertyCollections.validators.[*].identifier
--------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.validators.[*].identifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`
      - :ref:`"\<validatorIdentifier>"<typo3.cms.form.prototypes.\<prototypeidentifier>.validatorsdefinition.\<validatoridentifier>>`

:aspect:`Description`
      Identifies the validator which should be attached to the form element. Must be equal to a existing ``<validatorIdentifier>``.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.propertycollections.validators.*.editors:

formEditor.propertyCollections.validators.[*].editors
-----------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.validators.[*].editors

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Array with available ``inspector editors`` for this validator.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.propertycollections.finishers:

formEditor.propertyCollections.finishers
----------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.finishers

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Array with configurations for available finisher for a form definition.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.propertycollections.finishers.*.identifier:

formEditor.propertyCollections.finishers.[*].identifier
-------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.finishers.[*].identifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`
      - :ref:`"\<finisherIdentifier>"<typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.\<finisheridentifier>>`

:aspect:`Description`
      Identifies the finisher which should be attached to the form definition. Must be equal to a existing ``<finisherIdentifier>``.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.propertycollections.finishers.*.editors:

formEditor.propertyCollections.finishers.[*].editors
----------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.propertyCollections.finishers.[*].editors

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Array with available ``inspector editors`` for this finisher.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.label:

formEditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      This label will be shown within the "new element" Modal.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.group:

formEditor.group
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.group

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Related options`
      - :ref:`TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formEditor.formElementGroups <typo3.cms.form.prototypes.\<prototypeidentifier>.formeditor.formelementgroups>`

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Description`
      Define within which group within the ``form editor`` "new Element" modal the form element should be shown.
      The ``group`` value must be equal to an array key within ``formElementGroups``.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.groupsorting:

formEditor.groupSorting
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.groupSorting

:aspect:`Data type`
      int

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      The position within the ``formEditor.group`` for this form element.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.iconidentifier:

formEditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.iconIdentifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      An icon identifier which must be registered through the ``\TYPO3\CMS\Core\Imaging\IconRegistry``.
      This icon will be shown within

      - :ref:`"Inspector [FormElementHeaderEditor]"<typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.formelementheadereditor>`.
      - :ref:`"Abstract view formelement templates"<apireference-formeditor-stage-commonabstractformelementtemplates>`.
      - ``Tree`` component.
      - "new element" Modal


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formEditor.editors-tree:

formEditor.editors
------------------

.. toctree::

    formEditor/Index


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>-concreteconfigurations:

Concrete configurations
=======================

.. toctree::

    formElementTypes/AdvancedPassword
    formElementTypes/Checkbox
    formElementTypes/ContentElement
    formElementTypes/DatePicker
    formElementTypes/Email
    formElementTypes/Fieldset
    formElementTypes/FileUpload
    formElementTypes/Form
    formElementTypes/GridContainer
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
