.. include:: ../../Includes.txt

=====================================================
Feature: #96733 - New backend module registration API
=====================================================

See :issue:`96733`

Description
===========

The registration and usage of backend modules was previously based
on the global array :php:`$TBE_MODULES`. This however had a couple
of drawbacks, e.g. module registration could be changed at runtime,
which had been resolved by introducing a new registration API.

Therefore, instead of using the :php:`ExtensionManagementUtility::addModule()`
and :php:`ExtensionUtility::registerModule()` (extbase) API methods in
:file:`ext_tables.php` files, the configuration is now placed in the
dedicated :file:`Configuration/Backend/Modules.php` configuration file.

Those files are then read and processed when building the container. This
means the state is fixed and can't be changed at runtime. This approach
follows the general Core strategy (see e.g. :doc:`Icons.php <../11.4/Feature-94692-RegisteringIconsViaServiceContainer>`),
since it highly improves the loading speed of every request as the
registration can be handled at once and cached during warmup of the
core caches. Besides caching, this will also allow additional features
in the future, which were blocked due to the loose state.

Previous configuration in :file:`ext_tables.php`:

.. code-block:: php

    ExtensionManagementUtility::addModule(
        'web',
        'example',
        'top',
        '',
        [
            'routeTarget' => MyExampleModuleController::class . '::handleRequest',
            'name' => 'web_example',
            'access' => 'admin',
            'workspaces' => 'online'
            'iconIdentifier' => 'module-example',
            'labels' => 'LLL:EXT:example/Resources/Private/Language/locallang_mod.xlf',
            'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
        ]
    );

    ExtensionUtility::registerModule(
        'Extkey',
        'web',
        'example',
        'after:info',
        [
            MyExtbaseExampleModuleController::class => 'list, detail',
        ],
        [
            'access' => 'admin',
            'workspaces' => 'online'
            'iconIdentifier' => 'module-example',
            'labels' => 'LLL:EXT:extkey/Resources/Private/Language/locallang_mod.xlf',
            'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
        ]
    );

Will now be registered in :file:`Configuration/Backend/Modules.php`:

.. code-block:: php

    return [
        'web_module' => [
            'parent' => 'web',
            'position' => ['top'],
            'access' => 'admin',
            'workspaces' => 'live'
            'path' => '/module/web/example',
            'iconIdentifier' => 'module-example',
            'navigationComponent' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
            'labels' => 'LLL:EXT:example/Resources/Private/Language/locallang_mod.xlf',
            'routes' => [
                '_default' => [
                    'target' => MyExampleModuleController::class . '::handleRequest',
                ],
            ],
        ],
        'web_ExtkeyExample' => [
            'parent' => 'web',
            'position' => ['after' => 'web_info'],
            'access' => 'admin',
            'workspaces' => 'live'
            'iconIdentifier' => 'module-example',
            'path' => '/module/web/ExtkeyExample',
            'labels' => 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf',
            'extensionName' => 'Extkey',
            'controllerActions' => [
                MyExtbaseExampleModuleController::class => [
                    'list', 'detail'
                ],
            ],
        ],
    ];

.. note::

    Each modules array key is used as the module identifier, which
    will also be the route identifier. It's no longer necessary to
    use the `mainModule_subModule` pattern, since a possible parent
    will be defined with the `parent` option.


Module configuration options
============================

+----------------------------------------------------------+------------------------------------------------------------------+
| Option                                                   | Description                                                      |
+==========================================================+==================================================================+
| parent (:php:`string`)                                   | If the module should be a submodule, the parent identifier, e.g. |
|                                                          | `web` has to be set here.                                        |
+----------------------------------------------------------+------------------------------------------------------------------+
| path (:php:`string`)                                     | Define the path to the default endpoint. The path can be         |
|                                                          | anything, but will fallback to the known                         |
|                                                          | `/module/<mainModue>/<subModule>` pattern, if not set.           |
+----------------------------------------------------------+------------------------------------------------------------------+
| standalone (:php:`bool`)                                 | Whether the module is a standalone module (parent without        |
|                                                          | sub modules).                                                    |
+----------------------------------------------------------+------------------------------------------------------------------+
| access (:php:`string`)                                   | Can be `user` (editor permissions), `admin`, or                  |
|                                                          | `systemMaintainer`.                                              |
+----------------------------------------------------------+------------------------------------------------------------------+
| workspaces (:php:`string`)                               | Can be `*` (= always), `live` or `offline`                       |
+----------------------------------------------------------+------------------------------------------------------------------+
| position (:php:`array`)                                  | The module position. Allowed values are `top` and `bottom` as    |
|                                                          | well as the key value pairs `before => <identifier>` and         |
|                                                          | `after => <identifier>`.                                         |
+----------------------------------------------------------+------------------------------------------------------------------+
| appearance (:php:`array`)                                | Allows to define additional appearance options:                  |
|                                                          |   - `renderInModuleMenu` (:php:`bool`)                           |
+----------------------------------------------------------+------------------------------------------------------------------+
| iconIdentifier (:php:`string`)                           | The module icon identifier                                       |
+----------------------------------------------------------+------------------------------------------------------------------+
| icon (:php:`string`)                                     | Path to a module icon (Deprecated: Use `iconIdentifier` instead) |
+----------------------------------------------------------+------------------------------------------------------------------+
| labels (:php:`array` or :php:`string`)                   | An :php:`array` with the following keys:                         |
|                                                          |                                                                  |
|                                                          | - `title`                                                        |
|                                                          | - `description`                                                  |
|                                                          | - `shortDescription`                                             |
|                                                          |                                                                  |
|                                                          | The value can either be a static string or a locallang label     |
|                                                          | reference.                                                       |
|                                                          |                                                                  |
|                                                          | It's also possible to define the path to a locallang file.       |
|                                                          | The referenced file should contain the following label keys:     |
|                                                          |                                                                  |
|                                                          | - `mlang_tabs_tab` (Used as module title)                        |
|                                                          | - `mlang_labels_tabdescr` (Used as module description)           |
|                                                          | - `mlang_labels_tablabel` (Used as module short description)     |
+----------------------------------------------------------+------------------------------------------------------------------+
| component (:php:`string`)                                | The view component, responsible for rendering the module.        |
|                                                          | Defaults to `TYPO3/CMS/Backend/Module/Iframe`.                   |
+----------------------------------------------------------+------------------------------------------------------------------+
| navigationComponent (:php:`string`)                      | The module navigation component, e.g.                            |
|                                                          | `TYPO3/CMS/Backend/PageTree/PageTreeElement`.                    |
+----------------------------------------------------------+------------------------------------------------------------------+
| navigationComponentId (:php:`string`)                    | The module navigation component (Deprecated: Use                 |
|                                                          | `navigationComponent` instead).                                  |
+----------------------------------------------------------+------------------------------------------------------------------+
| inheritNavigationComponentFromMainModule (:php:`bool`)   | Whether the module should use the parents navigation component.  |
|                                                          | This option defaults to :php:`true` and can therefore be used to |
|                                                          | stop the inheritance for sub modules.                            |
+----------------------------------------------------------+------------------------------------------------------------------+

Module-dependent configuration options
--------------------------------------

Default:

+----------------------------+---------------------------------------------------------------------+
| Option                     | Description                                                         |
+============================+=====================================================================+
| routes (:php:`array`)      | Define the routes to this module. Each route requires a `path` and  |
|                            | the `target`, except the mandatory `_default` route, which uses     |
|                            | the `path` from the top-level configuration::                       |
|                            |                                                                     |
|                            |     routes' => [                                                    |
|                            |         '_default' => [                                             |
|                            |             'target' => Controller::class . '::handleRequest',      |
|                            |         ],                                                          |
|                            |     ],                                                              |
|                            |                                                                     |
|                            | Please note, using additional routes - next to `_default` is not    |
|                            | yet implemented.                                                    |
+----------------------------+---------------------------------------------------------------------+

Extbase:

+----------------------------------+---------------------------------------------------------------+
| Option                           | Description                                                   |
+==================================+===============================================================+
| extensionName (:php:`string`)    | The extension name, the module is registered for.             |
+----------------------------------+---------------------------------------------------------------+
| controllerActions (:php:`array`) | Define the controller action pair. The array keys are the     |
|                                  | controller class names and the values are the actions, which  |
|                                  | can either be defined as array or comma-separated list::      |
|                                  |                                                               |
|                                  |     'controllerActions' => [                                  |
|                                  |         Controller::class => [                                |
|                                  |             'aAction', 'anotherAction',                       |
|                                  |         ],                                                    |
|                                  |     ],                                                        |
+----------------------------------+---------------------------------------------------------------+

The BeforeModuleCreationEvent
=============================

The new PSR-14 :php:`BeforeModuleCreationEvent` allows extension authors
to manipulate the module configuration, before it is used to create and
register the module.

Registration of an event listener in the :file:`Services.yaml`:

.. code-block:: yaml

  MyVendor\MyPackage\Backend\ModifyModuleIcon:
    tags:
      - name: event.listener
        identifier: 'my-package/backend/modify-module-icon'

The corresponding event listener class:

.. code-block:: php

    use TYPO3\CMS\Backend\Module\BeforeModuleCreationEvent;

    class ModifyModuleIcon {

        public function __invoke(BeforeModuleCreationEvent $event): void
        {
            // Change module icon of page module
            if ($event->getIdentifier() === 'web_layout') {
                $event->setConfigurationValue('iconIdentifider', 'my-custom-icon-identifier');
            }
        }
    }

BeforeModuleCreationEvent methods
---------------------------------

+-------------------------+-----------------------+----------------------------------------------------+
| Method                  | Parameters            | Description                                        |
+=========================+=======================+====================================================+
| getIdentifier()         |                       | Returns the identifier of the module in question.  |
+-------------------------+-----------------------+----------------------------------------------------+
| getConfiguration()      |                       | Get the module configuration, as defined in the    |
|                         |                       | :php:`Configuration/Backend/Modules.php` file.     |
+-------------------------+-----------------------+----------------------------------------------------+
| setConfiguration()      | :php:`$configuration` | Overrides the module configuration.                |
+-------------------------+-----------------------+----------------------------------------------------+
| hasConfigurationValue() | :php:`$key`           | Checks whether the given key is set.               |
+-------------------------+-----------------------+----------------------------------------------------+
| getConfigurationValue() | :php:`$key`           | Returns the value for the given :php:`key`, or     |
|                         | :php:`$default`       | the :php:`$default`, if not set.                   |
+-------------------------+-----------------------+----------------------------------------------------+
| setConfigurationValue() | :php:`$key`           | Updates the configuration :php:`$key` with the     |
|                         | :php:`$value`         | given :php:`value`.                                |
+-------------------------+-----------------------+----------------------------------------------------+


New ModuleProvider API
=======================

The other piece is the new :php:`ModuleProvider` API, which allows extension
authors to work with the registered modules in a straightforward way.

Previously there had been a couple of different classes and methods, which
did mostly the same but in other ways. Also handling of those classes had
been tough, especially the :php:`ModuleLoader` component.

See :doc:`changelog <../12.0/Breaking-96733-RemovedSupportForModuleHandlingBasedOnTBE_MODULES>`
for all removed classes and methods.

The new API is now the central point to retrieve modules, since it will
automatically perform necessary access checks and prepare specific structures,
e.g. for the use in menus.

ModuleProvider API methods
--------------------------

+---------------------------+--------------------------------------+----------------------------------------------------------+
| Method                    | Parameters                           | Description                                              |
+===========================+======================================+==========================================================+
| isModuleRegistered()      | :php:`$identifier`                   | Checks whether a module is registered for the given      |
|                           |                                      | identifier. Does NOT perform any access check!           |
+---------------------------+--------------------------------------+----------------------------------------------------------+
| getModule()               | :php:`$identifier`                   | Returns a module for the given identifier. In case a     |
|                           | :php:`$user`                         | user is given,also access checks are performed.          |
|                           | :php:`$respectWorkspaceRestrictions` | Additionally, one can define whether workspace           |
|                           |                                      | restrictions should be respected.                        |
+---------------------------+--------------------------------------+----------------------------------------------------------+
| getModules()              | :php:`$user`                         | Returns all modules either grouped by main modules       |
|                           | :php:`$respectWorkspaceRestrictions` | or flat. In case a user is given, also access checks     |
|                           | :php:`$grouped`                      | are performed. Additionally, one can define whether      |
|                           |                                      | workspace restrictions should be respected.              |
+---------------------------+--------------------------------------+----------------------------------------------------------+
| getModuleForMenu()        | :php:`$identifier`                   | Returns the requested main module prepared for           |
|                           | :php:`$user`                         | menu generation or similar structured output (nested),   |
|                           | :php:`$respectWorkspaceRestrictions` | if it exists and the user has necessary permissions.     |
|                           |                                      | Additionally, one can define whether workspace           |
|                           |                                      | restrictions should be respected.                        |
+---------------------------+--------------------------------------+----------------------------------------------------------+
| getModulesForModuleMenu() | :php:`$user`                         | Returns all allowed modules for the current user,        |
|                           | :php:`$respectWorkspaceRestrictions` | prepared for module menu generation or similar           |
|                           |                                      | structured output (nested). Additionally, one can define |
|                           |                                      | whether workspace restrictions should be respected.      |
+---------------------------+--------------------------------------+----------------------------------------------------------+
| accessGranted()           | :php:`$identifier`                   | Check access of a module for a given user. Additionally, |
|                           |                                      | one can define whether workspace restrictions should     |
|                           |                                      | be respected.                                            |
+---------------------------+--------------------------------------+----------------------------------------------------------+

ModuleInterface
===============

Instead of a global array structure, the registered modules are stored as
objects in a registry. The module objects do all implement the :php:`ModuleInterface`.
This allows a well-defined OOP-based approach to work with registered models.

The :php:`ModuleInterface` basically provides getters for the options,
defined in the module registration and additionally provides methods for
relation handling (main modules and sub modules).

+---------------------------+--------------------------+-----------------------------------------------+
| Method                    | Return type              | Description                                   |
+===========================+==========================+===============================================+
| getIdentifier()           | :php:`string`            | Returns the internal name of the module,      |
|                           |                          | used for referencing in permissions etc.      |
+---------------------------+--------------------------+-----------------------------------------------+
| getPath()                 | :php:`string`            | Returns the module main path                  |
+---------------------------+--------------------------+-----------------------------------------------+
| getIconIdentifier()       | :php:`$string`           | Returns the module icon identifier            |
+---------------------------+--------------------------+-----------------------------------------------+
| getTitle()                | :php:`string`            | Returns the module title (see:                |
|                           |                          | `mlang_tabs_tab`).                            |
+---------------------------+--------------------------+-----------------------------------------------+
| getDescription()          | :php:`string`            | Returns the module description (see:          |
|                           |                          | `mlang_labels_tabdescr`).                     |
+---------------------------+--------------------------+-----------------------------------------------+
| getShortDescription()     | :php:`string`            | Returns the module short description (see:    |
|                           |                          | `mlang_labels_tablabel`).                     |
+---------------------------+--------------------------+-----------------------------------------------+
| isStandalone()            | :php:`bool`              | Returns, whether the module is standalone     |
|                           |                          | (main module without sub modules).            |
+---------------------------+--------------------------+-----------------------------------------------+
| getComponent()            | :php:`string`            | Returns the view component responsible for    |
|                           |                          | rendering the module (iFrame or name of the   |
|                           |                          | web component).                               |
+---------------------------+--------------------------+-----------------------------------------------+
| getNavigationComponent()  | :php:`string`            | Returns the web component to be rendering the |
|                           |                          | navigation area.                              |
+---------------------------+--------------------------+-----------------------------------------------+
| getPosition()             | :php:`array`             | Returns the position of the module, such as   |
|                           |                          | `top` or `bottom` or `after => anotherModule` |
|                           |                          | or `before => anotherModule`.                 |
+---------------------------+--------------------------+-----------------------------------------------+
| getAppearance()           | :php:`array`             | Returns a modules' appearance options, e.g.   |
|                           |                          | used for module menu.                         |
+---------------------------+--------------------------+-----------------------------------------------+
| getAccess()               | :php:`string`            | Returns defined access level, can be `user`,  |
|                           |                          | `admin` or `systemMaintainer`.                |
+---------------------------+--------------------------+-----------------------------------------------+
| getWorkspaceAccess()      | :php:`string`            | Returns defined workspace access, can be `*`  |
|                           |                          | (all), `live` or `offline`.                   |
+---------------------------+--------------------------+-----------------------------------------------+
| getParentIdentifier()     | :php:`string`            | In case this is a sub module, returns the     |
|                           |                          | parent module identifier.                     |
+---------------------------+--------------------------+-----------------------------------------------+
| getParentModule()         | :php:`?ModuleInterface`  | In case this is a sub module, returns the     |
|                           |                          | parent module.                                |
+---------------------------+--------------------------+-----------------------------------------------+
| hasParentModule()         | :php:`bool`              | Returns whether the module has a parent       |
|                           |                          | module defined (is a sub module).             |
+---------------------------+--------------------------+-----------------------------------------------+
| hasSubModule($identifier) | :php:`bool`              | Returns whether the module has a specific     |
|                           |                          | sub module assigned.                          |
+---------------------------+--------------------------+-----------------------------------------------+
| hasSubModules()           | :php:`bool`              | Returns whether the module has a sub modules  |
|                           |                          | assigned.                                     |
+---------------------------+--------------------------+-----------------------------------------------+
| getSubModule($identifier) | :php:`?ModuleInterface`  | If set, returns the requested sub module.     |
+---------------------------+--------------------------+-----------------------------------------------+
| getSubModules()           | :php:`ModuleInterface[]` | Returns all assigned sub modules.             |
+---------------------------+--------------------------+-----------------------------------------------+
| getDefaultRouteOptions()  | :php:`array`             | Returns options, to be added to the main      |
|                           |                          | module route. Usually `module`, `moduleName`  |
|                           |                          | and `access`.                                 |
+---------------------------+--------------------------+-----------------------------------------------+

Impact
======

Registration of backend modules is now done in extensions'
:file:`Configuration/Backend/Modules.php` file. This allows
to have all modules registered at build-time.

The new :php:`ModuleProvider` API takes care of permission handling
and returns objects based on the :php:`ModuleInterface`. The rendering
is now based on a well-defined OOP-based approach, which is used throughout
all places in TYPO3 Backend now in a unified way.

.. index:: Backend, PHP-API, ext:backend
