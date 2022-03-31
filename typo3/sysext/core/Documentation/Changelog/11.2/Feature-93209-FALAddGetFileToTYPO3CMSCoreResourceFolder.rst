.. include:: /Includes.rst.txt

======================================================================
Feature: #93209 - FAL: Add getFile() to TYPO3\CMS\Core\Resource\Folder
======================================================================

See :issue:`93209`

Description
===========

The FAL :php:`\TYPO3\CMS\Core\Resource\Folder` object now contains a new
convenience method :php:`getFile()`.

The :php:`\TYPO3\CMS\Core\Resource\FolderInterface` does not contain the
definition yet, as this would be a breaking change, thus, a comment is added to
make sure the interface gets this addition in TYPO3 v12 as well.

Impact
======

When dealing as a developer with FAL Folder objects, the method
:php:`$folder->getFile("filename.ext")` can now be used instead of
:php:`$folder->getStorage()->getFileInFolder("filename.ext", $folder)`.

.. index:: FAL, ext:core
