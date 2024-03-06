.. include:: /Includes.rst.txt


.. _config-ref:

=======================
Configuration Reference
=======================

.. _config-ref-yaml:

YAML Configuration Reference
============================

When configuring the CKEditor using YAML, these are the property
names that are currently used:

.. contents::
   :local:
   :depth: 1

processing
----------

Configuring transformations kicks in the RteHtmlParser API of TYPO3, to
only allow certain HTML tags and attributes when saving the database or
leaving the database to the RTE. However, defining transformations towards
RTE is not really necessary anymore. Defining more strict processing options
when storing content in the database also needs to be ensured that CKEditor
allows this functionality too.

This configuration option was previously built within `RTE.proc` and can
still be overridden via Page TSconfig. Everything defined via “processing”
is available in RTE.proc and triggers RteHtmlParser options.

editor
------

Editor contains all RTE-specific options. All CKEditor-specific options, which one
could imagine are available under “config” property and handed over to CKEditor’s
instance-specific config array.

All other sub-properties are usually handled via TYPO3 and then injected in the
CKEditor instance at runtime. This is useful for registering extra plugins, like
the TYPO3 core does with a custom :file:`typo3-link.js` plugin, or adding
third-party plugins like handling images.

editor.config
~~~~~~~~~~~~~

.. option:: editor.config

   Configuration options  For a list of all options see
   https://ckeditor.com/docs/ckeditor5/latest/api/module_core_editor_editorconfig-EditorConfig.html

   .. note::

      Some configuration options from the official CKEditor 5 documentation
      do not apply to TYPO3, since they are related to specific plugins
      (for example: CKBox, CloudServices) which are not bundled in TYPO3's
      CKEditor build.

.. option:: editor.config.language

   defines the editor’s UI language, and is dynamically calculated (if not set otherwise) by
   the backend users’ preference.

.. option:: editor.config.contentsLanguage

   defines the language of the data, which is fetched from the
   sys_language information, but can be overridden by this option as well.
   For referencing files, TYPO3's internal "EXT:" syntax can be used, for
   using language labels, TYPO3's "LLL:" language functionality can be used.

.. option:: editor.config.contentsCss

   defines the CSS file of the editor and the styles that can be applied.

   Example::

      editor.config.contentsCss:
        - "EXT:rte_ckeditor/Resources/Public/Css/contents.css"

   This is the default, as defined in `EXT:rte_ckeditor/Configuration/RTE/Editor/Base.yaml
   <https://github.com/typo3/typo3/blob/main/typo3/sysext/rte_ckeditor/Configuration/RTE/Editor/Base.yaml>`__.

   .. note::
      When adding custom styling and fonts, all CSS declarations need to be
      prefixed with `.ck-content`. This scoping is applied by TYPO3
      automatically to all custom CSS styles. Referenced CSS stylesheets need to
      be downloadable via :js:`fetch()` in order for the JavaScript-based
      prefixing to work.

.. option:: editor.config.heading

   Defines headings available in the heading dropdown.

   Example::

      heading:
        options:
          - { model: 'heading2', view: 'h2', title: 'Heading 2' }

.. option:: editor.config.style

   Defines styles available in the style dropdown.

   Example::

      style:
        definitions:
          - { name: "Lead", element: "p", classes: ['lead'] }

.. option:: editor.config.importModules

   Imports custom CKEditor plugins. See :file:`EXT:rte_ckeditor/Configuration/RTE/Editor/Plugins.yaml`
   or :ref:`How do I create a custom plugin? <config-example-customplugin>`
   for examples.

.. _config-ref-tsconfig:

Page TSconfig
=============

We recommend you to put all configurations for the preset in the
:ref:`YAML <config-typo3-yaml>` configuration. However, it is still possible to
override these settings through the page TSconfig.

You can find a list of configuration properties in the :ref:`Page TSconfig
reference, chapter RTE <t3tsconfig:pageTsRte>`.
