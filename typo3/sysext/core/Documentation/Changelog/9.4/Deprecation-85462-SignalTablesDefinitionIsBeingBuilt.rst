.. include:: /Includes.rst.txt

===========================================================
Deprecation: #85462 - Signal 'tablesDefinitionIsBeingBuilt'
===========================================================

See :issue:`85462`

Description
===========

The usage of signal :php:`tablesDefinitionIsBeingBuilt` of class
:php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility`
has been marked as deprecated and will be removed in TYPO3 v10.

The signal is a duplication of :php:`tablesDefinitionIsBeingBuilt` of class
:php:`\TYPO3\CMS\Install\Service\SqlExpectedSchemaService` that is now also emitted during
extension installation.


Impact
======

Slots of this signal will get executed in TYPO3 v9 but will be abandoned with TYPO3 v10. If a slot provides
SQL definitions a PHP :php:`E_USER_DEPRECATED` error is triggered.


Affected Installations
======================

Extensions that register slots for the signal :php:`tablesDefinitionIsBeingBuilt` of class
:php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility`.


Migration
=========

Extensions should use the signal :php:`tablesDefinitionIsBeingBuilt` of class
:php:`\TYPO3\CMS\Install\Service\SqlExpectedSchemaService` instead which is now emitted during an
extension installation.

.. index:: Backend, LocalConfiguration, PHP-API, NotScanned, ext:extensionmanager
