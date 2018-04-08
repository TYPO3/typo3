.. include:: ../../Includes.txt

===============================================================================================================
Deprecation: #84641 - Deprecated AdminPanel related methods and properties in FrontendBackendUserAuthentication
===============================================================================================================

See :issue:`84641`

Description
===========

The admin panel has been extracted into an own extension. To enable users to de-activate the admin panel completely,
the hard coupling between the extension and other parts of the core had to be resolved. The admin panel now takes care
of its own initialization and provides API methods related to its functionality.
The following API methods and properties located in `FrontendBackendUserAuthentication` have been marked as deprecated:

* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$adminPanel`
* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$extAdminConfig`
* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$extAdmEnabled`

* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::initializeAdminPanel()`
* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::initializeFrontendEdit()`
* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::isFrontendEditingActive()`
* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::displayAdminPanel()`
* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::isAdminPanelVisible()`


Impact
======

Using any of the methods will trigger a deprecation warning.


Affected Installations
======================

Any installation directly calling one of the mentioned methods or properties.


Migration
=========

* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$adminPanel` - use `MainController` of EXT:adminpanel instead
* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$extAdminConfig` - load directly from TSConfig if needed
* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$extAdmEnabled` - check directly against TSConfig if necessary

Both initialization methods `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::initializeAdminPanel` and
`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::initializeFrontendEdit` were rewritten as PSR-15 middlewares,
remove any calls as they are not necessary anymore.

* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::isFrontendEditingActive` and `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::isAdminPanelVisible` - check against TSFE directly

* `\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::displayAdminPanel` - use `MainController::render()` instead

.. index:: Frontend, PHP-API, PartiallyScanned
