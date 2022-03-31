.. include:: /Includes.rst.txt

================================================
Deprecation: #80993 - GeneralUtility::getUserObj
================================================

See :issue:`80993`

Description
===========

The method :php:`GeneralUtility::getUserObj()` has been marked as deprecated as it is a sole wrapper for
:php:`GeneralUtility::makeInstance()`.


Impact
======

Calling the method will trigger a deprecation log entry.


Affected Installations
======================

Any installation using third-party extension that call this method.


Migration
=========

Use :php:`GeneralUtility::makeInstance()` instead, which acts as a simple drop-in replacement.

.. index:: PHP-API, FullyScanned
