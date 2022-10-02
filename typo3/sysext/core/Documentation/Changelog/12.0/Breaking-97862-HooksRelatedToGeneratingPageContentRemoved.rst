.. include:: /Includes.rst.txt

.. _breaking-97862-1657195630:

===================================================================
Breaking: #97862 - Hooks related to generating page content removed
===================================================================

See :issue:`97862`

Description
===========

The existing TYPO3 hooks in the process of generating a TYPO3 Frontend page

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['usePageCache']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache']`

have been removed. These hooks have been used to execute custom PHP code after
a page is generated in the TYPO3 frontend and ready to be stored in cache.

Due to the removal of the hooks and the introduction of the new PSR-14 events
the method signature of :php:`TypoScriptFrontendController->generatePage_postProcessing()`
has been changed. The method now requires a :php:`ServerRequestInterface` as first
argument.

Impact
======

Extension code that hooks into these places will not be executed anymore in
TYPO3 v12+.

Extension code calling :php:`TypoScriptFrontendController->generatePage_postProcessing()`
without providing a :php:`ServerRequestInterface` as first argument
will trigger a PHP `ArgumentCountError`.

Affected installations
======================

TYPO3 installations with custom extensions using these hooks such as static file
generation or modifying the page content cache, which is highly likely in
third-party extensions. The extension scanner will detect usages as
strong match.

Extensions, manually calling :php:`TypoScriptFrontendController->generatePage_postProcessing()`
without providing a :php:`ServerRequestInterface` as first argument. The
extension scanner will detect usages as weak match.

Migration
=========

Use one of the two newly introduced
:doc:`PSR-14 events <../12.0/Feature-97862-NewPSR-14EventsForManipulatingFrontendPageGenerationAndCacheBehaviour>`:

* :php:`TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent`
* :php:`TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent`

Extensions using the hooks can be made compatible with TYPO3 v11 and TYPO3 v12
by registering a PSR-14-based event listener while keeping the legacy hook
in place.

The :php:`AfterCacheableContentIsGeneratedEvent` acts as a replacement for

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['usePageCache']`

whereas the :php:`AfterCachedPageIsPersistedEvent` is the replacement for

:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache']`.

Provide a :php:`ServerRequestInterface` as first argument when calling
:php:`TypoScriptFrontendController->generatePage_postProcessing()` in custom
extension code.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
