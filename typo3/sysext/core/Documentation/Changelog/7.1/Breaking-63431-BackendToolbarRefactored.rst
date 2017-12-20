
.. include:: ../../Includes.txt

=============================================
Breaking: #63431 - Backend toolbar refactored
=============================================

See :issue:`63431`

Description
===========

The upper right backend toolbar has been refactored with TYPO3 CMS 7.0. A new PHP interface
and a new registration were introduced.


Impact
======

Extensions that are not adapted to the new interface will not show up in the toolbar anymore, but
will not throw a fatal PHP error.

Method BackendController::addToolbarItem() has been marked as deprecated.


Affected installations
======================

If a TYPO3 CMS instance uses extensions based on the old interface and registration, the
according items will no longer show up in the toolbar.


Migration
=========

Extensions must implement the new interface \TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface
and must be registered in $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'].

Warning: The new interface is not 100% finished, method `checkAccess()` will probably be
substituted by two other methods in later versions of TYPO3 CMS.


.. index:: PHP-API, Backend
