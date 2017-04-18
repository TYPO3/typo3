.. include:: ../../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.validatorseditor:

==================
[ValidatorsEditor]
==================


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.validatorseditor-introduction:

Introduction
============

Shows a select list with validators. If a validator is already added to the form element, then this validator will be removed from the select list.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.validatorseditor-properties:

Properties
==========

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.templatename-validatorseditor:

templateName
------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formEditor.formEditorPartials <typo3.cms.form.prototypes.\<prototypeidentifier>.formeditor.formeditorpartials>`

:aspect:`value`
      Inspector-FinishersEditor

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      .. include:: properties/TemplateName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.identifier-validatorseditor:
.. include:: properties/Identifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.label-validatorseditor:
.. include:: properties/Label.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.selectoptions.*.value-validatorseditor:

selectOptions.[*].value
-----------------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`"[validatorsDefinition]"<typo3.cms.form.prototypes.\<prototypeidentifier>.validatorsdefinition.*>`

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Has to match with a ``TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition`` configuration key.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.selectoptions.*.label-validatorseditor:

selectOptions.[*].label
-----------------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

.. :aspect:`Related options`
      @ToDo

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      The label which is shown within the select field.
