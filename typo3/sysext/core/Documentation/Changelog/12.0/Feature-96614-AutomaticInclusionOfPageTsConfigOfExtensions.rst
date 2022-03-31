.. include:: /Includes.rst.txt

===================================================================
Feature: #96614 - Automatic inclusion of PageTsConfig of extensions
===================================================================

See :issue:`96614`

Description
===========

Extension authors can now put a file named
:file:`Configuration/page.tsconfig` in their extension folder.

This file is then recognized to load the contents as global PageTsConfig
for the whole TYPO3 installation during build-time. This is much
more performant than the existing solution to use
:php:`ExtensionManagementUtility::addPageTSConfig()` in
:file:`ext_localconf.php`, which is added to
:php:`$TYPO3_CONF_VARS[SYS][defaultPageTSconfig]` during runtime.


Impact
======

When a file is created, the pageTSconfig is loaded automatically without a
custom registration anymore, and cached within the core caches, and more
performant than the existing registration format.

.. index:: TSConfig, ext:core
