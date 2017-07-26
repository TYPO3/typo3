.. include:: ../../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.gridcolumnviewportconfigurationeditor:

=======================================
[GridColumnViewPortConfigurationEditor]
=======================================

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.gridcolumnviewportconfigurationeditor-introduction:

Introduction
============

Shows a viewport selector as buttons and an input field. With this editor, you can define how many columns per viewPort an form element should occupy.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.gridcolumnviewportconfigurationeditor-properties:

Properties
==========

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.templateName-gridcolumnviewportconfigurationeditor:

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
      Inspector-GridColumnViewPortConfigurationEditor

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      .. include:: properties/TemplateName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.identifier-gridcolumnviewportconfigurationeditor:
.. include:: properties/Identifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.label-gridcolumnviewportconfigurationeditor:
.. include:: properties/Label.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.configurationOptions.viewPorts.*.viewPortIdentifier-gridcolumnviewportconfigurationeditor:

configurationOptions.viewPorts.[*].viewPortIdentifier
-----------------------------------------------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`"properties.gridColumnClassAutoConfiguration"<typo3.cms.form.prototypes.\<prototypeIdentifier>.formelementsdefinition.\<formelementtypeidentifier>.properties.gridcolumnclassautoconfiguration>`

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Has to match with a ``TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.formElementsDefinition.<formElementTypeIdentifier>.properties.gridColumnClassAutoConfiguration.viewPorts`` configuration key.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.configurationOptions.viewPorts.*.label-gridcolumnviewportconfigurationeditor:

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


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.configurationOptions.numbersOfColumnsToUse.label-gridcolumnviewportconfigurationeditor:

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


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.configurationOptions.numbersOfColumnsToUse.propertyPath-gridcolumnviewportconfigurationeditor:

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


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.configurationOptions.numbersOfColumnsToUse.fieldExplanationText-gridcolumnviewportconfigurationeditor:

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
