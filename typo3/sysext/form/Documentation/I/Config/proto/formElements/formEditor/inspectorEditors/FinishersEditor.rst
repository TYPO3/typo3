.. include:: /Includes.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.finisherseditor:

=================
[FinishersEditor]
=================


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.finisherseditor-introduction:

Introduction
============

Shows a select list with finishers. If a finisher is already added to the form definition, then this finisher will be removed from the select list.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.finisherseditor-properties:

Properties
==========

.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.templatename-finisherseditor:

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
      Inspector-FinishersEditor

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      .. include:: properties/TemplateName.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.identifier-finisherseditor:
.. include:: properties/Identifier.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.label-finisherseditor:
.. include:: properties/Label.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.selectoptions.*.value-finisherseditor:

selectOptions.[*].value
-----------------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`"[finishersDefinition]"<prototypes.prototypeidentifier.finishersdefinition.*>`


.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Has to match with a ``prototypes.<prototypeIdentifier>.finishersdefinition`` configuration key.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.selectoptions.*.label-finisherseditor:

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
