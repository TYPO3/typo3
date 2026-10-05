..  include:: /Includes.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.countrysingleselecteditor:

===========================
[CountrySingleSelectEditor]
===========================

..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.countrysingleselecteditor-introduction:

Introduction
============

..  versionadded:: 15.0
    :changelog: feature-109444-1744012800

Shows a select field with all countries. The selected country code is
written into the form element property that the option `propertyPath`
defines.

The `CountrySelect` element uses this editor for its default value. The
select field lists all countries, whatever the options
`prioritizedCountries`, `onlyCountries`, and `excludeCountries` of the form
element define. If one of these options excludes the selected country, the
frontend renders no option for it, and the default value has no effect.

In a form definition, set the default value with the country code in
`defaultValue`:

..  code-block:: yaml
    :caption: EXT:my_extension/Resources/Private/Forms/contact.form.yaml

    type: CountrySelect
    identifier: country-1
    label: 'Country'
    defaultValue: 'DE'
    properties:
      onlyCountries:
        - DE
        - AT
        - CH


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.countrysingleselecteditor-properties:

Properties
==========

..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.templatename-countrysingleselecteditor:

templateName
------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`value`
      Inspector-CountrySingleSelectEditor

:aspect:`Good to know`
      -   :ref:`The inspector of the form editor <concepts-formeditor-inspector>`

:aspect:`Description`
      ..  include:: properties/TemplateName.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.identifier-countrysingleselecteditor:
..  include:: properties/Identifier.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.label-countrysingleselecteditor:
..  include:: properties/Label.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.propertypath-countrysingleselecteditor:
..  include:: properties/PropertyPath.rst.txt


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.description-countrysingleselecteditor:

description
-----------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Good to know`
      -   :ref:`The inspector of the form editor <concepts-formeditor-inspector>`
      -   :ref:`Translating form editor settings <concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      A text that is shown below the select field in the inspector. The
      `CountrySelect` element uses it to remind editors that the country
      filters must not exclude the selected country.


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.countrysingleselecteditor-example:

Example
=======

The `CountrySelect` element of the prototype `standard` configures the
editor like this:

..  code-block:: yaml
    :caption: EXT:form/Configuration/Form/Base/FormElements/CountrySelect.yaml

    prototypes:
      standard:
        formElementsDefinition:
          CountrySelect:
            formEditor:
              editors:
                245:
                  identifier: defaultValue
                  templateName: Inspector-CountrySingleSelectEditor
                  label: formEditor.elements.CountrySelect.editor.defaultValue.label
                  propertyPath: defaultValue
                  description: formEditor.elements.CountrySelect.editor.defaultValue.description
