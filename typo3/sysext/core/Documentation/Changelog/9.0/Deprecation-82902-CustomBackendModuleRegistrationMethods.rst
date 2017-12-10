.. include:: ../../Includes.txt

================================================================
Deprecation: #82902 - Custom Backend Module registration methods
================================================================

See :issue:`82902`

Description
===========

The internal API to register backend modules via :php:`ExtensionManagementUtility::configureModule()` and
:php:`configureModuleFunction` has been marked as deprecated.

It was solely introduced to allow script-based dispatching of backend modules used in TYPO3 v6.2 which
had multiple entry-points (mod1/conf.php and mod1/index.php).

Since TYPO3 v7 Backend Routing is available, thus the old registration API is no longer needed.

Impact
======

Registering a :php:`configureModuleFunction` will trigger a deprecation warning.

Calling :php:`ExtensionManagementUtility::configureModule()` will trigger a deprecation warning.


Affected Installations
======================

Installations with legacy and/or custom Backend modules in extensions.


Migration
=========

Use either :php:`ExtensionManagementUtility::addModule()` or Extbase's
:php:`ExtensionUtility::registerModule()` to register a module, always providing a `routeTarget`.

.. index:: Backend, PHP-API, PartiallyScanned
