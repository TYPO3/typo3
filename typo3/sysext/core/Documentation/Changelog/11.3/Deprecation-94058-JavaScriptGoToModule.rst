.. include:: /Includes.rst.txt

=============================================
Deprecation: #94058 - JavaScript goToModule()
=============================================

See :issue:`94058`

Description
===========

One of the most prominent inline JavaScript functions
:js:`goToModule()` has been deprecated in favor of a streamlined
ActionHandler API for JavaScript.


Impact
======

When using the internal backend module entry objects via `setOnClick` and
`getOnClick` methods, PHP deprecation warnings are now triggered.


Affected Installations
======================

TYPO3 installations with custom extensions referencing these methods.


Migration
=========

Use the following HTML code to replace the inline :js:`goToModule()`
call to for example link to the page module:

.. code-block:: html

   <a href="#"
      data-dispatch-action="TYPO3.ModuleMenu.showModule"
      data-dispatch-args-list="web_layout"
   >
      Go to page module
   </a>

.. index:: JavaScript, FullyScanned, ext:backend
