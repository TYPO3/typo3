.. include:: /Includes.rst.txt

.. _breaking-107783-1760945127:

=======================================================================================
Breaking: #107783 - Registration of Metadata Extractors via `registerExtractionService`
=======================================================================================

See :issue:`107783`

Description
===========

The method :php:`TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::registerExtractionService()`
has been removed in favor of automatic registration via the
:php:`TYPO3\CMS\Core\Resource\Index\ExtractorInterface`.

Registration of Metadata extractors now happens automatically when the required
interface :php:`TYPO3\CMS\Core\Resource\Index\ExtractorInterface` is implemented
by a class. No further registration is necessary.

Impact
======

Any call to :php:`ExtractorRegistry::registerExtractionService()` is now a no-op
and has no effect in TYPO3 v14.0+. Metadata extractors are registered automatically
via the interface.

Affected installations
======================

TYPO3 installations with custom extensions that register Metadata extractor classes
via the mentioned method in :file:`ext_localconf.php`. The extension scanner will
report usages.

Migration
=========

The method is removed without deprecation in order to allow extensions
to work with TYPO3 v13 (using the registration method) and v14+ (using
automatic interface-based registration) without any further deprecations.

Remove the manual registration from :file:`ext_localconf.php`:

..  code-block:: diff
    :caption: EXT:my_ext/ext_localconf.php

    - $extractorRegistry = GeneralUtility::makeInstance(ExtractorRegistry::class);
    - $extractorRegistry->registerExtractionService(MyExtractor::class);

Since custom extractors already implement the required interface
:php:`TYPO3\CMS\Core\Resource\Index\ExtractorInterface`, no further change
is required inside the extractor class itself.

.. index:: FAL, PHP-API, FullyScanned, ext:core
