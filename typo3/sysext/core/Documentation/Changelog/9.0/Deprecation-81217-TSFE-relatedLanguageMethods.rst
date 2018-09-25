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
of the Frontend rendering engine as a replacement for :php:`getLLL()`.

If you are not doing anything special on language initialization, the call to :php:`initLLvars()` 
can likely be dropped. If you need to influence language initialization yourself, you can use the 
hooks :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_preProcess']`
 or :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess']`.

For doing special logic, it is recommend to set up a custom instance of :php:`LanguageService`
which holds all functionality directly.

For example you may then use :php:`$languageService->includeLLFile(...);` instead of :php:`readLLfile()`.

.. index:: Frontend, PHP-API, FullyScanned
