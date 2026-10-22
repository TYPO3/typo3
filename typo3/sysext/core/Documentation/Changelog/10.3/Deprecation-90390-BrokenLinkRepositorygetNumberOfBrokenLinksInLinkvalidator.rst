.. include:: /Includes.rst.txt

.. _deprecation-90390:

=====================================================================================
Deprecation: #90390 - BrokenLinkRepository::getNumberOfBrokenLinks() in linkvalidator
=====================================================================================

See :issue:`90390`

Description
===========

The method :php-short:`\TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository::getNumberOfBrokenLinks()` has been marked as deprecated.


Impact
======

Usage of the method triggers a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Every TYPO3 installation that uses the method.


Migration
=========

Use :php-short:`\TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository::isLinkTargetBrokenLink()` instead.

.. index:: Backend, NotScanned, ext:linkvalidator
