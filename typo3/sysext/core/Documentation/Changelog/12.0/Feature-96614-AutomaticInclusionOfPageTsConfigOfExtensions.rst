.. include:: /Includes.rst.txt

.. _feature-96614:

====================================================================
Feature: #96614 - Automatic inclusion of page TSconfig of extensions
====================================================================

See :issue:`96614`

Description
===========

Extension authors can now put a file named
:file:`Configuration/page.tsconfig` in their extension folder.

This file is then recognized to load the contents as global page TSconfig
for the whole TYPO3 installation during build-time. This is much
more performant than the existing solution to use
:php:`ExtensionManagementUtility::addPageTSConfig()` in
:file:`ext_localconf.php`, which is added to
:php:`$TYPO3_CONF_VARS[SYS][defaultPageTSconfig]` during runtime.

Impact
======

When a file is created, the page TSconfig is loaded automatically without a
custom registration anymore, and cached within the Core caches, and more
performant than the existing registration format.

.. index:: TSConfig, ext:core
