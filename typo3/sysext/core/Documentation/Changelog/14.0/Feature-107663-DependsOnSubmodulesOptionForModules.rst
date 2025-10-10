..  include:: /Includes.rst.txt

..  _feature-107663-1760110062:

===================================================
Feature: #107663 - Introduce submodule dependencies
===================================================

See :issue:`107663`

Description
===========

Backend modules can now declare a dependency on their submodules using the new
:php:`dependsOnSubmodules` configuration option. When enabled, a module will
automatically hide itself from the module menu if none of its submodules are
available to the current user.

This powerful feature enables container modules to adapt dynamically to the
user's permissions and installed extensions, ensuring the module menu remains
clean and only displays modules that provide actual functionality.

Example configuration:

..  code-block:: php

    return [
        'web_info' => [
            'parent' => 'web',
            'access' => 'user',
            'path' => '/module/web/info',
            'iconIdentifier' => 'module-info',
            'labels' => 'LLL:EXT:info/Resources/Private/Language/locallang_mod_web_info.xlf',
            'navigationComponent' => '@typo3/backend/tree/page-tree-element',
            'dependsOnSubmodules' => true,
            'routes' => [
                '_default' => [
                    'target' => InfoModuleController::class . '::handleRequest',
                ],
            ],
        ],
        'web_info_overview' => [
            'parent' => 'web_info',
            'access' => 'user',
            // ... configuration
        ],
        'web_info_translations' => [
            'parent' => 'web_info',
            'access' => 'user',
            // ... configuration
        ],
    ];

In this example, the :guilabel:`Web > Info` module will only be shown in the
module menu if at least one of its submodules (:guilabel:`Overview`,
:guilabel:`Translations`) is available to the current user.

If all submodules are either disabled, removed, or the user lacks access
permissions to them, the parent module will automatically be hidden from
the module menu.

Impact
======

Module menus become more intuitive and user-focused. Container modules equipped
with :php:`dependsOnSubmodules` intelligently adapt to the current context,
appearing only when they offer actionable functionality to the user.

The :guilabel:`Web > Info` module leverages this feature to seamlessly vanish
from the module menu when extensions are uninstalled or users lack permissions
to access its submodules, preventing dead-end navigation paths and enhancing
the overall backend experience.

..  index:: Backend, PHP-API, ext:backend
