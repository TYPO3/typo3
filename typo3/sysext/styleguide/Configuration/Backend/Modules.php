<?php

use TYPO3\CMS\Styleguide\Controller\ComponentsController;
use TYPO3\CMS\Styleguide\Controller\PageTreesController;
use TYPO3\CMS\Styleguide\Controller\StylesController;

return [
    'styleguide' => [
        'parent' => 'tools',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/styleguide',
        'iconIdentifier' => 'module-styleguide',
        'labels' => 'styleguide.modules.overview',
        'aliases' => ['help_styleguide'],
        'appearance' => [
            'dependsOnSubmodules' => true,
        ],
        'showSubmoduleOverview' => true,
    ],
    'styleguide_components' => [
        'parent' => 'styleguide',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/styleguide/components',
        'labels' => 'styleguide.modules.components',
        'routes' => [
            '_default' => [
                'target' => ComponentsController::class . '::handleRequest',
            ],
        ],
    ],
    'styleguide_styles' => [
        'parent' => 'styleguide',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/styleguide/styles',
        'labels' => 'styleguide.modules.styles',
        'routes' => [
            '_default' => [
                'target' => StylesController::class . '::handleRequest',
            ],
        ],
    ],
    'styleguide_pagetrees' => [
        'parent' => 'styleguide',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/styleguide/manage-page-trees',
        'labels' => 'styleguide.modules.pagetrees',
        'routes' => [
            '_default' => [
                'target' => PageTreesController::class . '::handleRequest',
            ],
        ],
    ],
];
