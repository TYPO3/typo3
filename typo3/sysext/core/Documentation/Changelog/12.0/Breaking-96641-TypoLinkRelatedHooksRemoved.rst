.. include:: /Includes.rst.txt

.. _breaking-96641:

=================================================
Breaking: #96641 - TypoLink related hooks removed
=================================================

See :issue:`96641`

Description
===========

Following hooks, related to link generation with TYPO3's Frontend
Link building technique `typoLink`, have been removed  in favor of
the new PSR-14 events :php:`\TYPO3\CMS\Frontend\Event\AfterLinkIsGeneratedEvent`:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getATagParamsPostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlProcessors']`

Especially the latter functionality was not available
for all link types (only mail, file + external links).

At the same time, some external links and mail links were not
using `typoLink`, because internally the method :php:`$cObj->http_makelinks()`
had been used.

This architectural design flaw had been solved by introducing
a unified Link Generation API ("LinkFactory").

Impact
======

Using these hooks in extensions has no effect anymore in TYPO3 v12+.

Affected Installations
======================

TYPO3 installations with custom extensions using these hooks for
modifying links. The extension scanner in the Upgrade module / Install
tool will show affected occurrences.

Migration
=========

In order to make TYPO3 extensions compatible with TYPO3 v11 and
TYPO3 v12 simultaneously, the new PSR-14 event :php:`AfterLinkIsGeneratedEvent`
should be added in addition to the existing hooks.

The new :doc:`PSR-14 event <../12.0/Feature-96641-NewPSR-14EventForModifyingLinks>`
contains all information about the link result and the configuration itself.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
