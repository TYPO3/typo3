.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*:

==================================================
[<formElementTypeIdentifier>][formEditor][editors]
==================================================


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors-properties:

Properties
==========

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors:

<formElementTypeIdentifier>.formEditor.editors
----------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeidentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.editors

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      Array with numerical keys. Each arrayitem describes an ``inspector editor`` which is used to write values into a form element property.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*-commonproperties:

Common [<formElementTypeIdentifier>][formEditor][editors][*] properties
=======================================================================

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.identifier:
.. include:: inspectorEditors/properties/Identifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.templatename:

templateName
------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.formEditor.editors.*.templateName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formEditor.formEditorPartials <typo3.cms.form.prototypes.\<prototypeidentifier>.formeditor.formeditorpartials>`

:aspect:`Default value`
      Depends (see :ref:`concrete element configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      .. include:: inspectorEditors/properties/TemplateName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.label:
.. include:: inspectorEditors/properties/Label.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.propertypath:
.. include:: inspectorEditors/properties/PropertyPath.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formEditor.editors-availableinspectoreditors:

available inspector editors
---------------------------

.. toctree::

   inspectorEditors/CheckboxEditor
   inspectorEditors/CollectionElementHeaderEditor
   inspectorEditors/FinishersEditor
   inspectorEditors/FormElementHeaderEditor
   inspectorEditors/GridColumnViewPortConfigurationEditor
   inspectorEditors/MultiSelectEditor
   inspectorEditors/PropertyGridEditor
   inspectorEditors/RemoveElementEditor
   inspectorEditors/RequiredValidatorEditor
   inspectorEditors/SingleSelectEditor
   inspectorEditors/TextareaEditor
   inspectorEditors/TextEditor
   inspectorEditors/Typo3WinBrowserEditor
   inspectorEditors/ValidatorsEditor
   inspectorEditors/ValidationErrorMessageEditor
