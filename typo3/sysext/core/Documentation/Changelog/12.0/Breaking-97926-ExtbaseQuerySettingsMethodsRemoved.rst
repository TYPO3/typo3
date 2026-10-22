.. include:: /Includes.rst.txt

.. _breaking-97926-1657726187:

========================================================
Breaking: #97926 - Extbase QuerySettings methods removed
========================================================

See :issue:`97926`

Description
===========

Extbase's Persistence functionality is basing ORM queries on certain settings
usually fetched from :php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface`, with a default
implementation :php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings`.

The interface itself has changed so that it now requires two new methods:

:php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::getLanguageAspect(): LanguageAspect`
:php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::setLanguageAspect(LanguageAspect $aspect)`

The LanguageAspect covers both the overlay functionality and setting the
language ID.

For this reason, the following methods are removed from
:php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface`:

- :php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::getLanguageOverlayMode()`
- :php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::setLanguageOverlayMode($languageOverlayMode)`
- :php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::getLanguageUid()`
- :php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::setLanguageUid($languageUid)`

All adaptions have been made to the default implementation in
:php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings`, however the removed methods from the interface are kept
within the implementation to avoid fatal PHP errors.

Impact
======

Any custom implementation of :php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface` needs to implement
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
