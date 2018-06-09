.. include:: ../../Includes.txt

===================================================
Deprecation: #85196 - Protect SetupModuleController
===================================================

See :issue:`85196`

Description
===========

This file is about third party usage (consumer that call the class as well as
signals or hooks depending on it) of :php:`TYPO3\CMS\Setup\Controller\SetupModuleController`.

A series of class properties have been set to protected.
They will throw deprecation warnings if called public from outside:

* :php:`$OLD_BE_USER`
* :php:`$MOD_MENU`
* :php:`$MOD_SETTINGS`
* [not scanned] :php:`$content`
* :php:`$overrideConf`
* :php:`$languageUpdate`

These methods have been deprecated:

* :php:`getFormProtection()`
* :php:`simulateUser()`


Impact
======

Calling one of the above methods or accessing one of the above properties on an instance of
:php:`SetupModuleController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

The extension scanner will find most usages, but may also find some false positives. The most
common property and method names like :php:`$content` are not registered and will not be found
if an extension uses that on an instance of :php:`SetupModuleController`


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.

.. index:: Backend, PHP-API, PartiallyScanned, ext:setup