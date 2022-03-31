.. include:: /Includes.rst.txt

============================================================================================
Deprecation: #87613 - Deprecate \\TYPO3\\CMS\\Extbase\\Utility\\TypeHandlingUtility::hex2bin
============================================================================================

See :issue:`87613`

Description
===========

:php:`\TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::hex2bin` has been marked as deprecated and will be removed in TYPO3 11.0.


Impact
======

Calling :php:`\TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::hex2bin` will trigger PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations that call :php:`\TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::hex2bin`.


Migration
=========

Use the native php function :php:`hex2bin` instead.

.. index:: PHP-API, FullyScanned, ext:extbase
