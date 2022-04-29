.. include:: /Includes.rst.txt


.. _prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.gridcolumnviewportconfigurationeditor:

=======================================
[GridColumnViewPortConfigurationEditor]
=======================================

.. _prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.gridcolumnviewportconfigurationeditor-introduction:

Introduction
============

Shows a viewport selector as buttons and an input field. With this editor, you can define how many columns per viewPort an form element should occupy.


.. _prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.gridcolumnviewportconfigurationeditor-properties:

Properties
==========

.. _prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.templateName-gridcolumnviewportconfigurationeditor:

templateName
------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`prototypes.\<prototypeIdentifier>.formEditor.formEditorPartials <prototypes.\<prototypeidentifier>.formeditor.formeditorpartials>`

:aspect:`value`
      Inspector-GridColumnViewPortConfigurationEditor

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      .. include:: properties/TemplateName.rst


.. _prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.identifier-gridcolumnviewportconfigurationeditor:
.. include:: properties/Identifier.rst


.. _prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.label-gridcolumnviewportconfigurationeditor:
.. include:: properties/Label.rst


.. _prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.configurationOptions.viewPorts.*.viewPortIdentifier-gridcolumnviewportconfigurationeditor:

configurationOptions.viewPorts.[*].viewPortIdentifier
-----------------------------------------------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`"properties.gridColumnClassAutoConfiguration"<prototypes.\<prototypeIdentifier>.formelementsdefinition.\<formelementtypeidentifier>.properties.gridcolumnclassautoconfiguration>`

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Has to match with a ``prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.properties.gridColumnClassAutoConfiguration.viewPorts`` configuration key.


.. _prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.configurationOptions.viewPorts.*.label-gridcolumnviewportconfigurationeditor:

configurationOptions.viewPorts.[*].label
----------------------------------------

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
      The label for the viewport button.


.. _prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.configurationOptions.numbersOfColumnsToUse.label-gridcolumnviewportconfigurationeditor:

configurationOptions.numbersOfColumnsToUse.label
------------------------------------------------

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
      The label for the "Numbers of columns" input field.


.. _prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.configurationOptions.numbersOfColumnsToUse.propertyPath-gridcolumnviewportconfigurationeditor:

configurationOptions.numbersOfColumnsToUse.propertyPath
-------------------------------------------------------

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
      The path to the property of the form element which should be written.


.. _prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.configurationOptions.numbersOfColumnsToUse.fieldExplanationText-gridcolumnviewportconfigurationeditor:

configurationOptions.numbersOfColumnsToUse.fieldExplanationText
---------------------------------------------------------------

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
       A text which is shown at the bottom of the "Numbers of columns" input field.
