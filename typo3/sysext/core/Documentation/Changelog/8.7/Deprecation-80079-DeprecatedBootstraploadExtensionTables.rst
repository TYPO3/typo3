.. include:: /Includes.rst.txt

======================================================================
Deprecation: #80079 - Deprecated method Bootstrap::loadExtensionTables
======================================================================

See :issue:`80079`

Description
===========

The internal method :php:`TYPO3\CMS\Core\Core\Bootstrap::loadExtensionTables()` has been deprecated and should not be used any longer.


Impact
======

Calling the deprecated :php:`Bootstrap::loadExtensionTables()` method will trigger a deprecation log entry.


Affected Installations
======================

Any installation using the mentioned method :php:`Bootstrap::loadExtensionTables()`.
Please note that this method is marked as internal and should not be called at all from outside the TYPO3 core.


Migration
=========

If you need to call the internal Bootstrap method, you can use :php:`Bootstrap::loadBaseTca()` and :php:`Bootstrap::loadExtTables()` now.
Please note that both methods are marked as internal and don't belong to public TYPO3 core API.
This means that the methods can be adjusted anytime by the core itself.

.. index:: Backend, PHP-API
