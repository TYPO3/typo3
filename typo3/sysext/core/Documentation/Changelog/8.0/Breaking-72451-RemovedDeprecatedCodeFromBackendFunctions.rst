
.. include:: /Includes.rst.txt

=================================================================
Breaking: #72451 - Removed deprecated code from backend functions
=================================================================

See :issue:`72451`

Description
===========

Removed deprecated code from backend functions

The following methods have been removed:

`ClickMenu::wrapColorTableCM`
`ClickMenu::excludeIcon`
`ContextMenuAction::getClass`
`ContextMenuAction::setClass`
`SuggestWizardDefaultReceiver::getIcon`
`BackendUserAuthentication::checkCLIuser`
`PageFunctionsController::printContent`
`InfoModuleController::printContent`

The following display condition option have been removed:

* Evaluates conditions concerning extensions
* Evaluates whether the field is a value for the default language.

The property `$OS` has been removed from `BackendUserAuthentication`

The property `$doc` has been removed from `InfoModuleController`


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use custom calls to one of the methods above.


Migration
=========

For property `$OS` use the constant TYPO3_OS directly.

.. index:: PHP-API, Backend
