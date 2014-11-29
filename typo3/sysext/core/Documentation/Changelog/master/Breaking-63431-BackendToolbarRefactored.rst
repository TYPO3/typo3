=============================================
Breaking: #63431 - Backend toolbar refactored
=============================================

Description
===========

The upper right backend toolbar was refactored with TYPO3 CMS 7.0. A new PHP interface
and a new registration was introduced.

Impact
======

Extensions not adapted to the new interface will not show up in the toolbar anymore, but
will not throw a fatal PHP error.

Method BackendController::addToolbarItem() is deprecated.

Affected installations
======================

If a TYPO3 CMS instance uses extensions based on the old interface and registration, the
according items will vanish from the toolbar.

Migration
=========

Extensions must implement the new interface \TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface
and must register in $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'].

Warning: The new interface is not 100% finished, method checkAccess() will probably be
substituted by two other methods in later TYPO3 CMS versions.
