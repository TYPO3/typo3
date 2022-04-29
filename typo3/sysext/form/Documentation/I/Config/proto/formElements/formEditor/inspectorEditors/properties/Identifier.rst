.. include:: /Includes.rst.txt
identifier
----------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.editors.*.identifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Identifies the current ``inspector editor`` within the current form element.
      The identifier is a text of your choice but must be unique within the optionpath ``prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.editors``.
