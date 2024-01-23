.. include:: /Includes.rst.txt

.. _breaking-101311-1689067519:

====================================================================================
Breaking: #101311 - Make the parameter for GeneralUtility::sanitizeLocalUrl required
====================================================================================

See :issue:`101311`

Description
===========

The (only) parameter for :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl()`
is now required.

Impact
======

Calling :php:`GeneralUtility::sanitizeLocalUrl()` without an argument will result
in a PHP error.

Affected installations
======================

Only those installations that call :php:`GeneralUtility::sanitizeLocalUrl()`
without an argument.

The extension scanner will detect affected usages as a strong match.

Migration
=========

Make sure to pass an argument to :php:`GeneralUtility::sanitizeLocalUrl()`.

.. index:: PHP-API, FullyScanned, ext:core
