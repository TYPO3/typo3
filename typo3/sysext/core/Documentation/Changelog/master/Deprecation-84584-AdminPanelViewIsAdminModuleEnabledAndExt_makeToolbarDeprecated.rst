.. include:: ../../Includes.txt

=========================================================================================
Deprecation: #84584 - AdminPanelView: isAdminModuleEnabled and ext_makeToolbar deprecated
=========================================================================================

See :issue:`84584`

Description
===========

Due to the complete refactoring of the admin panel, the following methods have been deprecated:

- `\TYPO3\CMS\Adminpanel\View\AdminPanelView::isAdminModuleEnabled`
- `\TYPO3\CMS\Adminpanel\View\AdminPanelView::ext_makeToolBar`


Impact
======

Calling either one of the methods results in a deprecation warning.


Affected Installations
======================

Installations calling either of the methods.


Migration
=========

Migrate `\TYPO3\CMS\Adminpanel\View\AdminPanelView::isAdminModuleEnabled`:

- Refactor your admin panel modules to the new API (using AbstractModule / AdminPanelModuleInterface) and check via "Module->isEnabled()"
- When using this with existing admin panel modules call "isEnabled" on the new module instance instead

Migrate `\TYPO3\CMS\Adminpanel\View\AdminPanelView::ext_makeToolBar`:
- When building your custom edit toolbars, build them yourself matching your templates and styles - you can use `\TYPO3\CMS\Adminpanel\Service\EditToolbarService::createToolbar` as an inspiration on how to do so.

.. index:: Frontend, PHP-API, FullyScanned, ext:adminpanel
