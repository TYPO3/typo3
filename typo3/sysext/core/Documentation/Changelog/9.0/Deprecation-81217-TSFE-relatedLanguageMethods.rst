.. include:: ../../Includes.txt

===================================================
Deprecation: #81217 - TSFE-related language methods
===================================================

See :issue:`81217`

Description
===========

The main class for generating frontend output (TypoScriptFrontendController) has been streamlined
to use the same API within LanguageService.

Therefore the following methods within TypoScriptFrontendController have been marked as deprecated:
* :php:`readLLfile()`
* :php:`getLLL()`
* :php:`initLLvars()`


Impact
======

Calling any of the PHP methods above will trigger a deprecation warning.


Affected Installations
======================

Any TYPO3 installation calling custom frontend code with the methods above.


Migration
=========

Use :php:`TypoScriptFrontendController->sL()` for resolving language labels in the language
of the Frontend rendering engine.

For doing custom special logic, it is recommend to set up a custom instance of :php:`LanguageService`
which holds all functionality directly.

.. index:: Frontend, PHP-API, FullyScanned