.. include:: ../../Includes.txt

================================================================
Deprecation: #95343 - Legacy hook for new content element wizard
================================================================

See :issue:`95343`

Description
===========

The hook :php:`$GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']` which has been
used primarily back in TYPO3 v4.x times with the Extension Kickstarter for Pi-Based Plugins has been deprecated.


Impact
======

When an extension is registering a hook, and the New Content Element Wizard is called, a PHP deprecation message is triggered.


Affected Installations
======================

TYPO3 installations with third-party extensions using this hook.


Migration
=========

The alternative hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook']`
can be used instead, which allows to modify and add wizard items
as well.

.. index:: Backend, PHP-API, FullyScanned, ext:backend