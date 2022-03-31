.. include:: /Includes.rst.txt

======================================================================
Deprecation: #85130 - $TSFE->getPageShortcut() moved to PageRepository
======================================================================

See :issue:`85130`

Description
===========

The method :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::getPageShortcut()` has been
moved to :php:`TYPO3\CMS\Frontend\Page\PageRepository::getPageShortcut()`, as it conceptually belongs in
this class.


Impact
======

Calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations using the method directly in an extension.


Migration
=========

Switch the call :php:`$GLOBALS['TSFE']->getPageShortcut()` to :php:`$GLOBALS['TSFE']->sys_page->getPageShortcut()` to receive the exact
same result without a deprecation message.

.. index:: Frontend, PHP-API, PartiallyScanned
