
.. include:: ../../Includes.txt

====================================================================
Deprecation: #73606 - Deprecate IconRegistry::getDeprecationSettings
====================================================================

See :issue:`73606`

Description
===========

`IconRegistry::getDeprecationSettings` has been marked as deprecated.


Impact
======

Using `IconRegistry::getDeprecationSettings()` will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance using a third-party extension using the PHP method above.

.. index:: PHP-API, Backend
