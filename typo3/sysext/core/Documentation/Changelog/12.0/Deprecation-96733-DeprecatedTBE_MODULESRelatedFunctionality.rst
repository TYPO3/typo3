.. include:: /Includes.rst.txt

.. _deprecation-96733:

==================================================================
Deprecation: #96733 - Deprecated TBE_MODULES related functionality
==================================================================

See :issue:`96733`

Description
===========

Due to the removal of global array :php:`$TBE_MODULES`
(see :doc:`breaking changelog <../12.0/Breaking-96733-RemovedSupportForModuleHandlingBasedOnTBE_MODULES>`),
the following related methods have been deprecated:

- :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->modAccess()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::isModuleSetInTBE_MODULES()`

Impact
======

Calling mentioned methods will trigger a PHP :php:`E_USER_DEPRECATED` error.
The extension scanner will report usages.

Affected Installations
======================

All installations calling mentioned methods in custom extension code.

Migration
=========

Use the new :php:`ModuleProvider` API (see :doc:`feature changelog <../12.0/Feature-96733-NewBackendModuleRegistrationAPI>`) instead.

Replace :php:`BackendUserAuthentication->modAccess()` with :php:`ModuleProvider->accessGranted()`.

Replace :php:`BackendUtility::isModuleSetInTBE_MODULES()` with :php:`ModuleProvider->isModuleRegistered()`.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
