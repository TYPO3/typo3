.. include:: ../../Includes.txt

===================================================================
Deprecation: #86486 - TypoScriptFrontendController->processOutput()
===================================================================

See :issue:`86486`

Description
===========

The method :php:`TypoScriptFrontendController->processOutput()` has been
marked as deprecated.


Impact
======

Calling this method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with extensions that use the method.


Migration
=========

Use :php:`TypoScriptFrontendController->applyHttpHeadersToResponse()` and
:php:`TypoScriptFrontendController->processContentForOutput()` instead, if necessary.

.. index:: Frontend, PHP-API, FullyScanned
