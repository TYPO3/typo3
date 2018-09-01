.. include:: ../../Includes.txt

================================================================
Deprecation: #85666 - TypoScriptFrontendController->initTemplate
================================================================

See :issue:`85666`

Description
===========

The method :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->initTemplate()` has been marked as
deprecated.


Impact
======

Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions calling this public method directly.


Migration
=========

The method call can simply get removed, the TemplateService in instantiated by TSFE on demand.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
