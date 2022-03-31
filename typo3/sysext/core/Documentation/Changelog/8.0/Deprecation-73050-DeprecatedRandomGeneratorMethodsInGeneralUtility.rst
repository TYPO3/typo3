
.. include:: /Includes.rst.txt

===========================================================================
Deprecation: #73050 - Deprecated random generator methods in GeneralUtility
===========================================================================

See :issue:`73050`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes()` has been marked as deprecated in favor of :php:`\TYPO3\CMS\Core\Crypto\Random->generateRandomBytes()`.

Also the method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getRandomHexString()` has been marked as deprecated in favor of :php:`\TYPO3\CMS\Core\Crypto\Random->generateRandomHexString()`.


Impact
======

Calling this methods directly will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance that use :php:`GeneralUtility::generateRandomBytes()` or :php:`GeneralUtility::getRandomHexString()` directly within an extension or third-party code.


Migration
=========

Replace calls to :php:`GeneralUtility::generateRandomBytes()` with :php:`GeneralUtility::makeInstance(Random::class)->generateRandomBytes()`.

Also replace calls to :php:`GeneralUtility::getRandomHexString()` with :php:`GeneralUtility::makeInstance(Random::class)->generateRandomHexString()`.

.. index:: PHP-API
