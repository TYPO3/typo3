.. include:: /Includes.rst.txt

============================================================
Deprecation: #79560 - Deprecate ClientUtility::getDeviceType
============================================================

See :issue:`79560`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\ClientUtility::getDeviceType` is not used and completely outdated and has been marked as deprecated.


Impact
======

Calling :php:`\TYPO3\CMS\Core\Utility\ClientUtility::getDeviceType` method will trigger a deprecation log entry. Code using this method will work until it is removed in TYPO3 v9.


Affected Installations
======================

Any installation using the mentioned method :php:`\TYPO3\CMS\Core\Utility\ClientUtility::getDeviceType`.

.. index:: PHP-API
