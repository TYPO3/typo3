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
      - :ref:`"Inspector"<concepts-formeditor-inspector>`

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

:aspect:`Description`
      If set to true, the textarea will be rendered as a rich text editor using CKEditor 5.
      This allows for formatted text input with features like bold, italic, links, and lists.

      The RTE configuration is loaded from the global TYPO3 RTE presets defined in the
      system configuration. Use the ``richtextConfiguration`` option to specify which
      preset should be used.

.. :aspect:`Example`
      .. code-block:: yaml

         prototypes:
           standard:
             formElementsDefinition:
               StaticText:
                 formEditor:
                   editors:
                     100:
                       identifier: text
                       templateName: Inspector-TextareaEditor
                       label: formEditor.elements.StaticText.editor.text.label
                       propertyPath: text
                       enableRichtext: true


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
      'form-label'

:aspect:`Related options`
      - :ref:`prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.formeditor.editors.*.enablerichtext-textareaeditor`

:aspect:`Description`
      Defines which RTE preset configuration should be used when ``enableRichtext`` is true.
      The preset name must correspond to a preset defined in the global TYPO3 RTE configuration.

      Common preset names include:

      - ``form-label`` - Simple formatting for labels and short texts (bold, italic, link) - default
      - ``form-content`` - Extended formatting for content fields (includes lists)
      - ``default`` - The default TYPO3 RTE configuration
      - ``minimal`` - A minimal configuration with basic formatting
      - ``full`` - A full-featured configuration with all available features

      If the specified preset does not exist, the system will fall back to the 'form-label' preset.

.. :aspect:`Example`
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
                             richtextConfiguration: form-label

