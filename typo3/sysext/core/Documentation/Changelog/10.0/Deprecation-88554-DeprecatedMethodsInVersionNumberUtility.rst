.. include:: /Includes.rst.txt

================================================================
Deprecation: #88554 - Deprecated methods in VersionNumberUtility
================================================================

See :issue:`88554`

Description
===========

The following methods of :php:`\TYPO3\CMS\Core\Utility\VersionNumberUtility` have been marked as deprecated and will be removed in
TYPO3 11.0:

* :php:`convertIntegerToVersionNumber`
* :php:`splitVersionRange`
* :php:`raiseVersionNumber`


Impact
======

Calling the methods :php:`convertIntegerToVersionNumber`, :php:`splitVersionRange` or :php:`raiseVersionNumber` of
:php:\TYPO3\CMS\Core\Utility\VersionNumberUtility` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations that call the mentioned methods.


Migration
=========

Implement the methods in your custom code.

.. index:: PHP-API, FullyScanned, ext:core
