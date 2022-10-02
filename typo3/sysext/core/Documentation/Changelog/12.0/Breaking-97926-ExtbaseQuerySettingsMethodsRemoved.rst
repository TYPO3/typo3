.. include:: /Includes.rst.txt

.. _breaking-97926-1657726187:

========================================================
Breaking: #97926 - Extbase QuerySettings methods removed
========================================================

See :issue:`97926`

Description
===========

Extbase's Persistence functionality is basing ORM queries on certain settings
usually fetched from :php:`QuerySettingsInterface`, with a default
implementation :php:`Typo3QuerySettings`.

The interface itself has changed so that it now requires two new methods:

:php:`QuerySettingsInterface::getLanguageAspect(): LanguageAspect`
:php:`QuerySettingsInterface::setLanguageAspect(LanguageAspect $aspect)`

The LanguageAspect covers both the overlay functionality and setting the
language ID.

For this reason, the following methods are removed from
:php:`QuerySettingsInterface`:

- :php:`QuerySettingsInterface::getLanguageOverlayMode()`
- :php:`QuerySettingsInterface::setLanguageOverlayMode($languageOverlayMode)`
- :php:`QuerySettingsInterface::getLanguageUid()`
- :php:`QuerySettingsInterface::setLanguageUid($languageUid)`

All adaptions have been made to the default implementation in
:php:`Typo3QuerySettings`, however the removed methods from the interface are kept
within the implementation to avoid fatal PHP errors.

Impact
======

Any custom implementation of :php:`QuerySettingsInterface` needs to implement
the newly defined methods of the interface.

Affected installations
======================

TYPO3 installations with custom Extbase extensions dealing with QuerySettings
that are adjusted with the methods used above.

Migration
=========

Switch the affected extensions via PHP to calling the newly added methods, as this is
how TYPO3 Core behaves the most reliable.

.. index:: PHP-API, FullyScanned, ext:extbase
