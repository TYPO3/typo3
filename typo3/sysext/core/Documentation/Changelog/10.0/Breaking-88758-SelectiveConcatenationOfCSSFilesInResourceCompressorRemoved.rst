.. include:: /Includes.rst.txt

=====================================================================================
Breaking: #88758 - Selective Concatenation of CSS files in ResourceCompressor removed
=====================================================================================

See :issue:`88758`

Description
===========

:php:`TYPO3\CMS\Core\Resource\ResourceCompressor`, used to merge and compress CSS and JS files, has had an option to only
merge CSS files from selected folders. This was used to limit CSS files of skins for TYPO3
Backend files.

The functionality has been removed, as all added CSS files are now merged into one file.

As TYPO3 Frontend and TypoScript has a much more flexible system for adding CSS files,
which should be concatenated, this change does not affect TYPO3 API of Frontend Requests.


Impact
======

Calling :php:`TYPO3\CMS\Core\Resource\ResourceCompressor->concatenateCssFiles()` with a second argument has no effect anymore.

Adding CSS files manually in TYPO3 Backend via custom extensions will now automatically be merged
with the loaded CSS styles of :php:`$TBE_STYLES` skin.


Affected Installations
======================

TYPO3 installations with extensions adding third-party CSS files in the TYPO3 Backend,
or extensions using :php:`TYPO3\CMS\Core\Resource\ResourceCompressor` directly.


Migration
=========

None, as it is considered to be useful to have one larger CSS file for TYPO3 Backend.

If necessary, add a CSS file manually via PageRenderer API which should be excluded from Concatenation.

.. index:: PHP-API, FullyScanned
