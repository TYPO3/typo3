.. include:: /Includes.rst.txt

=====================================================================================
Deprecation: #90390 - BrokenLinkRepository::getNumberOfBrokenLinks() in linkvalidator
=====================================================================================

See :issue:`90390`

Description
===========

The method :php:`BrokenLinkRepository::getNumberOfBrokenLinks()` has been marked as deprecated.


Impact
======

Usage of the method triggers a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Every TYPO3 installation that uses the method.


Migration
=========

Use :php:`BrokenLinkRepository::isLinkTargetBrokenLink()` instead.

.. index:: Backend, NotScanned, ext:linkvalidator
