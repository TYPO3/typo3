.. include:: ../../Includes.txt

================================================================
Deprecation: #85462 - Signal 'hasInstalledExtensions' deprecated
================================================================

See :issue:`85462`

Description
===========

The usage of signal :php:`hasInstalledExtensions` of class
:php:`\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService`
is marked as deprecated and will be removed in v10.

The signal is a duplication of :php:`afterExtensionInstall` that is also emitted during an
extension installation.


Impact
======

Slots of this signal will get executed in v9 but will not get emitted with v10.


Affected Installations
======================

Extensions that register slots for the signal :php:`hasInstalledExtensions` of class
:php:`\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService`.


Migration
=========

Extensions should use the signal :php:`afterExtensionInstall` of class
:php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility` instead which is fired after an
extension has been installed.

.. index:: Backend, LocalConfiguration, PHP-API, NotScanned, ext:extensionmanager
