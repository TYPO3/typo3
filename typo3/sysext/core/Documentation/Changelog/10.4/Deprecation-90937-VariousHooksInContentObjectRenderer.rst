.. include:: /Includes.rst.txt

============================================================
Deprecation: #90937 - Various hooks in ContentObjectRenderer
============================================================

See :issue:`90937`

Description
===========

The following hooks within class :php:`ContentObjectRenderer` have been marked as deprecated:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClassDefault']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['extLinkATagParamsHandler']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']`

All hooks have been available for a long time, and several new concepts and APIs that have been added in previous LTS versions already, that superseded these hooks.


Impact
======

Extensions registering the any one of the hooks listed above will trigger a PHP :php:`E_USER_DEPRECATED` error when the code is executed.


Affected Installations
======================

TYPO3 installations with older extensions implementing one of the hooks above, which is very rare and only serve specific use-cases
for rendering ContentObjects or custom link style tags that are not related to TYPO3 v8 linking syntax (`t3://...`).


Migration
=========

The hooks :php:`cObjTypeAndClass` and :php:`cObjTypeAndClassDefault` can be simplified by using the new way of registering custom ContentObjects via:

:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']` - see :file:`EXT:frontend/ext_localconf.php` for examples - TYPO3 Core adds its shipped ContentObjects exactly the same way.

The :php:`typolinkLinkHandler` hook is used for registering custom link syntax that start with a certain keyword such as "news:13".

Since TYPO3 v8, LinkHandler support has been added to TYPO3 Core natively, using the new `t3://` syntax.
The "LinkHandler" registry can be extended via :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['linkHandler']`
and :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']` that serves the same purpose with a better API.

.. index:: Frontend, FullyScanned, ext:frontend
