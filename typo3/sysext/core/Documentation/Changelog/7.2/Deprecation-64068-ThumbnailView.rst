
.. include:: ../../Includes.txt

============================================================
Deprecation: #64068 - Deprecate thumbs.php and ThumbnailView
============================================================

See :issue:`64068`

Description
===========

Prior to the File Abstraction Layer (FAL) there was :file:`typo3/thumbs.php` generating all preview images for the TYPO3
Backend resources. This functionality is now marked for removal in TYPO3 CMS 8, as all functionality in the core already
uses the File Abstraction Layer.


Impact
======

Using `ThumbnailView`, `thumbs.php` or `BackendUtility::getThumbNail()` will throw a deprecation warning.


Affected installations
======================

Any TYPO3 installation with custom extensions using one of the files / methods mentioned.


Migration
=========

Use the File Abstraction Layer for any custom works. See `BackendUtility::thumbCode()` for inspiration.


.. index:: PHP-API, Backend
