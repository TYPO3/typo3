.. include:: /Includes.rst.txt

.. _feature-96874-1664488673:

============================
Feature: #96874 - CKEditor 5
============================

See :issue:`96874`

Description
===========

TYPO3 v12 ships with CKEditor 5, a Rich-Text Editor to edit fields where
custom formatting for text with styling or links, or table formatting can be
achieved.

CKEditor 5 is a completely rewritten and new editor compared to CKEditor 4,
which was shipped since TYPO3 v8.

In general, most of the feature-set can be used in TYPO3 as before, with some
details kept in mind when upgrading.

Please read the documentation on the conceptual changes between CKEditor 4 and
CKEditor 5:

https://ckeditor.com/docs/ckeditor5/latest/installation/getting-started/migration-from-ckeditor-4.html

Impact
======

Next to plugins, which are not compatible anymore due to a completely different
model architecture, some configuration options have been modified or do not
apply anymore.

Most of the RTE configuration, which is done in TYPO3 in YAML preset files,
is migrated, however it is recommended to rewrite any custom configuration files
to become familiar with the CKEditor 5 API.

CSS Styling
-----------

CKEditor 5 does not load its editor in a specific iframe anymore. Especially
for adding custom styling and fonts, all CSS declarations now need to be prefixed
with ".ck-content". This can be achieved via SCSS, which TYPO3 natively
handles for custom CSS styles.
However, <body> tag CSS declarations won't work, as the `<body>` tag does not
apply to the editor HTML rendering anymore.

Configuration Options
---------------------

Some options have been adapted, which are rarely used, but now documented here:

*    editor.config.defaultContentLanguage is migrated to editor.config.language.content
*    editor.config.defaultLanguage is migrated to editor.config.language.ui

The following options are not needed anymore in CKEditor 5:

*    editor.config.uiColor
*    editor.config.removeDialogTabs
*    editor.config.entities_latin
*    editor.config.entities
*    editor.config.extraAllowedContent (migrated to editor.config.htmlSupport, covered via GeneralHTMLSupport plugin)
*    :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rte_ckeditor']['plugins']['TYPO3Link']['additionalAttributes']`

More migration options can be found here:
https://ckeditor.com/docs/ckeditor5/latest/installation/getting-started/migration-from-ckeditor-4.html

Custom configuration to the WordCount plugin is automatically migrated
from `editor.config.wordcount` to `editor.config.wordCount`:

*   `editor.config.justifyClasses` was used to add classes to the alignment types,
    which is migrated to `editor.config.alignment`. Example:

    ..  code-block:: yaml

        alignment:
          options:
            - { name: 'left', className: 'text-start' }
            - { name: 'center', className: 'text-center' }
            - { name: 'right', className: 'text-end' }
            - { name: 'justify', className: 'text-justify' }

   in addition, the extraPlugins `justify` is not needed anymore. The new
   plugin called `Alignment` is always active.

*   `editor.config.format_tags` was used to populate various block-level elements
    with a syntax like `p;h1;h2;h3;h4;h5;pre`. This is now moved to `editor.config.heading`:

    ..  code-block:: yaml

        heading:
          options:
            - { model: 'paragraph', title: 'Paragraph' }
            - { model: 'heading2', view: 'h2', title: 'Heading 2' }
            - { model: 'heading3', view: 'h3', title: 'Heading 3' }
            - { model: 'formatted', view: 'pre', title: 'My Pre-Formatted Text' }

*   `editor.config.removeButtons` items have a different naming now, and
    are moved to `editor.config.toolbar.removeItems`. This is however not needed
    anymore since toolbarGroups are removed and each button can now be declared
    properly.

*   `editor.config.stylesSet` which is used for the dropdown of custom
    style elements, is moved to `editor.config.style.definitions`
    with a similar syntax.

    ..  code-block:: yaml

        style:
          definitions:
            # block level styles
            - { name: "Lead", element: "p", classes: ['lead'] }
            - { name: "Small", element: "small", classes: [] }
            # Inline styles
            - { name: "Muted", element: "span", classes: ['text-muted'] }

    Please note that as of today, the "classes" attribute must be used,
    and custom "style" attribute does not work.

*   `editor.config.toolbarGroups` was previously used to create the buttons in the
    toolbar. This was used in conjunction with `editor.config.removeButtons`.
    Grouping is no longer available, but instead all buttons are listed
    separately with minor naming changes.
    The new option is now named `editor.config.toolbar` with `items` and
    `removeItems` as possible lists of buttons to show or hide.

    Functionality like "Cut/Copy/Paste" is now implicitly built-in without the
    need of cluttering the toolbar.

    Example from TYPO3's "Full" RTE configuration Yaml file:

    ..  code-block:: yaml

        toolbar:
          items:
            - clipboard
            - undo
            - redo
            # grouping separator
            - '|'
            - find
            - selectAll
            - '|'
            - Link
            - SoftHyphen
            - insertTable
            - tableColumn
            - tableRow
            - mergeTableCells
            - '|'
            - sourceEditing
            - horizontalLine
            # line break
            - '-'
            - bold
            - italic
            - underline
            - strikethrough
            - subscript
            - superscript
            - alignment
            - removeFormat
            - '|'
            - bulletedList
            - numberedList
            - blockQuote
            - indent
            - outdent
            - '|'
            - specialCharacters
            - '-'
            - style
            - heading

    Removal of single buttons via `editor.config.removeButtons` is now of limited
    need, however a list of `editor.config.toolbar.removeItems` can be given.

*   `editor.config.stylesSet` which is used for the dropdown of custom
    style elements, is moved to `editor.config.style.definitions`
    with a similar syntax.

    ..  code-block:: yaml

        style:
          definitions:
            # block level styles
            - { name: "Lead", element: "p", classes: ['lead'] }
            - { name: "Small", element: "small", classes: [] }
            # Inline styles
            - { name: "Muted", element: "span", classes: ['text-muted'] }

CKEditor 5 integration is still experimental and subject to change to adapt
to further needs until TYPO3 v12 LTS.

.. index:: RTE, ext:rte_ckeditor
