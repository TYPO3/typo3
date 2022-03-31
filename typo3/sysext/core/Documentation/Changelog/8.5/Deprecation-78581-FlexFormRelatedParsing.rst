.. include:: /Includes.rst.txt

===============================================
Deprecation: #78581 - Flex form related parsing
===============================================

See :issue:`78581`

Description
===========

Three flex form data structure related parsing methods have been deprecated:

* :php:`BackendUtility::getFlexFormDS()`
* :php:`GeneralUtility::resolveSheetDefInDS()`
* :php:`GeneralUtility::resolveAllSheetsInDS()`


Impact
======

Calling those PHP methods will trigger a deprecation log entry.


Affected Installations
======================

Extensions calling one of the above methods.


Migration
=========

:php:`BackendUtility::getFlexFormDS()` has been refactored to a combination of two methods
:php:`FlexFormTools->getDataStructureIdentifier()` and :php:`FlexFormTools->parseDataStructureByIdentifier()`.
The two methods are heavily documented and the combination works in many cases just as before. Read the method
comments for a detailed description of their purpose.

Warning: The hook :php:`getFlexFormDSClass` within :php:`BackendUtility::getFlexFormDS()` is no longer called
by the core. Please refer to the according "Breaking" document for details on this topic.

.. index:: PHP-API, FlexForm, Backend
