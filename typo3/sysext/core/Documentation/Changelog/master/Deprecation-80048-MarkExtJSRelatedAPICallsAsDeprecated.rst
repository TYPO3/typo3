.. include:: ../../Includes.txt

================================================================
Deprecation: #80048 - Mark ExtJS related API calls as deprecated
================================================================

See :issue:`80048`

Description
===========

The usage of ExtJS has been marked as deprecated. Therefore the following methods of :php:`ExtensionManagementUtility` have been marked as deprecated:

- :php:`addExtJSModule`
- :php:`registerExtDirectComponent`


Impact
======

Calling any of the PHP methods will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation working with custom extensions that use any of these  methods.


Migration
=========

All of the functionality is obsolete or outdated and should be handled differently from now on:

1. Use :php:`ExtensionManagementUtility::addModule` instead of :php:`addExtJSModule`.

2. Some ajax routes_ instead of ExtDirect.

.. _routes: https://docs.typo3.org/typo3cms/InsideTypo3Reference/CoreArchitecture/Backend/Routing/Index.html

.. index:: Backend, PHP-API
