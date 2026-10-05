.. include:: /Includes.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.textareaeditor:

================
[TextareaEditor]
================

.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.textareaeditor-introduction:

Introduction
============

Shows a textarea.


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.textareaeditor-properties:

Properties
==========

.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.templatename-textareaeditor:

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
      Inspector-TextareaEditor

:aspect:`Good to know`
      -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
      .. include:: properties/TemplateName.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.identifier-textareaeditor:
.. include:: properties/Identifier.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.label-textareaeditor:
.. include:: properties/Label.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.propertypath-textareaeditor:
.. include:: properties/PropertyPath.rst.txt


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.enablerichtext-textareaeditor:

enableRichtext
--------------

:aspect:`Data type`
      boolean

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      false

:aspect:`Related options`
      -   :ref:`richtextConfiguration <prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.richtextconfiguration-textareaeditor>`

:aspect:`Good to know`
      -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
      ..  versionadded:: 14.2
          :changelog: feature-108966-1738963200

          Rich text editing in the form editor.

      If set to true, the textarea is rendered as a rich text editor using
      CKEditor 5. Editors can then format the text, for example with bold,
      italic, and links.

      The editor configuration is loaded from a global TYPO3 RTE preset. Use
      the `richtextConfiguration` option to select the preset.

      The following editors of the `standard` prototype enable rich text
      editing by default:

      -   The text of the `StaticText` element (preset `form-content`)
      -   The label of the `Checkbox` element (preset `form-label`)
      -   The message of the `EmailToSender`, `EmailToReceiver`, and
          `Confirmation` finishers (preset `form-content`)

:aspect:`Example`
      .. code-block:: yaml

         prototypes:
           standard:
             formElementsDefinition:
               StaticText:
                 formEditor:
                   editors:
                     300:
                       identifier: staticText
                       templateName: Inspector-TextareaEditor
                       label: formEditor.elements.StaticText.editor.staticText.label
                       propertyPath: properties.text
                       enableRichtext: true
                       richtextConfiguration: form-content


.. _prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.richtextconfiguration-textareaeditor:

richtextConfiguration
---------------------

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      form-label

:aspect:`Related options`
      - :ref:`prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.enablerichtext-textareaeditor`

:aspect:`Good to know`
      -   :ref:`Inspector <concepts-formeditor-inspector>`

:aspect:`Description`
      ..  versionadded:: 14.2
          :changelog: feature-108966-1738963200

          Rich text editing in the form editor.

      Defines the RTE preset that is used if `enableRichtext` is true. The
      preset must be registered in
      `$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']`. If this option is not
      set, the `form-label` preset is used.

      EXT:form registers two presets in its :file:`ext_localconf.php`:

      -   `form-label` - Bold, italic, and link. Intended for labels and
          short texts.
      -   `form-content` - Bold, italic, link, bulleted lists, and numbered
          lists. Intended for content fields.

      The presets of EXT:rte_ckeditor, for example `default`, `minimal`,
      and `full`, can be used as well.

:aspect:`Example`
      .. code-block:: yaml

         prototypes:
           standard:
             formElementsDefinition:
               Form:
                 formEditor:
                   propertyCollections:
                     finishers:
                       50:
                         identifier: Confirmation
                         editors:
                           300:
                             identifier: message
                             templateName: Inspector-TextareaEditor
                             label: formEditor.elements.Form.finisher.Confirmation.editor.message.label
                             propertyPath: options.message
                             enableRichtext: true
                             richtextConfiguration: form-content
