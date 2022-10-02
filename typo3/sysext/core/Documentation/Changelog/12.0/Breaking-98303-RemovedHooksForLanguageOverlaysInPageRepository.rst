.. include:: /Includes.rst.txt

.. _breaking-98303-1662659583:

========================================================================
Breaking: #98303 - Removed hooks for language overlays in PageRepository
========================================================================

See :issue:`98303`

Description
===========

The hooks

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getRecordOverlay']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPageOverlay']`

have been removed in favor of new PSR-14 events.

In addition, the method :php:`PageRepository->getRecordOverlay()` has been
marked as protected as the new events take place at a slightly different
piece of code.

Impact
======

Extensions using these hooks will have no effect anymore.

Extensions calling :php:`PageRepository->getRecordOverlay()` will trigger
a deprecation warning.

Affected installations
======================

TYPO3 installations with custom extensions using these hooks.

Migration
=========

Migrate to the new :ref:`PSR-14 events <feature-98303-1662659478>`:

* :php:`\TYPO3\CMS\Core\Domain\Event\BeforeRecordLanguageOverlayEvent`
* :php:`\TYPO3\CMS\Core\Domain\Event\AfterRecordLanguageOverlayEvent`
* :php:`\TYPO3\CMS\Core\Domain\Event\BeforePageLanguageOverlayEvent`

Extensions using the hooks can be made compatible with TYPO3 v11 and TYPO3 v12
by registering a PSR-14-based event listener while keeping the legacy hook
in place.

Extensions calling :php:`PageRepository->getRecordOverlay()` should call
:php:`PageRepository->getLanguageOverlay()` instead.

The events are now fired for any kind of database table, and are much
more generic, as they contain the full language fallback chain and overlay
behavior.

.. index:: Frontend, PHP-API, FullyScanned, ext:core
