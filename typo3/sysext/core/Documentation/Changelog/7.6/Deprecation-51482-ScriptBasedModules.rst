
.. include:: ../../Includes.txt

==========================================
Deprecation: #51482 - Script-based modules
==========================================

See :issue:`51482`

Description
===========

Pseudo-modules that are registered via `ExtensionManagementUtility::addModulePath()` and
modules that are registered via `ExtensionManagementUtility::addModule()` using the fourth parameter
as a custom script-path have been marked as deprecated.

The method `ExtensionManagementUtility::addModulePath()` itself has been marked as deprecated.


Impact
======

All existing modules which are not registered via Routing will trigger a deprecation entry on registration
of the module and when calling the module directly.


Affected Installations
======================

All third-party extensions registering a wizard, module or route without using routeTarget or Routes.php,
which have been introduced with TYPO3 CMS 7.


Migration
=========

Use Configuration/Backend/Routes.php to register wizards and use
`ExtensionManagementUtility::addModule()` when registering a routePath option in the fifth parameter to
use the proper PSR-7 compatible way of registering and calling modules.
Make sure to use UriBuilder and `BackendUtility::getModuleUrl()` to link to these modules instead of
hard-linking to the script names.


.. index:: PHP-API, Backend
