
.. include:: /Includes.rst.txt

====================================================
Deprecation: #73516 - Various GeneralUtility methods
====================================================

See :issue:`73516`

Description
===========

The following methods within GeneralUtility have been marked as deprecated:

.. code-block:: php

	GeneralUtility::flushOutputBuffers()
	GeneralUtility::xmlGetHeaderAttribs()
	GeneralUtility::imageMagickCommand()

The second and third parameter of `GeneralUtility::getFileAbsFileName()` have been removed as well.


Impact
======

Calling any of the methods above will trigger a deprecation log entry. Calling `GeneralUtility::getFileAbsFileName()`
with the second and third parameter set will also trigger a deprecation log entry.


Affected Installations
======================

Any installation using any third-party extension calling any of these methods.


Migration
=========

For `GeneralUtility::flushOutputBuffers()` use `ob_clean()`.

For `GeneralUtility::imageMagickCommand()` use `CommandUtility::imageMagickCommand`.

.. index:: PHP-API
