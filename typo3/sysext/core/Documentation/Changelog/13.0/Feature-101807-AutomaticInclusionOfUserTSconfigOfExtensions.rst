.. include:: /Includes.rst.txt

.. _feature-101807-1693473782:

=====================================================================
Feature: #101807 - Automatic inclusion of user TSconfig of extensions
=====================================================================

See :issue:`101807`

Description
===========

Extension authors can now put a file named
:file:`Configuration/user.tsconfig` in their extension folder.

This file is then recognized to load the contents as global user TSconfig
for the whole TYPO3 installation during build-time. This is
more performant than the existing solution using
:php:`ExtensionManagementUtility::addUserTSConfig()` in
:file:`ext_localconf.php`, which is added to
:php:`$TYPO3_CONF_VARS[SYS][defaultUserTSconfig]` during runtime.


Impact
======

When a file is created, the user TSconfig is loaded automatically without a
custom registration, cached within the Core caches, and more
performant than the existing registration format. The old registration
format has been marked as deprecated, see
:ref:`Deprecated ExtensionManagementUtility::addUserTSConfig() <deprecation-101807-1693474000>`
for more details.


.. index:: TSConfig, ext:core
