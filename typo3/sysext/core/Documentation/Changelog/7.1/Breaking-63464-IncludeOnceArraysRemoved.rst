
.. include:: ../../Includes.txt

========================================================================
Breaking: #63464 - Remove include_once inclusions inside ModuleFunctions
========================================================================

See :issue:`63464`

Description
===========

The functionality to include PHP files within module functions (e.g. info module) via an `include_once` array
has been removed. The API did not use the include_once array anymore and certain places were marked as deprecated
since TYPO3 CMS 6.2. All module functions are using the common autoloading functionality via namespaced classes.

The following `include_once` arrays within the following modules have been removed:

* Web => Page
* Web => Page - New Content Element Wizard
* Web => Functions
* Web => Info
* Web => Template
* Web => Recycler
* User => Task Center
* System => Scheduler

Impact
======

Any non-API usage of the `include_once` array in any custom module function will fail.


Affected installations
======================

Any installation with an extension using the property `$include_once` to load additional files via direct access instead
of using the API via `ExtensionManagementUtility::insertModuleFunction()`.


Migration
=========

Use the autoloader to load any custom classes inside your code, or any hooks if available in the custom module functions
to include any file.


.. index:: PHP-API, Backend
