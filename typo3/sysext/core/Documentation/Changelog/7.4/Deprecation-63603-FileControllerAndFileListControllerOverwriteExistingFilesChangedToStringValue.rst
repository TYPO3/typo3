
.. include:: /Includes.rst.txt

==========================================================================================================
Deprecation: #63603 - FileController and FileListController overwriteExistingFiles changed to string value
==========================================================================================================

See :issue:`63603`

Description
===========

The GET/POST param to tell the FileController and FileListController whether to override a file or not switched from a bool
value to a string with the possibilities of the `\TYPO3\CMS\Core\Resource\DuplicationBehavior` enumeration.


Impact
======

Extensions still using `overwriteExistingFiles = 1` will throw a deprecation warning.


Affected Installations
======================

All installations with extensions that use the BE upload functionality and supply the file override option.


Migration
=========

Change the `<input name="overwriteExistingFiles" value="1">` to `<input name="overwriteExistingFiles" value="replace">`.


.. index:: PHP-API, FAL, Backend
