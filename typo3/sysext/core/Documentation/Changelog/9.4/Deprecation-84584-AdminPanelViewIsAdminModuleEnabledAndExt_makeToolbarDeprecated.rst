.. include:: ../../Includes.txt

=========================================================================================
Deprecation: #84584 - AdminPanelView: isAdminModuleEnabled and ext_makeToolbar deprecated
=========================================================================================

See :issue:`84584`

Description
===========

Due to the complete refactoring of the admin panel, the following methods have been deprecated:

- :php:`\TYPO3\CMS\Adminpanel\View\AdminPanelView::isAdminModuleEnabled()`
- :php:`\TYPO3\CMS\Adminpanel\View\AdminPanelView::ext_makeToolBar()`


Impact
======

Calling either one of the methods results in a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Installations calling either one of the methods mentioned.


Migration
=========

Migrate :php:`\TYPO3\CMS\Adminpanel\View\AdminPanelView::isAdminModuleEnabled()`:

- Refactor your admin panel modules to the new API (using :php:`AbstractModule` / :php:`AdminPanelModuleInterface`) and check via :php:`Module->isEnabled()`.
- When using this with existing admin panel modules call :php:`isEnabled()` on the new module instance instead.

Migrate :php:`\TYPO3\CMS\Adminpanel\View\AdminPanelView::ext_makeToolBar()`:

- When creating custom edit toolbars, build them by yourself matching your templates and styles - you can use :php:`\TYPO3\CMS\Adminpanel\Service\EditToolbarService::createToolbar()` as an inspiration on how to do so.

.. index:: Frontend, PHP-API, FullyScanned, ext:adminpanel
