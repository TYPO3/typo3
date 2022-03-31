.. include:: /Includes.rst.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.requiredvalidatoreditor:

=========================
[RequiredValidatorEditor]
=========================

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.requiredvalidatoreditor-introduction:

Introduction
============

Shows a checkbox. If set, a validator ('NotEmpty' by default) will be written into the ``form definition``. In addition another property could be written into the ``form definition``.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.requiredvalidatoreditor-properties:

Properties
==========

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.templatename-requiredvalidatoreditor:

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
      Inspector-RequiredValidatorEditor

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      .. include:: properties/TemplateName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.identifier-requiredvalidatoreditor:
.. include:: properties/Identifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.label-requiredvalidatoreditor:
.. include:: properties/Label.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.validatoridentifier-requiredvalidatoreditor:

validatorIdentifier
-------------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

.. :aspect:`Related options`
      @ToDo

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      The ``<validatorIdentifier>`` which should be used.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.propertypath-requiredvalidatoreditor:

propertyPath
------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

.. :aspect:`Related options`
      @ToDo

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      An property path which should be written into the `form definition`` if the checkbox is set.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.propertyvalue-requiredvalidatoreditor:

propertyValue
-------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

.. :aspect:`Related options`
      @ToDo

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      The value for the property path which should be written into the `form definition`` if the checkbox is set.
