..  include:: /Includes.rst.txt

..  _feature-107663-1760110062:

===================================================
Feature: #107663 - Introduce submodule dependencies
===================================================

See :issue:`107663`

Description
===========

Backend modules can now declare a dependency on their submodules using the new
:php:`appearance['dependsOnSubmodules']` configuration option. When enabled, a
module will automatically hide itself from the module menu if none of its
submodules are available to the current user.

This feature enables container modules to adapt dynamically to the user's
permissions and installed extensions, ensuring the module menu remains clean
and only displays modules that provide actual functionality.

Example configuration
---------------------

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/Modules.php

    use TYPO3\CMS\Info\Controller\InfoModuleController;

    return [
        'content_status' => [
            'parent' => 'content',
            'access' => 'user',
            'path' => '/module/content/status',
            'iconIdentifier' => 'module-info',
            'labels' => 'backend.modules.status',
            'aliases' => ['web_info'],
            'navigationComponent' => '@typo3/backend/tree/page-tree-element',
            'appearance' => [
                'dependsOnSubmodules' => true,
            ],
            'showSubmoduleOverview' => true,
        ],
        'web_info_overview' => [
            'parent' => 'content_status',
            'access' => 'user',
            // ... configuration
        ],
        'web_info_translations' => [
            'parent' => 'content_status',
            'access' => 'user',
            // ... configuration
        ],
    ];

In this example, the :guilabel:`Content > Status` module will only be shown in the
module menu if at least one of its submodules (:guilabel:`Overview`,
:guilabel:`Translations`) is available to the current user.

..  note::
    The "Content > Status" was called "Web > Info" before TYPO3 v14, see also
    `Feature: #107628 - Improved backend module naming and structure <https://docs.typo3.org/permalink/changelog:feature-107628-1729026000>`_.

If all submodules are either disabled, removed, or the user lacks access
permissions to them, the parent module will automatically be hidden from the
module menu.

Impact
======

Module menus become more intuitive and user-focused. Container modules equipped
with :php:`appearance['dependsOnSubmodules']` intelligently adapt to the current
context, appearing only when they offer actionable functionality to the user.

The :guilabel:`Content > Status` module leverages this feature to seamlessly disappear
from the module menu when extensions are uninstalled or when users lack
permissions to access its submodules, preventing dead-end navigation paths and
enhancing the overall backend experience.

..  index:: Backend, PHP-API, ext:backend
