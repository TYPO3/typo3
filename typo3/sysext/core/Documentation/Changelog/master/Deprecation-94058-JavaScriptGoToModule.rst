.. include:: ../../Includes.txt

=============================================
Deprecation: #94058 - JavaScript goToModule()
=============================================

See :issue:`94058`

Description
===========

One of the most prominent inline JavaScript functions :javascript:`goToModule()` has been deprecated in favor of
a streamlined ActionHandler API for JavaScript.


Impact
======

When using the internal Backend Module entry objects via `setOnClick` and `getOnClick` methods, PHP deprecation warnings are now triggered.


Affected Installations
======================

TYPO3 installations with custom extensions referencing these methods.


Migration
=========

Use the following HTML code to replace the inline `goToModule()`
call to e.g. link to the page module:

:html:`<a href="#" data-dispatch-action="TYPO3.ModuleMenu.showModule" data-dispatch-args-list="web_layout">Go to page module</a>`

.. index:: JavaScript, FullyScanned, ext:backend