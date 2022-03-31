.. include:: /Includes.rst.txt

======================================================
Deprecation: #90522 - TSFE properties regarding images
======================================================

See :issue:`90522`

Description
===========

The image related properties :php:`$imagesOnPage` and :php:`$lastImageInfo` of
:php:`TypoScriptFrontendController` have been marked as deprecated.

Impact
======

Calling these properties will trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected Installations
======================

All installations using these properties are affected.

Migration
=========

For :php:`$imagesOnPage` the AssetCollector may be used instead:

.. code-block:: php

   $assetCollector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Page\AssetCollector::class);
   $imagesOnPage = $assetCollector->getMedia();

.. index:: Frontend, PHP-API, NotScanned, ext:core
