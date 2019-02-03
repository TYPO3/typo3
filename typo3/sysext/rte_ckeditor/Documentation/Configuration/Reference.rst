.. include:: ../Includes.txt


.. _config-ref:

=======================
Configuration Reference
=======================

.. _config-ref-yaml:

Yaml Configuration Reference
============================

When configurating the CKEditor using Yaml, these are the property
names that are currently used:

* processing
* editor

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

.. todo: rewrite this section to optimize the UX and look and feel

editor.config:
   Configuration options  For a list of all options see
   https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html

editor.config.language
   defines the editor’s UI language, and is dynamically calculated (if not set otherwise) by
   the backend users’ preference.

editor.config.contentsLanguage
   defines the language of the data, which is fetched from the
   sys_language information, but can be overridden by this option as well.
   For referencing files, TYPO3's internal "EXT:" syntax can be used, for
   using language labels, TYPO3's "LLL:" language functionality can be used.

editor.config.contentsCss
   defines the CSS file of the editor and the styles that can be applied.

   Example:

   .. code-block:: yaml

      editor.config.contentCss: "EXT:rte_ckeditor/Resources/Public/Css/contents.css"

   This is the default, as defined in `EXT:rte_ckeditor/Configuration/RTE/Editor/Base.yml
   <https://github.com/TYPO3/TYPO3.CMS/blob/9.5/typo3/sysext/rte_ckeditor/Configuration/RTE/Editor/Base.yaml>`__.

editor.config.style
   Sets the style

editor.config.toolbarGroup
   This defines, which toolbarGroups (and corresponding buttons) will be
   visible in the toolbar.

   Example:

   .. code-block:: yaml

      toolbarGroups:
        - { name: clipboard, groups: [ clipboard, cleanup, undo ] }

   See :ref:`config-example-toolbargroup` for
   more information.

editor.config.removeButtons
   deactivates default buttons rendered in the toolbar.

   Example:

   .. code-block:: yaml

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

editor.externalPlugins
   is a list of plugins with their routes and additional configuration options,
   the main “resource” subproperty needs to be set which is a JavaScript file
   using CKEditor’s plugin API, see `rte_ckeditor/Configuration/RTE/Editor/Plugins.yml
   <https://github.com/TYPO3/TYPO3.CMS/blob/9.5/typo3/sysext/rte_ckeditor/Configuration/RTE/Editor/Plugins.yaml>`__.
