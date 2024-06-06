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

All other subproperties are usually handled via TYPO3 and then injected in the
CKEditor instance at runtime. This is useful for registering extra plugins, like
the TYPO3 core does with a custom Typolink.js plugin, or adding third-party plugins
like handling images.

editor.config
~~~~~~~~~~~~~

.. option:: editor.config:

   Configuration options  For a list of all options see
   https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html

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

      editor.config.contentsCss: "EXT:rte_ckeditor/Resources/Public/Css/contents.css"

   This is the default, as defined in `EXT:rte_ckeditor/Configuration/RTE/Editor/Base.yaml
   <https://github.com/TYPO3/typo3/blob/36055eb6d2b4ae87be702ee18c995c17812151ef/typo3/sysext/rte_ckeditor/Configuration/RTE/Editor/Base.yaml>`__.

.. todo: change url to Base.yaml after branching main to 10.4

.. option:: editor.config.allowedContent

   defines the tags, their attributes, styles and classes that are allowed.
   Plugins (like table and tabletools) use these definitions to change their behaviour.
   
   For details, have a look for "requiredContent" in the unminified source code of the responsible plugin:
   <https://github.com/ckeditor/ckeditor4/blob/master/plugins/table/dialogs/table.js>
   
   For more information on the syntax see the docs:
   <https://ckeditor.com/docs/ckeditor4/latest/guide/dev_allowed_content_rules.html>

.. option:: editor.config.extraAllowedContent

   Example::
   
      # allow <q> tags
      editor.config.extraAllowedContent: "*(*)[data-*];q"
      
   This enhances TYPO3's default `*(*)[data-*]` by the `q` tag.

.. option:: editor.config.disallowedContent

   defines the tags, their attributes, styles and classes that are **not** allowed.
   Plugins (like table and tabletools) use these definitions to change their behaviour.

   Example 1::
   
      editor.config.disallowedContent: "td{width,height,background-color,border-color}"

   The example above removes 4 input fields from the table cell dialog.

   Example 2::
   
      editor.config.disallowedContent: {
        table: {
          styles: 'width,height',
          attributes: 'border,align,cellspacing,cellpadding'
        },
        td: {
          styles: 'width,height,white-space,text-align,vertical-align,background-color,border-color'
        }
      }
      
   The example above removes a lot of fields from both table and table cell dialogs.

.. option:: editor.config.style

   Sets the style

.. option:: editor.config.toolbarGroup

   defines which toolbarGroups (and corresponding buttons) will be
   visible in the toolbar.

   Example::

      toolbarGroups:
        - { name: clipboard, groups: [ clipboard, cleanup, undo ] }

   See :ref:`config-example-toolbargroup` for
   more information.

.. option:: editor.config.removeButtons

   deactivates default buttons rendered in the toolbar.

   Example::

      removeButtons:
      - Anchor
      - Superscript
      - Subscript
      - Underline
      - Strike


   See `removeButtons
   <https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-removeButtons>`__
   for more information.

editor.*
~~~~~~~~

.. option:: editor.externalPlugins

   is a list of plugins with their routes and additional configuration options,
   the main “resource” subproperty needs to be set which is a JavaScript file
   using CKEditor’s plugin API, see `rte_ckeditor/Configuration/RTE/Editor/Plugins.yaml
   <https://github.com/typo3/typo3/blob/main/typo3/sysext/rte_ckeditor/Configuration/RTE/Editor/Plugins.yaml>`__.

.. todo: change url to Plugins.yaml after branching main to 10.4


.. _config-ref-tsconfig:

Page TSconfig
=============

We recommend you to put all configurations for the preset in the
:ref:`YAML <config-typo3-yaml>` configuration. However, it is still possible to
override these settings through the page TSconfig.

You can find a list of configuration properties in the :ref:`Page TSconfig
reference, chapter RTE <t3tsconfig:pageTsRte>`.
