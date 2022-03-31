
.. include:: /Includes.rst.txt

==========================================================
Breaking: #72405 - Removed traditional BE modules handling
==========================================================

See :issue:`72405`

Description
===========

The traditional way of registering backend modules done via custom `mod1/index.php` and `mod1/conf.php` has been removed.


Impact
======

Calling `ExtensionManagementUtility::addModulePath()` will result in a fatal error. Additionally, all modules that
are registered via `ExtensionManagementUtility::addModule()` and setting a path will not be registered properly
anymore.

`$TBE_MODULES['_PATHS']` is always empty now. Additionally, the options `script` and `navFrameScript` and
`navFrameScriptParam` will have no effect anymore when registering a module.


Affected Installations
======================

Any installation using an extension that registers a module via the traditional way using standalone scripts.


Migration
=========

Use the option `routeTarget` when registering a module, and PSR-7 equivalent entry-points in module controllers.

.. index:: PHP-API, Backend
