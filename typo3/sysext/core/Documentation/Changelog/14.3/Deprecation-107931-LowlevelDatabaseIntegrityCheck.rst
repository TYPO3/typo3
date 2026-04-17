.. include:: /Includes.rst.txt

.. _deprecation-107931-1775647667:

============================================================
Deprecation: #107931 - Lowlevel DatabaseIntegrityCheck class
============================================================

See :issue:`107931`

Description
===========

The class :php:`\TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck` has been
deprecated and will be removed in TYPO3 v15.0.

The class is no longer used internally by TYPO3 and should not be relied upon
by extensions.

Impact
======

Using :php-short:`\TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck` will trigger
a PHP :php:`E_USER_DEPRECATED` error. The class will be removed in TYPO3 v15.0.

Affected installations
======================

TYPO3 installations with extensions that use
:php-short:`\TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck` directly.

Migration
=========

Extensions that rely on this class should implement the necessary functionality
themselves.

.. index:: PHP-API, FullyScanned, ext:lowlevel
