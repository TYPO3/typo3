
.. include:: ../../Includes.txt

==================================================================
Breaking: #72419 - Remove deprecated code from backend controllers
==================================================================

See :issue:`72419`

Description
===========

Remove deprecated code from backend controllers

The following methods have been removed:

`BackendController::getPageRenderer`
`BackendController::addToolbarItem`
`ClickMenuController::init`
`ClickMenuController::main`
`ClickMenuController::printContent`
`ElementInformationController::printContent`
`MoveElementController::printContent`
`NewContentElementController::getWizardItems`
`DummyController::printContent`
`EditDocumentController::printContent`
`EditDocumentController::editRegularContentFromId`
`FileSystemNavigationFrameController::printContent`
`LoginFramesetController::printContent`
`NewRecordController::printContent`
`SimpleDataHandlerController::finish`
`ColorpickerController::printContent`
`EditController::closeWindow`
`RteController::printContent`
`TableController::printContent`


The following classes have been removed completely:

`ListFrameLoaderController`
`PageTreeNavigationController`


Impact
======

Using the one of the methods or classes above will result in a fatal error.


Affected Installations
======================

Instances which use custom calls to the methods or classes above.


Migration
=========

For `BackendController::addToolbarItem` Toolbar items are registered in $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'] now.

.. index:: PHP-API, Backend
