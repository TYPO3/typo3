.. include:: /Includes.rst.txt

===============================================
Deprecation: #86366 - Methods in AbstractUpdate
===============================================

See :issue:`86366`

Description
===========

To ease the update pain a compatibility layer for AbstractUpdate based
upgrade wizards has been implemented, that allows running "old" wizards
on CLI (enabling extension authors to support both TYPO3 v8 and TYPO3 v9 with one
wizard).

The following methods of class :php:`TYPO3\CMS\Install\Updates\AbstractUpdate` have been marked as deprecated and will be removed with TYPO3 v10:

* [not scanned] :php:`getTitle()`
* [not scanned] :php:`setTitle()`
* [not scanned] :php:`getIdentifier()`
* [not scanned] :php:`setIdentifier()`
* [not scanned] :php:`getDescription()`
* :php:`executeUpdate()`
* :php:`updateNecessary()`
* :php:`getPrerequisites()`
* :php:`setOutput()`
* :php:`shouldRenderWizard()`
* :php:`checkIfTableExists()`
* :php:`installExtensions()`
* :php:`markWizardAsDone()`
* :php:`isWizardDone()`

The class itself has also been marked as deprecated, construction will trigger a PHP :php:`E_USER_DEPRECATED` error.

Impact
======

Calling the mentioned methods through an extended class will trigger a PHP :php:`E_USER_DEPRECATED` error.

All UpdateWizards extending AbstractUpdate gained cli capability since :issue:`86076`.

Affected Installations
======================

Each instance with custom update wizards that extend AbstractUpdate.

Migration
=========

Use the interfaces instead the abstract class to define the capabilities of the Upgrade Wizard class.
See https://docs.typo3.org/typo3cms/extensions/core/latest/Changelog/9.4/Feature-86076-NewAPIForUpgradeWizards.html.

.. index:: Backend, CLI, PHP-API, PartiallyScanned, ext:install
