
.. include:: ../../Includes.txt

===========================================================
Breaking: #67654 - Remove $GLOBALS[FILEICONS] functionality
===========================================================

See :issue:`67654`

Description
===========

The global variable `$GLOBALS['FILEICONS']` was in use for displaying icons before the sprite icons for files were introduced in TYPO3 v4.4.

The `$FILEICONS` has been removed completely as well as the function call `BackendUtility::getFileIcon()`.


Impact
======

Any usage on `$GLOBALS['FILEICONS']` will have no effect anymore.

Any calls on `BackendUtility::getFileIcon()` will result in a fatal error.


Affected Installations
======================

Instances that populate or make use of `$GLOBALS['FILEICONS']` or installations with extensions calling `BackendUtility::getFileIcon()` directly.


Migration
=========

Use sprite icons via `IconUtility::getSpriteIconForFile()`.
