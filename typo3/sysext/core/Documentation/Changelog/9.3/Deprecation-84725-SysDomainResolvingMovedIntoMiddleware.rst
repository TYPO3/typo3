.. include:: ../../Includes.txt

================================================================
Deprecation: #84725 - sys_domain resolving moved into middleware
================================================================

See :issue:`84725`

Description
===========

The method :php:`PageRepository->getDomainStartPage()` has been marked as deprecated.

The method :php:`TypoScriptFrontendController->findDomainRecord()` which was marked
as internal, has been removed.

As both methods have been used to resolve the root page ID of the current request,
they were solely there to fill :php:`$GLOBALS['TSFE']->domainStartPage` which is now filled
at an earlier stage through the :php:`SiteResolver` middleware.


Impact
======

Calling the PageRepository method will trigger a PHP :php:`E_USER_DEPRECATED` error.

Calling the TypoScriptFrontendController method will result in a fatal PHP error.


Affected Installations
======================

TYPO3 installations with third-party extensions calling the methods directly, usually
related to resolve a page ID or to mimic a frontend call.


Migration
=========

If the return value is needed, access :php:`$GLOBALS['TSFE']->domainStartPage` directly.

If the functionality is used in a third-party functionality and still needed,
ensure to extend from :php:`SiteResolver` middleware to call the now-protected method equivalents
instead.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend