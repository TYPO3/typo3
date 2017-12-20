
.. include:: ../../Includes.txt

======================================================
Deprecation: #68098 - Deprecate GeneralUtility methods
======================================================

See :issue:`68098`

Description
===========

The following methods within `GeneralUtility` have been marked as deprecated and will be removed in TYPO3 CMS v8.

.. code-block:: php

	GeneralUtility::modifyHTMLColor()
	GeneralUtility::modifyHTMLColorAll()
	GeneralUtility::isBrokenEmailEnvironment()
	GeneralUtility::normalizeMailAddress()
	GeneralUtility::formatForTextarea()
	GeneralUtility::getThisUrl()
	GeneralUtility::cleanOutputBuffers()

The functionality `formatForTextarea()` was used in the older days to actually support IE4 and Netscape 3 properly
and can now safely be exchanged by `htmlspecialchars()`.


Impact
======

All extensions using these methods directly will throw a deprecation message.


Affected Installations
======================

Installations with extensions that use the methods above handling.


Migration
=========

Use corresponding functionality from `getIndpEnv()` instead of `getThisUrl()`.

For the other methods, you can re-implement the functionality yourself in your extension where needed.


.. index:: PHP-API
