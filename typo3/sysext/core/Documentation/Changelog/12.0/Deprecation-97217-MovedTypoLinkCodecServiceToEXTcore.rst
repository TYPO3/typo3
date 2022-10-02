.. include:: /Includes.rst.txt

.. _deprecation-97217:

============================================================
Deprecation: #97217 - Moved TypoLinkCodecService to EXT:core
============================================================

See :issue:`97217`

Description
===========

The :php:`TypoLinkCodecService` class is used to encode and decode link
parameters, which are usually next to the actual link the `target` or
`class` information. This functionality is not directly bound to frontend
specific logic. To resolve cross dependencies the class has been moved to
the `LinkHandling` namespace in EXT:core. The old namespace has therefore
been deprecated.

Impact
======

The namespace has changed from :php:`\TYPO3\CMS\Frontend\Service\TypoLinkCodecService`
to :php:`\TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService` and the old namespace
has been marked as deprecated.

Affected Installations
======================

All installations using the deprecated namespace
:php:`\TYPO3\CMS\Frontend\Service\TypoLinkCodecService`. The extension
scanner will report usages.

Migration
=========

Replace usages with the new namespace
:php:`\TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService`
in custom extension code.

.. index:: PHP-API, FullyScanned, ext:frontend
