
.. include:: /Includes.rst.txt

=============================================================
Breaking: #72473 - Removed deprecated miscellaneous functions
=============================================================

See :issue:`72473`

Description
===========

Removed deprecated miscellaneous functions

The following methods have been removed:

`FlexFormTools::getAvailableLanguages`
`AbstractPlugin::pi_list_searchBox`
`ImportExportController::printContent`
`SearchFormController::checkExistance`
`SearchFormController::checkExistence`
`SchedulerModuleController::render`
`SchedulerModuleController::checkDate`
`SetupModuleController::printContent`
`TypoScriptTemplateModuleController::printContent`
`VersionModuleController::printContent`

Last parameter `addTofeInterface` for `ExtensionManagementUtility::addTCAcolumns` has been removed.

The following options in the Install Tool have been removed:

`FE\strictFormmail`
`FE\secureFormmail`
`FE\formmailMaxAttachmentSize`
`SC_OPTIONS\GLOBAL\softRefParser_GL'`


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.

Using one of the options from the Install Tool won't have any effect anymore.


Affected Installations
======================

Instances which use one of the methods above or use one of the removed Install Tool options.

.. index:: PHP-API
