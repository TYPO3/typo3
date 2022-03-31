.. include:: /Includes.rst.txt

=========================================================================
Deprecation: #83116 - Caching framework wrapper methods in BackendUtility
=========================================================================

See :issue:`83116`

Description
===========

The methods :php:`BackendUtility::getHash()` and :php:`BackendUtility::storeHash()` have been marked as
deprecated.


Impact
======

Calling the methods will trigger a deprecation warning.


Affected Installations
======================

Any extension using the methods in custom PHP code.


Migration
=========

Use the Caching Framework directly, as the methods now only act as wrapper methods.

.. index:: PHP-API, Backend, FullyScanned
