.. include:: ../../Includes.txt

======================================================================
Deprecation: #85462 - Signal 'tablesDefinitionIsBeingBuilt' deprecated
======================================================================

See :issue:`85462`

Description
===========

The usage of signal :php:`tablesDefinitionIsBeingBuilt` of class
:php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility`
is marked as deprecated and will be removed in v10.

The signal is a duplication of :php:`tablesDefinitionIsBeingBuilt` of class
:php:`\TYPO3\CMS\Install\Service\SqlExpectedSchemaService` that is now also emitted during an
extension installation.


Impact
======

Slots of this signal will get executed in v9 but will not get emitted with v10. If a slot provides
SQL definitions a deprecation error is triggered.


Affected Installations
======================

Extensions that register slots for the signal :php:`tablesDefinitionIsBeingBuilt` of class
:php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility`.


Migration
=========

Extensions should use the signal :php:`tablesDefinitionIsBeingBuilt` of class
:php:`\TYPO3\CMS\Install\Service\SqlExpectedSchemaService` instead which is now fired during an
extension installation.

.. index:: Backend, LocalConfiguration, PHP-API, NotScanned, ext:extensionmanager
