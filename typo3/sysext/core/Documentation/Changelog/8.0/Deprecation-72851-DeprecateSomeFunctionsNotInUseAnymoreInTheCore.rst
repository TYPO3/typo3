
.. include:: /Includes.rst.txt

=============================================================================
Deprecation: #72851 - Deprecate some functions not in use anymore in the core
=============================================================================

See :issue:`72851`

Description
===========

The following unused methods have been marked as deprecated:

`BackendUtility::processParams()`
`BackendUtility::makeConfigForm()`
`BackendUtility::titleAltAttrib()`
`BackendUtility::getSQLselectableList()`
`ContentObjectRenderer->processParams()`


Impact
======

Calling one of the aforementioned methods will trigger a deprecation log entry.


Affected Installations
======================

Instances with custom backend modules that use one of the aforementioned methods.

.. index:: PHP-API
