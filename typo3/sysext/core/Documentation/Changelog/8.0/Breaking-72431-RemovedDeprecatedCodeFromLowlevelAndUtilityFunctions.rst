
.. include:: ../../Includes.txt

=============================================================================
Breaking: #72431 - Remove deprecated code from lowlevel and utility functions
=============================================================================

See :issue:`72431`

Description
===========

The following deprecated methods have been removed:

* `ConfigurationView->printContent()`
* `DatabaseIntegrityView->printContent()`
* `StringUtility::isLastPartOfString()`
* `Bootstrap->executeExtTablesAdditionalFile()`
* `DatabaseTreeDataProvider->emitDeprecatedPostProcessTreeDataSignal()`


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use calls to the methods above.
Instances which use TYPO3_extTableDef_script for TCA overrides.
Instances which use the signal `TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\TableConfiguration\\DatabaseTreeDataProvider`


Migration
=========

* `StringUtility::isLastPartOfString()` use endsWith() instead
* `Bootstrap->executeExtTablesAdditionalFile()` (TYPO3_extTableDef_script) Move your TCA overrides to Configuration/TCA/Overrides of your project specific extension, or slot the signal "tcaIsBeingBuilt" for further processing.
* `DatabaseTreeDataProvider->emitDeprecatedPostProcessTreeDataSignal()` Update the signal name to TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\DatabaseTreeDataProvider.

.. index:: PHP-API, TCA
