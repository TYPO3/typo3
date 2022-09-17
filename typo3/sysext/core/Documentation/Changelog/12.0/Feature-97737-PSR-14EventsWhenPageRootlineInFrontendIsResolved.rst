.. include:: /Includes.rst.txt

.. _feature-97737-1654595148:

================================================================================
Feature: #97737 - New PSR-14 events when Page + Rootline in Frontend is resolved
================================================================================

See :issue:`97737`

Description
===========

Three new PSR-14 events have been added in the process when the main class
:php:`TypoScriptFrontendController` is resolving a page and its rootline,
based on the incoming request.

* :php:`BeforePageIsResolvedEvent`
* :php:`AfterPageWithRootLineIsResolvedEvent`
* :php:`AfterPageAndLanguageIsResolvedEvent`

All events receive the incoming PSR-7 Request object, and the
:php:`TypoScriptFrontendController` object.

In addition, the latter two events allow event listeners to define a custom
PSR-7 Response for custom permission layers, and interrupting further processing
of a page.

These events serve as a replacement for the previously available hooks:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PreProcessing']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['fetchPageId-PostProcessing']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_preProcess']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess']`

Impact
======

Please note that TypoScript hasn't been resolved at the time of firing the
events, as this is done in the next step of the Frontend request.

.. index:: Frontend, ext:frontend
