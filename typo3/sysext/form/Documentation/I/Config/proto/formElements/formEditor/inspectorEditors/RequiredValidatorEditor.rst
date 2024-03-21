.. include:: /Includes.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.requiredvalidatoreditor:

=========================
[RequiredValidatorEditor]
=========================

.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.requiredvalidatoreditor-introduction:

Introduction
============

Shows a checkbox. If set, a validator ('NotEmpty' by default) will be written into the ``form definition``. In addition another property could be written into the ``form definition``.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.requiredvalidatoreditor-properties:

Properties
==========

.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.templatename-requiredvalidatoreditor:

templateName
------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`prototypes.prototypeIdentifier.formEditor.formEditorPartials <prototypes.prototypeIdentifier.formeditor.formeditorpartials>`

:aspect:`value`
      Inspector-RequiredValidatorEditor

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      .. include:: properties/TemplateName.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.identifier-requiredvalidatoreditor:
.. include:: properties/Identifier.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.label-requiredvalidatoreditor:
.. include:: properties/Label.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.validatoridentifier-requiredvalidatoreditor:

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


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.propertypath-requiredvalidatoreditor:

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


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.propertyvalue-requiredvalidatoreditor:

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
