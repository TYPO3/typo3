.. include:: /Includes.rst.txt

================================================================================
Breaking: #81171 - Edit ability of TypoScript template in EXT:tstemplate removed
================================================================================

See :issue:`81171`

Description
===========

Editing "Constants" and "Setup" of templates in the backend template module has been
refactored to use FormEngine field rendering instead of an own solution.


Impact
======

Rendering the edit form for the fields "Constants" and "Setup" is now done by FormEngine, triggered
by EditDocumentController. The following code has been removed without substitution:

* Public method :php:`TypoScriptTemplateInformationModuleFunctionController->processTemplateRowAfterLoading()`
* Public method :php:`TypoScriptTemplateInformationModuleFunctionController->processTemplateRowBeforeSaving()`
* Hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postTCEProcessingHook']`
* Hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postOutputProcessingHook']`
* Public property :php:`TypoScriptTemplateModuleController::$e`
* Hook class :php:`\TYPO3\CMS\T3editor\Hook\TypoScriptTemplateInfoHook`

Due to code removal the following features were removed without substitution:

* "Include TypoScript file content" functionality
* Saving the form via CTRL/CMD+S keystroke


Affected Installations
======================

All installations are affected.


Migration
=========

As the hooks `postTCEProcessingHook` and `postOutputProcessingHook` were removed without
substitution, any functionality has to be migrated to custom FormEngine render types.

.. index:: Backend, FullyScanned, PHP-API
