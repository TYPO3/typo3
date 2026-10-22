.. include:: /Includes.rst.txt

.. _deprecation-84641:

===============================================================================================================
Deprecation: #84641 - Deprecated AdminPanel related methods and properties in FrontendBackendUserAuthentication
===============================================================================================================

See :issue:`84641`

Description
===========

The admin panel has been extracted into an own extension. To enable users to de-activate the admin panel completely,
the hard coupling between the extension and other parts of the core had to be resolved. The admin panel now takes care
of its own initialization and provides API methods related to its functionality.
The following API methods and properties located in :php-short:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication` have been marked as deprecated:

* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$adminPanel`
* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$extAdminConfig`
* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$extAdmEnabled`

* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::initializeAdminPanel()`
* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::initializeFrontendEdit()`
* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::isFrontendEditingActive()`
* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::displayAdminPanel()`
* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::isAdminPanelVisible()`


Impact
======

Using any of the methods will trigger a deprecation warning.


Affected Installations
======================

Any installation directly calling one of the mentioned methods or properties.


Migration
=========

* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$adminPanel` - use `MainController` of EXT:adminpanel instead
* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$extAdminConfig` - load directly from TSConfig if needed
* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::$extAdmEnabled` - check directly against TSConfig if necessary

Both initialization methods :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::initializeAdminPanel` and
:php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::initializeFrontendEdit` were rewritten as PSR-15 middlewares,
remove any calls as they are not necessary anymore.

* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::isFrontendEditingActive` and :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::isAdminPanelVisible` - check against TSFE directly

* :php:`\TYPO3\CMS\Backend\FrontendBackendUserAuthentication::displayAdminPanel` - use `MainController::render()` instead

.. index:: Frontend, PHP-API, PartiallyScanned
