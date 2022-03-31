.. include:: /Includes.rst.txt

=====================================================
Deprecation: #85462 - Signal 'hasInstalledExtensions'
=====================================================

See :issue:`85462`

Description
===========

The usage of signal :php:`hasInstalledExtensions` of class
:php:`\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService`
has been marked as deprecated and will be removed in TYPO3 v10.

The signal is a duplication of :php:`afterExtensionInstall` that is also emitted during
extension installation.


Impact
======

Slots of this signal will get executed in TYPO3 v9 but will be abandoned with TYPO3 v10.


Affected Installations
======================

Extensions that register slots for the signal :php:`hasInstalledExtensions` of class
:php:`\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService`.


Migration
=========

Extensions should use the signal :php:`afterExtensionInstall` of class
:php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility` instead which is emitted after an
extension has been installed.

.. index:: Backend, LocalConfiguration, PHP-API, NotScanned, ext:extensionmanager
