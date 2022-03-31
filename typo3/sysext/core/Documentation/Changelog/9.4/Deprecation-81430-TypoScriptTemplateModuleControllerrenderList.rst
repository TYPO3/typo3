.. include:: /Includes.rst.txt

====================================================================
Deprecation: #81430 - TypoScriptTemplateModuleController::renderList
====================================================================

See :issue:`81430`

Description
===========

The PHP method :php:`TypoScriptTemplateModuleController::renderList` has been marked as deprecated and will be removed with TYPO3 v10.


Impact
======

Installations accessing the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances calling the method.


Migration
=========

No migration available. Remove the method call, implement the required functionality in your own code or unload the extension.

.. index:: Backend, PHP-API, FullyScanned
