.. include:: ../../Includes.txt

=========================================================
Feature: #79216 - Add YAML configuration for CKEditor RTE
=========================================================

See :issue:`79216`

Description
===========

The CKEditor-flavored RTE can now be configured via YAML files, defined as *presets*.

A preset contains both the RTE configuration and the HTML processing when storing the content
in the database.

A YAML file for RTE configurations can be registered by any extension in `ext_localconf.php`:

:php:`$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:rte_ckeditor/Configuration/RTE/Default.yaml';`

The TYPO3 Core ships with three flavors for the RTE configuration which can also be overridden via
PageTSconfig on a per-field/type level:

.. code-block:: typoscript

   RTE.default.preset = minimal
   RTE.config.tt_content.bodytext.types.textmedia.preset = full


The PageTSconfig allows to use the minimal configuration everywhere, but to use the full
configuration on the tt_content.bodytext field (but only for textmedia content types).

With the YAML configuration files, an "imports" functionality allows to import other
configuration and just override the necessary values for a custom configuration for a specific site.
This way, the processing part of EXT:rte_ckeditor can be used directly (which acts as best practice)
but the editor part can be completely customized.

The YAML format thus states three important parts considered by the RTE configuration preset:

1. "imports"
   Allows to import other files via the "resource" sub-property
2. "processing"
   uses the former "proc" options to hand over to RteHtmlParser to sanitize the content - the option
   are the same as for RTEHtmlArea
3. "editor"
   A configuration for CKEditor, where all CKEditor-related options can be set which are available
   from the ckeditor configuration specifications (see http://docs.ckeditor.com/#!/api/CKEDITOR.config
   for all options).

.. index:: LocalConfiguration, RTE, TSConfig
