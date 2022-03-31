.. include:: /Includes.rst.txt

=======================================================
Deprecation: #85701 - Various methods in ModuleTemplate
=======================================================

See :issue:`85701`

Description
===========

The methods :php:`ModuleTemplate::icons()` and :php:`ModuleTemplate::loadJavascriptLib()`
have been marked as deprecated and will be removed in  TYPO3 v10.


Impact
======

Calling one of the mentioned methods will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Third party code which calls the methods mentioned.


Migration
=========

There is no migration for the method :php:`ModuleTemplate::icons()` available.
The method :php:`ModuleTemplate::loadJavascriptLib()` can be replaced by using :php:`PageRenderer` directly.

.. index:: Backend, FullyScanned
