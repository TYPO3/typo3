..  include:: /Includes.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.checkboxeditor:

================
[CheckboxEditor]
================


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.checkboxeditor-introduction:

Introduction
============

Shows a checkbox which write 'true' or 'false' within the form definition for a form element property.


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.checkboxeditor-properties:

Properties
==========

..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.templatename-checkboxeditor:

templateName
------------

:aspect:`Data type`
    string

:aspect:`Needed by`
    Backend (form editor)

:aspect:`Mandatory`
    Yes

:aspect:`Related options`
    -   :ref:`prototypes.\<prototypeIdentifier>.formEditor.formEditorPartials <prototypes.prototypeidentifier.formeditor.formeditorpartials>`

:aspect:`value`
    Inspector-CheckboxEditor

:aspect:`Good to know`
    -   :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
    ..  include:: properties/TemplateName.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.identifier-checkboxeditor:
..  include:: properties/Identifier.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.label-checkboxeditor:
..  include:: properties/Label.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.propertypath-checkboxeditor:
..  include:: properties/PropertyPath.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.additionalelementpropertypaths-checkboxeditor:

additionalElementPropertyPaths
------------------------------

:aspect:`Data type`
    array

:aspect:`Needed by`
    Backend (form editor)

:aspect:`Mandatory`
    No

:aspect:`Good to know`
    -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
    An array which holds property paths which should be written in addition to the propertyPath option.
    The state of the checkbox is written as 'true' or 'false' to these property paths.

    For example:

    ..  code-block:: yaml

        additionalElementPropertyPaths:
          10: 'properties.fluidAdditionalAttributes.data-strict'


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.donotsetifpropertyvalueisempty-checkboxeditor:

doNotSetIfPropertyValueIsEmpty
------------------------------

:aspect:`Data type`
    bool

:aspect:`Needed by`
    Backend (form editor)

:aspect:`Mandatory`
    No

:aspect:`Related options`
    -   :ref:`additionalElementPropertyPaths <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.additionalelementpropertypaths-checkboxeditor>`

:aspect:`Good to know`
    -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
    If set to true then the properties configured in ``additionalElementPropertyPaths`` will be removed within the ``form definition``
    if the checkbox is unchecked instead of writing 'false' for these properties.
