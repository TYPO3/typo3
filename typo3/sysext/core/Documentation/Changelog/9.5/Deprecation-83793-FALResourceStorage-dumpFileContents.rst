.. include:: /Includes.rst.txt

=============================================================
Deprecation: #83793 - FAL ResourceStorage->dumpFileContents()
=============================================================

See :issue:`83793`

Description
===========

The method :php:`TYPO3\CMS\Core\Resource\ResourceStorage->dumpFileContents()` has been marked as deprecated.


Impact
======

Calling this method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with extensions, which use the method.


Migration
=========

Use :php:`TYPO3\CMS\Core\Resource\ResourceStorage->streamFile()` instead.

.. index:: FAL, PHP-API, FullyScanned
