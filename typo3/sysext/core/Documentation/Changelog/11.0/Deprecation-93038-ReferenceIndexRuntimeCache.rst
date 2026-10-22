.. include:: /Includes.rst.txt

.. _deprecation-93038:

==================================================
Deprecation: #93038 - ReferenceIndex runtime cache
==================================================

See :issue:`93038`

Description
===========

Two methods of class :php-short:`\TYPO3\CMS\Core\Database\ReferenceIndex` have been marked as deprecated:

* :php-short:`\TYPO3\CMS\Core\Database\ReferenceIndex->enableRuntimeCache()`
* :php-short:`\TYPO3\CMS\Core\Database\ReferenceIndex->disableRuntimeCache()`


Impact
======

Calling these methods will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances with extensions calling above methods are affected. The extension
scanner locates candidates.


Migration
=========

The method calls can be dropped, cache handling is done internally.

.. index:: PHP-API, FullyScanned, ext:core
