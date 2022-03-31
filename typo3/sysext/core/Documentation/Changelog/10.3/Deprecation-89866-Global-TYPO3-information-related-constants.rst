.. include:: /Includes.rst.txt

================================================================
Deprecation: #89866 - Global TYPO3-information related constants
================================================================

See :issue:`89866`

Description
===========

The following global constants, which are initialized at the very
beginning of each TYPO3-related PHP process, have been marked as deprecated:

* :php:`TYPO3_copyright_year`
* :php:`TYPO3_URL_GENERAL`
* :php:`TYPO3_URL_LICENSE`
* :php:`TYPO3_URL_EXCEPTION`
* :php:`TYPO3_URL_DONATE`
* :php:`TYPO3_URL_WIKI_OPCODECACHE`

They have been migrated to the PHP class :php:`TYPO3\CMS\Core\Information\Typo3Information`
in order to benefit from opcaching, and to exactly reference when they are used
and where they are used throughout TYPO3.

This allows for further optimizations during the Bootstrap process and in our
testing suites.

In addition, the new PHP class encapsulates all global TYPO3-information and
community-wide information in one place.


Impact
======

No :php:`E_USER_DEPRECATED` error is triggered, however the constants will work during
TYPO3 v10, and be removed with TYPO3 v11.


Affected Installations
======================

Any TYPO3 installation with a custom extension that uses these
constants directly, which is highly unlikely.


Migration
=========

Use the public class constants or the public methods of the
new PHP class :php:`TYPO3\CMS\Core\Information\Typo3Information` directly.

.. index:: PHP-API, FullyScanned, ext:core
