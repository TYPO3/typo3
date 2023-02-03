.. include:: /Includes.rst.txt

.. _deprecation-99531-1673606839:

===============================================================
Deprecation: #99531 - Backwards-compatible language key mapping
===============================================================

See :issue:`99531`

Description
===========

Before TYPO3 v4.0, TYPO3 had inconsistencies in its language keys, such as "ja" (= Japan)
instead of "jp" (= Japanese), which was still applicable. However, the old language keys
have not been in use for TYPO3's built-in translation servers.

In recent years, it was not even possible to use these keys for custom label files anymore.

However, the mapping was still used to detect the language of the user agent,
primarily for the backend login screen when no language was detected.

For this reason, the method :php:`Locales->getIsoMapping()` has been deprecated.


Impact
======

Calling the method above will trigger a PHP deprecation warning.


Affected installations
======================

TYPO3 installations which have been maintained for more than 15 years, still
using this method, or still using this legacy language keys.


Migration
=========

Migrate to the official language keys / locales by renaming the language files.
It is highly unlikely that the outdated language keys worked in the past major
versions of TYPO3.

.. index:: PHP-API, FullyScanned, ext:core
