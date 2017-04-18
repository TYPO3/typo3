.. include:: ../../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.propertygrideditor:

====================
[PropertyGridEditor]
====================

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.propertygrideditor-introduction:

Introduction
============

Shows a grid which allows you to add (and remove) multiple rows and fill values for each row.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.propertygrideditor-properties:

Properties
==========

.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.templatename-propertygrideditor:

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
      Inspector-PropertyGridEditor

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      .. include:: properties/TemplateName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.identifier-propertygrideditor:
.. include:: properties/Identifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.label-propertygrideditor:
.. include:: properties/Label.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.propertypath-propertygrideditor:
.. include:: properties/PropertyPath.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.issortable-propertygrideditor:

isSortable
----------

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

.. :aspect:`Related options`
      @ToDo

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      If set to 'false' then the rows are not sortable.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.enableaddrow-propertygrideditor:

enableAddRow
------------

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

.. :aspect:`Related options`
      @ToDo

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      If set to 'false' the the "add new row" button is disabled.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.enabledeleterow-propertygrideditor:

enableDeleteRow
---------------

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

.. :aspect:`Related options`
      @ToDo

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      If set to 'false' the the "delete row" button is disabled.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.multiselection-propertygrideditor:

multiSelection
--------------

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

.. :aspect:`Related options`
      @ToDo

:aspect:`Good to know`
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

:aspect:`Description`
      If set to 'false' then only one row can be marked as preselected.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.removelastavailablerowflashmessagetitle-propertygrideditor:

removeLastAvailableRowFlashMessageTitle
---------------------------------------

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
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      There must be at least one existing row within this ``inspector editor``. If the last existing row is tried to be removed then a flash message is shown.
      This property define the title for the flash message.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.removelastavailablerowflashmessagemessage-propertygrideditor:

removeLastAvailableRowFlashMessageMessage
-----------------------------------------

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
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      There must be at least one existing row within this ``inspector editor``. If the last existing row is tried to be removed then a flash message is shown.
      This property define the text for the flash message.



.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.<formelementtypeidentifier>.formeditor.editors.*.shouldshowpreselectedvaluecolumn-propertygrideditor:

shouldShowPreselectedValueColumn
--------------------------------

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
      If set to 'false' then the column which is used to mark a row as preselected will be disabled.
