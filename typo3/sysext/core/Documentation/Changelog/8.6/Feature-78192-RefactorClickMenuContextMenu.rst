.. include:: /Includes.rst.txt

====================================================
Feature: #78192 - Refactor click menu (context menu)
====================================================

See :issue:`78192`

Description
===========

Click-menu (context-menu) handling has been refactored and unified.
The ExtJS/ExtDirect click-menu used on the page tree has been replaced with a jQuery based implementation.
The same context-menu implementation is used in all places in the Backend (page tree, page module, list module, file list, folder tree...).

Context-menu rendering flow
---------------------------

The context-menu is shown after click on the HTML element which has `class="t3js-contextmenutrigger"` together with `data-table`, `data-uid` and optional `data-context` attributes.

The JavaScript click event handler is implemented in the `TYPO3/CMS/Backend/ContextMenu` requireJS module. It takes the data attributes mentioned above and executes an ajax call to the :php:`\TYPO3\CMS\Backend\Controller\ContextMenuController->getContextMenuAction()`.

:php:`ContextMenuController` asks :php:`\TYPO3\CMS\Backend\ContextMenu\ContextMenu` to generate an array of items. ContextMenu builds a list of available item providers by asking each whether it can provide items (:php:`->canHandle()`), and what priority it has (:php:`->getPriority()`).

Custom item providers can be registered in :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders']`. They must implement :php:`\TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface` and can extend :php:`\TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider`.

A list of providers is sorted by priority, and then each provider is asked to add items. The generated array of items is passed from an item provider with higher priority to a provider with lower priority.

After that, a compiled list of items is returned to the :php:`ContextMenuController` which passes it back to the ContextMenu.js as JSON.

Example of the JSON response:

.. code-block:: javascript

    {
       "view":{
          "type":"item",
          "label":"Show",
          "icon":"<span class=\"t3js-icon icon icon-size-small icon-state-default icon-actions-document-view\" data-identifier=\"actions-document-view\">\n\t<span class=\"icon-markup\">\n<img src=\"\/typo3\/sysext\/core\/Resources\/Public\/Icons\/T3Icons\/actions\/actions-document-view.svg\" width=\"16\" height=\"16\" \/>\n\t<\/span>\n\t\n<\/span>",
          "additionalAttributes":{
             "data-preview-url":"http:\/\/typo37.local\/index.php?id=47"
          },
          "callbackAction":"viewRecord"
       },
       "edit":{
          "type":"item",
          "label":"Edit",
          "icon":"",
          "additionalAttributes":[
          ],
          "callbackAction":"editRecord"
       },
       "divider1":{
          "type":"divider",
          "label":"",
          "icon":"",
          "additionalAttributes":[

          ],
          "callbackAction":""
       },
       "more":{
          "type":"submenu",
          "label":"More options...",
          "icon":"",
          "additionalAttributes":[

          ],
          "callbackAction":"openSubmenu",
          "childItems":{
             "newWizard":{
                "type":"item",
                "label":"'Create New' wizard",
                "icon":"",
                "additionalAttributes":{
                },
                "callbackAction":"newContentWizard"
             }
          }
       }
    }

Based on the JSON data ContextMenu.js is rendering a context-menu. If one of the items is clicked, the according JS `callbackAction` is executed on the :js:`TYPO3/CMS/Backend/ContextMenuActions` JS module or other modules defined in the `additionalAttributes['data-callback-module']`.

For example usage of this API see:

- Beuser item provider :php:`\TYPO3\CMS\Beuser\ContextMenu\ItemProvider` and requireJS module :js:`TYPO3/CMS/Beuser/ContextMenuActions`
- Impexp item provider :php:`\TYPO3\CMS\Impexp\ContextMenu\ItemProvider` and requireJS module :js:`TYPO3/CMS/Impexp/ContextMenuActions`
- Version item provider :php:`\TYPO3\CMS\Version\ContextMenu\ItemProvider` and requireJS module :js:`TYPO3/CMS/Version/ContextMenuActions`
- Version item provider :php:`\TYPO3\CMS\Version\ContextMenu\ItemProvider` and requireJS module :js:`TYPO3/CMS/Version/ContextMenuActions`
- Filelist item providers :php:`\TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileDragProvider`, :php:`\TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider`,
  :php:`\TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileStorageProvider`, :php:`\TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FilemountsProvider`
  and requireJS module :js:`TYPO3/CMS/Filelist/ContextMenuActions`


.. index:: Backend, JavaScript, PHP-API, TSConfig
