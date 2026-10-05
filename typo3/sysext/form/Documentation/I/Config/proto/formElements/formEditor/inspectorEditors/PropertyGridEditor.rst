.. include:: /Includes.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.propertygrideditor:

====================
[PropertyGridEditor]
====================

.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.propertygrideditor-introduction:

Introduction
============

Shows a grid which allows you to add (and remove) multiple rows and fill values for each row.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.propertygrideditor-properties:

Properties
==========

.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.templatename-propertygrideditor:

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
      Inspector-PropertyGridEditor

:aspect:`Good to know`
      -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
      .. include:: properties/TemplateName.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.identifier-propertygrideditor:
.. include:: properties/Identifier.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.label-propertygrideditor:
.. include:: properties/Label.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.propertypath-propertygrideditor:
.. include:: properties/PropertyPath.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.issortable-propertygrideditor:

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
      -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
      If set to 'false' the rows are not sortable.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.enableaddrow-propertygrideditor:

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
      -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
      If set to 'false' the "add new row" button is disabled.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.enabledeleterow-propertygrideditor:

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
      -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
      If set to 'false' the "delete row" button is disabled.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.multiselection-propertygrideditor:

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
      -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
      If set to 'false' only one row can be marked as preselected.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.removelastavailablerowflashmessagetitle-propertygrideditor:

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
      -   :ref:`Inspector <concepts-formeditor-inspector>`
      -   :ref:`Translate form editor settings <concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      There must be at least one existing row within this ``inspector editor``. If the last existing row is tried to be removed a flash message is shown.
      This property defines the title for the flash message.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.removelastavailablerowflashmessagemessage-propertygrideditor:

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
      -   :ref:`Inspector <concepts-formeditor-inspector>`
      -   :ref:`Translate form editor settings <concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      There must be at least one existing row within this ``inspector editor``. If the last existing row is tried to be removed a flash message is shown.
      This property defines the text for the flash message.


..  _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.gridcolumns-propertygrideditor:

gridColumns
-----------

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Good to know`
      -   :ref:`Inspector <concepts-formeditor-inspector>`
      -   :ref:`Translate form editor settings <concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      A list of column configurations. Each item configures one column of the
      grid. The `name` key selects the column: `label`, `value`, or
      `selected`. The `title` key sets the column header.

      The `label` and `value` columns also accept the
      `enableFormelementSelectionButton` key. If it is set to true, each row
      shows a button next to the input field of this column. The button lists
      the form elements of the form, except pages and container elements such
      as fieldsets. Selecting a form element appends its identifier in curly
      braces, for example `{email-1}`, to the field value.

      ..  versionadded:: 14.2
          :changelog: feature-91924-1656664855

          The `enableFormelementSelectionButton` key for the `label` and
          `value` columns.

      For example:

      ..  code-block:: yaml

          gridColumns:
            -
              name: value
              title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.value.title
              enableFormelementSelectionButton: true
            -
              name: label
              title: formEditor.elements.Form.finisher.EmailToSender.editor.recipients.gridColumns.label.title
              enableFormelementSelectionButton: true
