.. include:: ../../Includes.txt

=============================================================
Deprecation: #83793 - FAL ResourceStorage->dumpFileContents()
=============================================================

See :issue:`83793`

Description
===========

The method :php:`ResourceStorage->dumpFileContents()` has been marked as deprecated.


Impact
======

Calling this method will trigger a PHP deprecation notice.


Affected Installations
======================

TYPO3 installations with extensions, which use the method.


Migration
=========

Use :php:`ResourceStorage->streamFile()` instead.

.. index:: FAL, PHP-API, FullyScanned
