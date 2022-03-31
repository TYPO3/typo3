.. include:: /Includes.rst.txt

=====================================================================
Important: #79847 - Fluid bugs fixed and features added (Fluid 2.3.1)
=====================================================================

See :issue:`79847`

Description
===========

The Fluid engine dependency is raised to version 2.3.1 which fixes a few important bugs and adds a couple of features:

* Namespace declarations (``{namespace foo=Bar\Baz\ViewHelpers}`` style) are now removed from output
  https://github.com/TYPO3/Fluid/pull/262
* The TemplatePaths object now accepts arrays for ``sanitizePath`` like the TYPO3 CMS adapter does.
  https://github.com/TYPO3/Fluid/pull/263
* Compiler is reset after each rendering - this fixes an issue where rendering the new ``HeaderAssets`` and ``FooterAssets``
  sections would fail to attach the assets until the Fluid template had been compiled (first page hit after cache flush).
  https://github.com/TYPO3/Fluid/pull/269

And in the new features department, two new features are added:

* XML namespace extraction is brought into sync with TYPO3 CMS adapter
  https://github.com/TYPO3/Fluid/pull/264
* An escaping modifier pre-processor has been added
  https://github.com/TYPO3/Fluid/pull/266

This means two things:

1. For template developers this means you can use ``{escaping off}`` in a template to completely disable the escaping
   which is normally done - which can be particularly helpful in non-HTML templates.
2. For the TYPO3 core this means it becomes possible to drop two classes (Fluid overrides) completely from the source;
   namely the ``XmlnsNamespaceTemplatePreProcessor`` and ``LegacyNamespaceExpressionNode``. Thus increasing the parsing
   efficiency of Fluid as it is integrated with TYPO3 CMS.

.. index:: Fluid
