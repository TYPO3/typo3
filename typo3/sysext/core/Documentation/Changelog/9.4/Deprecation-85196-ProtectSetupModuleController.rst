.. include:: /Includes.rst.txt

===================================================
Deprecation: #85196 - Protect SetupModuleController
===================================================

See :issue:`85196`

Description
===========

This file is about third party usage (consumer that call the class as well as
signals or hooks depending on it) of :php:`TYPO3\CMS\Setup\Controller\SetupModuleController`.

A series of class properties changed visibility to protected.
They will trigger PHP :php:`E_USER_DEPRECATED` errors if called from outside:

* :php:`$OLD_BE_USER`
* :php:`$MOD_MENU`
* :php:`$MOD_SETTINGS`
* [not scanned] :php:`$content`
* :php:`$overrideConf`
* :php:`$languageUpdate`

These methods have been marked as deprecated and will be removed with TYPO3 v10:

* :php:`getFormProtection()`
* :php:`simulateUser()`


Impact
======

Calling one of the methods mentioned above or accessing one of the properties on an instance of
:php:`SetupModuleController` will trigger a PHP :php:`E_USER_DEPRECATED` error in TYPO3 v9 and a PHP fatal error in TYPO3 v10.


Affected Installations
======================

The extension scanner will find most usages, but may also find some false positives. The most
common property and method names like :php:`$content` are not registered and will not be found
if an extension uses that on an instance of :php:`SetupModuleController`.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.

.. index:: Backend, PHP-API, PartiallyScanned, ext:setup
