..  include:: /Includes.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.dateeditor:

============
[DateEditor]
============

..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.dateeditor-introduction:

Introduction
============

..  versionadded:: 14.2
    :changelog: feature-109126-1740000000

Shows a structured editor for a date value. A select field offers the
following modes:

-   **No value**: The value is empty.
-   **Today**: The value is `today`.
-   **Absolute date**: A date picker writes a date in the format `Y-m-d`.
-   **Relative date**: Fields for the direction ("past" or "future"), the
    amount, and the unit (days, weeks, months, or years). They write an
    expression such as `-18 years` or `+1 month`.
-   **Custom relative expression**: A text field accepts any other relative
    date expression, for example `+1 month +3 days`.

When an existing value is loaded, the editor selects the matching mode.

The `standard` prototype uses this editor for the default value of the
`Date` element and for the minimum and maximum of the `DateRange`
validator.

Relative expressions follow the syntax of the PHP function `strtotime()`.
They are resolved when the form is validated or rendered.


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.dateeditor-properties:

Properties
==========

..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.templatename-dateeditor:

templateName
------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      -   :ref:`prototypes.prototypeIdentifier.formEditor.formEditorPartials <prototypes.prototypeIdentifier.formeditor.formeditorpartials>`

:aspect:`value`
      Inspector-DateEditor

:aspect:`Good to know`
      -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
      ..  include:: properties/TemplateName.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.identifier-dateeditor:
..  include:: properties/Identifier.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.label-dateeditor:
..  include:: properties/Label.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.propertypath-dateeditor:
..  include:: properties/PropertyPath.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.propertyvalidators-dateeditor:

propertyValidators
------------------

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Related options`
      -   :ref:`formElementPropertyValidatorsDefinition <prototypes.prototypeIdentifier.formeditor.formelementpropertyvalidatorsdefinition>`

:aspect:`Good to know`
      -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
      Validates the value of the `inspector editor` with JavaScript
      validators. The `standard` prototype uses the
      `RFC3339FullDateOrEmpty` validator. It accepts an empty value, a date
      in the format `Y-m-d`, or a relative date expression.

      For example:

      ..  code-block:: yaml

          propertyValidators:
            10: RFC3339FullDateOrEmpty


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.description-dateeditor:

description
-----------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Good to know`
      -   :ref:`Inspector <concepts-formeditor-inspector>`
      -   :ref:`Translate form editor settings <concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      A text which is shown at the bottom of the `inspector editor`.


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.additionalelementpropertypaths-dateeditor:

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
      An array which holds property paths which should be written in addition
      to the `propertyPath` option. If the value is empty, these properties
      are removed from the form element.

      For example:

      ..  code-block:: yaml

          additionalElementPropertyPaths:
            10: 'properties.fluidAdditionalAttributes.min'


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.example-dateeditor:

Example
=======

..  code-block:: yaml
    :caption: Excerpt of the DateRange validator editors of the Date element

    prototypes:
      standard:
        formElementsDefinition:
          Date:
            formEditor:
              propertyCollections:
                validators:
                  10:
                    identifier: DateRange
                    editors:
                      250:
                        identifier: minimum
                        templateName: Inspector-DateEditor
                        label: formEditor.elements.DatePicker.validators.DateRange.editor.minimum
                        propertyPath: options.minimum
                        propertyValidators:
                          10: RFC3339FullDateOrEmpty
                        additionalElementPropertyPaths:
                          10: properties.fluidAdditionalAttributes.min
