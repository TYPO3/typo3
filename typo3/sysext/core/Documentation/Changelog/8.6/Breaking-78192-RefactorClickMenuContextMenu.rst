.. include:: /Includes.rst.txt

=====================================================
Breaking: #78192 - Refactor click menu (context menu)
=====================================================

See :issue:`78192`

Description
===========

Due to the refactoring and unification of the click/context-menu handling in the TYPO3 Backend, a few breaking changes have been introduced.

Classes removed
---------------

- :php:`\TYPO3\CMS\Backend\ClickMenu\ClickMenu`
- :php:`\TYPO3\CMS\Backend\ContextMenu\ContextMenuAction`
- :php:`\TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection`
- :php:`\TYPO3\CMS\Backend\ContextMenu\Pagetree\ContextMenuDataProvider`
- :php:`\TYPO3\CMS\Backend\ContextMenu\Pagetree\Extdirect\ContextMenuConfiguration`
- :php:`\TYPO3\CMS\Backend\Controller\ClickMenuController`
- :php:`\TYPO3\CMS\Impexp\Clickmenu` (replaced by new hook implementation: :php:`TYPO3\CMS\Impexp\Hook\ContextMenuModifyItemsHook`)
- :php:`\TYPO3\CMS\Impexp\Hook\ContextMenuDisableItemsHook`
- :php:`\TYPO3\CMS\Version\ClickMenu\VersionClickMenu`

ExtJS component removed
-----------------------

- The :js:`TYPO3.Components.PageTree.ContextMenu` component defined in contextmenu.js has been removed.
- The `contextMenuProvider` property as well as `enableContextMenu` and `openContextMenu` methods of the :js:`TYPO3.Components.PageTree.Tree` component have been removed.

Migration
^^^^^^^^^
Migrate your code to a requireJS module for custom click-menu actions.

ClickMenu requireJS component removed
-------------------------------------

The `TYPO3/CMS/Backend/ClickMenu` requireJS component (ClickMenu.js) has been removed.

Migration
^^^^^^^^^

Use the new requireJS component: `TYPO3/CMS/Backend/ContextMenu`.


Page TSConfig change
--------------------

The pagetree context-menu configuration in Page TSConfig has been removed (except for the `disableItems` part).
The list of available menu items is now provided by `ItemProviders` e.g. :php:`\TYPO3\CMS\Backend\ContextMenu\ItemProviders\PageProvider`.

The TSConfig options for disabling click-menu items has been streamlined.
Also some items names have changed (e.g. `new_wizard` is now called `newWizard`, `db_list` is now `openListModule`). Refer to the provider class for correct naming.

Migration
^^^^^^^^^

Migrate TSConfig from:

:typoscript:`options.contextMenu.folderList.disableItems` to :typoscript:`options.contextMenu.table.sys_file.disableItems`

:typoscript:`options.contextMenu.folderTree.disableItems` to :typoscript:`options.contextMenu.table.sys_file.tree.disableItems`

:typoscript:`options.contextMenu.pageList.disableItems` to :typoscript:`options.contextMenu.table.pages.disableItems`

:typoscript:`options.contextMenu.pageTree.disableItems` to :typoscript:`options.contextMenu.table.pages.tree.disableItems`



Hooks removed
-------------

The following two hooks have been removed:

- :php:`$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['backend']['contextMenu']['disableItems']`


Migration
^^^^^^^^^

Use the new ItemsProvider API to add or modify click-menu items.
See existing usage of the API in the core :php:`TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider` or :php:`\TYPO3\CMS\Beuser\ContextMenu\ItemProvider`.


Legacy Tree
-----------

Support for drag & drop menu for LegacyTree.js of pages has been dropped.


Changed markup (data attributes) for click menu
-----------------------------------------------

- `data-listFrame` has been replaced with the optional attribute `data-context` attribute. Context is set to "tree" for click-menus triggered from trees.
- for files, `data-table` now contains the real table name "sys_file" while before it contained the combined identifier e.g. `1:/fileadmin/file.jpg`.
   the `data-uid` attribute now contains the combined identifier of the file (before it was empty).
   Thus the `data-uid` attribute value is not always an int.
- the class which triggers the context-menu has changed from :js:`t3-js-clickmenutrigger` to :js:`t3js-contextmenutrigger`


Migration
^^^^^^^^^

To trigger click-menus for files, use the correct class-attribute as well as the table and uid data attributes. Replace `data-listFrame="0"` with `data-context="tree"`, `data-listFrame="1"` can be removed (it's a default context now).


Impact
======

Instantiating the PHP class will result in a fatal PHP error.
Accessing removed JavaScript properties will result in a JavaScript error.

Removed hooks will not influence the menu rendering process.

Affected Installations
======================

Any installation using the removed PHP classes, JS components or hooks.

Migration
=========

Adapt your code to the new click menu API.


.. index:: Backend, JavaScript, PHP-API, TSConfig
