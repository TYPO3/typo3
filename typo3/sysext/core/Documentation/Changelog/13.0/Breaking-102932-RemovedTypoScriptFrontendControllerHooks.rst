.. include:: /Includes.rst.txt

.. _breaking-102932-1706202449:

==============================================================
Breaking: #102932 - Removed TypoScriptFrontendController hooks
==============================================================

See :issue:`102932`

Description
===========

The following frontend TypoScript and page rendering related hooks
have been removed:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc']`,
  substituted by event :php:`ModifyTypoScriptConfigEvent`.
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache']`,
  no direct substitution, use event :php:`AfterTypoScriptDeterminedEvent` or an own middleware
  after :php:`typo3/cms-frontend/prepare-tsfe-rendering` instead.
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase']',
  substituted by event :php:`BeforePageCacheIdentifierIsHashedEvent`.


Impact
======

Any such hook implementation registered is not executed anymore
with TYPO3 v13.0+.


Affected installations
======================

TYPO3 installations with custom extensions using above listed hooks.


Migration
=========

See :doc:`PSR-14 event <../13.0/Feature-102932-NewTypoScriptRelatedFrontendEvents>`
for substitutions. The new events are tailored for more restricted use cases and can
be used when existing hook usages have not been "side" usages. Any "off label" hook
usages should be converted to custom middlewares instead.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
