.. include:: ../../Includes.txt

=============================================================
Deprecation: #83254 - Moved page generation methods into TSFE
=============================================================

See :issue:`83254`

Description
===========

The following methods have been marked as deprecated

* :php:`TYPO3\CMS\Frontend\Page\PageGenerator::isAllowedLinkVarValue()`
* :php:`TYPO3\CMS\Frontend\Page\PageGenerator::generatePageTitle()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->printTitle()`

As their functionality has been moved into TypoScriptFrontendController.


Impact
======

Calling any of the PHP methods above will trigger a deprecation warning.


Affected Installations
======================

Any installation with a third-party extension directly accessing these methods.


Migration
=========

For the generation of the page title tag, the method
:php:`TypoScriptFrontendController->generatePageTitle()` should be used instead.

.. index:: Frontend, PHP-API, FullyScanned