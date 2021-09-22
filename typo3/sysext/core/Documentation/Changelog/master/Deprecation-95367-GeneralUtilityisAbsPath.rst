.. include:: ../../Includes.txt

=================================================
Deprecation: #95367 - GeneralUtility::isAbsPath()
=================================================

See :issue:`95367`

Description
===========

The lowlevel TYPO3 API method :php:`GeneralUtility::isAbsPath()`
has been marked as deprecated.


Impact
======

Calling the method in your own PHP code will trigger a PHP deprecation
notice.


Affected Installations
======================

TYPO3 installations with custom extensions calling this PHP
method. You can check if you are affected via the Extension
Scanner tool provided in the Install Tool.


Migration
=========

Replace any calls to :php:`GeneralUtility::isAbsPath()` with
the exact equivalent :php:`PathUtility::isAbsolutePath()` which
checks for the same input.

.. index:: PHP-API, FullyScanned, ext:core