.. include:: /Includes.rst.txt

====================================================================
Deprecation: #87882 - File related controllers moved to EXT:filelist
====================================================================

See :issue:`87882`

Description
===========

The following controllers have been moved to extension `filelist` as they are part of
the filelist feature set:

* :php:`CreateFolderController`
* :php:`EditFileController`
* :php:`FileUploadController`
* :php:`RenameFileController`
* :php:`ReplaceFileController`


Impact
======

The namespace changed from :php:`TYPO3\CMS\Backend\Controller\File` to :php:`TYPO3\CMS\Filelist\Controller\File`. Using
the old controllers will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Installations accessing any of the above controllers.


Migration
=========

When wanting to use any of the functionality in these controllers, you should build your own controllers as they are
internal and might change at any time. Use the TYPO3 file abstraction layer as API and add your own functionality on top
of it with an own controller instead of reusing these.

.. index:: Backend, PHP-API, PartiallyScanned, ext:filelist
