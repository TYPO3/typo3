
.. include:: /Includes.rst.txt

=========================================================================
Deprecation: #68748 - Deprecate AbstractContentObject::getContentObject()
=========================================================================

See :issue:`68748`

Description
===========

The method has been renamed to `getContentObjectRenderer()`. The old method name is
still present as a deprecated alias, which will be removed in TYPO3 v9.


Impact
======

Calling this method will trigger a deprecation log entry.


Affected Installations
======================

Any extensions calling `getContentObject()`.


Migration
=========

Replace calls to `getContentObject()` with `getContentObjectRenderer()`.

.. index:: PHP-API, Frontend
