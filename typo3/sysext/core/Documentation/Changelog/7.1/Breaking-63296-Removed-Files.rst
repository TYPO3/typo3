
.. include:: /Includes.rst.txt

==================================================
Breaking: #63296 - Deprecated typo3/ files removed
==================================================

See :issue:`63296`

Description
===========

The following script entry points have been removed without substitution:

- typo3/file_edit.php
- typo3/file_newfolder.php
- typo3/file_rename.php
- typo3/file_upload.php
- typo3/show_rechis.php
- typo3/listframe_loader.php

The corresponding ListFrameLoaderController class is now marked as deprecated.

Impact
======

Any script pointing to one of these file resources will trigger a 404 server response.

Affected installations
======================

An extension needs to be adapted in the unlikely case that it uses a link to any of the files.

Migration
=========

The functionality of these scripts (except listframe_loader.php which is not used at all any more) have been moved to "modules".
Use BackendUtility::getModuleUrl() to link to them. The module name is identical to the file name without the ".php" suffix.

e.g. BackendUtility::getModuleUrl('file_edit');


.. index:: PHP-API, Backend
