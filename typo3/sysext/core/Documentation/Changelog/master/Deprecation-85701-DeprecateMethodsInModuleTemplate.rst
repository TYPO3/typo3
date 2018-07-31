.. include:: ../../Includes.txt

=========================================================
Deprecation: #85701 - Deprecate methods in ModuleTemplate
=========================================================

See :issue:`85701`

Description
===========

The methods :php:`icons()` and :php:`loadJavascriptLib()` in the class :php:`ModuleTemplate`
have been marked as deprecated and will be removed in  TYPO3 v10.


Impact
======

Calling one of the mentioned methods will trigger a deprecation warning.


Affected Installations
======================

Third party code which accesses the methods.


Migration
=========

There is no migration for the method :php:`icons()` available.
The method :php:`loadJavascriptLib()` can be replaced by using the :php:`PageRenderer` directly.

.. index:: Backend, FullyScanned