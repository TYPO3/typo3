.. include:: /Includes.rst.txt

.. _deprecation-96641-2:

=========================================================================
Deprecation: #96641 - Link-related functionality in ContentObjectRenderer
=========================================================================

See :issue:`96641`

Description
===========

Various methods related to shorthand syntax of generating
links and URLs have been marked as deprecated:

* :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getATagParams()`
* :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getTypoLink()`
* :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getUrlToCurrentLocation()`
* :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getTypoLink_URL()`

They are related to functionality for generating URLs,
and have been marked as deprecated in favor of the new LinkFactory
API, and the existing :php:`$cObj->typoLink()` and :php:`$cObj->typoLink_URL()`
methods.

Impact
======

Calling these methods in your own PHP code will trigger PHP :php:`E_USER_DEPRECATED` errors.

Affected Installations
======================

TYPO3 installations with custom extensions using these methods for
generating links. The extension scanner in the Upgrade module / Install tool
will show affected occurrences.

Migration
=========

It is recommended to use either existing API calls:

* :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->typoLink()`
* :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->typoLink_URL()`

or the new methods for code that should be compatible with TYPO3 v12+ - only:

* :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->createUrl()`
* :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->createLink()`

or calling the LinkFactory API directly.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
