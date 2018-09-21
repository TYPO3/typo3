.. include:: ../../Includes.txt

===============================================
Deprecation: #86366 - Methods in AbstractUpdate
===============================================

See :issue:`86366`

Description
===========

To ease the update pain a compatibility layer for AbstractUpdate based
upgrade wizards will be implemented, that allows running "old" wizards
on CLI (enabling extension authors to support both v8 and v9 with one
wizard).

The following methods have been marked as deprecated and will be removed with TYPO3 v10:

* AbstractUpdate::getTitle()
* AbstractUpdate::setTitle()
* AbstractUpdate::getIdentifier()
* AbstractUpdate::setIdentifier()
* AbstractUpdate::getDescription()
* AbstractUpdate::executeUpdate()
* AbstractUpdate::updateNecessary()
* AbstractUpdate::getPrerequisites()
* AbstractUpdate::setOutput()
* AbstractUpdate::shouldRenderWizard()
* AbstractUpdate::checkIfTableExists()
* AbstractUpdate::installExtensions()
* AbstractUpdate::markWizardAsDone()
* AbstractUpdate::isWizardDone()

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

.. index:: Backend, CLI, PHP-API, FullyScanned, ext:install
