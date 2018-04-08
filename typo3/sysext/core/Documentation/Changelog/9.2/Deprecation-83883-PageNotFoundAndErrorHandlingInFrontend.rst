.. include:: ../../Includes.txt

===================================================================
Deprecation: #83883 - Page Not Found And Error handling in Frontend
===================================================================

See :issue:`83883`

Description
===========

The following methods have been marked as deprecated:

* php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageUnavailableAndExit()`
* php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageNotFoundAndExit()`
* php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->checkPageUnavailableHandler()`
* php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageUnavailableHandler()`
* php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageNotFoundHandler()`
* php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageErrorHandler()`

These methods have been commonly used by third-party extensions to show that a page is not found,
a page is unavailable due to misconfiguration or the access to a page was denied.


Impact
======

Calling any of the methods above will trigger a deprecation warning.


Affected Installations
======================

Any installation with third-party PHP extension code calling these methods.


Migration
=========

Use the new `ErrorController` with its custom actions `unavailableAction()`, `pageNotFoundAction()` and
`accessDeniedAction()`.

Instead of exiting the currently running script, a proposed PSR-7-compliant response is returned which can be
handled by the third-party extension to enrich, return or customize exiting the script.

.. index:: Frontend, PHP-API, FullyScanned