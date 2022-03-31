
.. include:: /Includes.rst.txt

===============================================================
Breaking: #59659 - Removal of deprecated code in sysext backend
===============================================================

See :issue:`59659`

Description
===========

Flexforms
---------

Flexform xml that still contains the old "<tx_templavoila><title>" code has to be adapted.
The "<tx_templavoila>" elements needs to be removed.

Wizard registration
-------------------

Wizard registration in TCA must not contain the "script=some/path/script.php" definition anymore.
The new API for registering wizards is to set "module[name]=module_name".

Removed PHP methods
-------------------

* `AbstractRecordList::writeBottom()` is removed without replacement. The functionality is not needed anymore.
* `SpriteGenerator::setOmmitSpriteNameInIconName()` is removed in favor of `setOmitSpriteNameInIconName()`
* `DocumentTemplate::isCMlayers()` is removed without replacement. The functionality is obsolete.
* `DocumentTemplate::getFileheader()` is removed. Use `getResourceHeader()` instead.
* `BackendUtility::displayWarningMessages()` is removed without replacement. The functionality was moved to ext:aboutmodules.
* `IconUtility::getIconImage()` is removed without replacement. Use sprite icon API instead.
* `PageLayoutView::getSelectedBackendLayoutUid()` is removed. Use `BackendLayoutView::getSelectedCombinedIdentifier()` instead.
* `ClickMenu::menuItemsForClickMenu()` is removed without replacement. The functionality is obsolete.

Removed JS functions
--------------------

* `showClickmenu_noajax()` is removed. Use `Clickmenu.ajax = false; showClickmenu_raw();` instead.
* `setLayerObj()` is replaced with `Clickmenu.populateData()`.
* `hideEmpty()` is replaced with `Clickmenu.hideAll()`.
* `hideSpecific()` is replaced with `Clickmenu.hide()`. E.g. `Clickmenu.hide('contentMenu1');`
* `showHideSelectorBoxes()` is replaced with `toggleSelectorBoxes()`.

Impact
======

A call to any of the aforementioned methods by third party code will result in
a fatal PHP error.


Affected installations
======================

Any installation which contains third party code still using these deprecated methods.


Migration
=========

Replace the calls with the suggestions outlined above.


.. index:: PHP-API, Backend
