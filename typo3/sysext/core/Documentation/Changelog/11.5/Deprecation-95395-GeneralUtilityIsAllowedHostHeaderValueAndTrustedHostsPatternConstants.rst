.. include:: /Includes.rst.txt

====================================================================================================
Deprecation: #95395 - GeneralUtility::isAllowedHostHeaderValue() and TRUSTED_HOSTS_PATTERN constants
====================================================================================================

See :issue:`95395`

Description
===========

The PHP method
:php:`TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedHostHeaderValue()`
and the PHP constants
:php:`TYPO3\CMS\Core\Utility\GeneralUtility::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL`
and
:php:`TYPO3\CMS\Core\Utility\GeneralUtility::ENV_TRUSTED_HOSTS_PATTERN_SERVER_NAME`
have been deprecated.


Impact
======

A deprecation will be logged in TYPO3 v11 if
:php:`TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedHostHeaderValue()` is
used. It is unlikely for extensions to have used this as the host header
is checked for every frontend and backend request anyway.

Usage of the constants will cause a PHP error "Undefined class constant" in
TYPO3 v12, the method
:php:`TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedHostHeaderValue()` will be
dropped without replacement.


Affected Installations
======================

Installations using the constants instead of static strings or
call the method explictily – which is unlikely.


Migration
=========

Use :php:`'.*'` instead of
:php:`TYPO3\CMS\Core\Utility\GeneralUtility::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL`
and :php:`'SERVER_NAME'` instead of
:php:`TYPO3\CMS\Core\Utility\GeneralUtility::ENV_TRUSTED_HOSTS_PATTERN_SERVER_NAME`.

Don't use  :php:`TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedHostHeaderValue()`.


.. index:: PHP-API, FullyScanned, ext:core
