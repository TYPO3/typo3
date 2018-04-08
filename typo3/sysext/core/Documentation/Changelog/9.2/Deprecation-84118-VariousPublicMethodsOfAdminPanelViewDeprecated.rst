.. include:: ../../Includes.txt

=========================================================================
Deprecation: #84118 - Various public methods of AdminPanelView deprecated
=========================================================================

See :issue:`84118`

Description
===========

To clean up the admin panel and provide a new API various functions of the main class `AdminPanelView` have been marked
as deprecated:

* `getAdminPanelHeaderData`
* `isAdminModuleEnabled`
* `saveConfigOptions`
* `extGetFeAdminValue`
* `forcePreview`
* `isAdminModuleOpen`
* `extGetHead`
* `linkSectionHeader`
* `extGetItem`


Impact
======

Calling any of the mentioned methods triggers an `E_USER_DEPRECATED` PHP error.


Affected Installations
======================

Any installation that calls one of the above methods.


Migration
=========

Implement your own AdminPanel module by using the new API (see `AdminPanelModuleInterface`).

.. index:: Frontend, FullyScanned, ext:frontend