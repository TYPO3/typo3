.. include:: /Includes.rst.txt

.. _deprecation-98303-1662659648:

==========================================================================
Deprecation: #98303 - Interfaces for PageRepository language overlay hooks
==========================================================================

See :issue:`98303`

Description
===========

The interfaces

* :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetRecordOverlayHookInterface`
* :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetPageOverlayHookInterface`

for the corresponding hooks

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getRecordOverlay']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPageOverlay']`

have been marked as deprecated.

Impact
======

The corresponding hooks have been removed, so the interfaces are not needed
anymore, however they are kept for extensions which aim to be compatible
with TYPO3 v11 and TYPO3 v12+ at the same time. No deprecation notice
is triggered while using in TYPO3 v12+.

Affected installations
======================

TYPO3 installations with custom extensions using these hooks and their interface.

Migration
=========

Migrate to the new :ref:`PSR-14 events <feature-98303-1662659478>`:

* :php:`\TYPO3\CMS\Core\Domain\Event\BeforeRecordLanguageOverlayEvent`
* :php:`\TYPO3\CMS\Core\Domain\Event\AfterRecordLanguageOverlayEvent`
* :php:`\TYPO3\CMS\Core\Domain\Event\BeforePageLanguageOverlayEvent`

.. index:: Frontend, PHP-API, FullyScanned, ext:core
