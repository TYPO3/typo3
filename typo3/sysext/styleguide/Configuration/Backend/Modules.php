<?php

use TYPO3\CMS\Styleguide\Controller\ComponentsController;
use TYPO3\CMS\Styleguide\Controller\GraphicalFunctionsController;
use TYPO3\CMS\Styleguide\Controller\PageTreesController;
use TYPO3\CMS\Styleguide\Controller\StylesController;

return [
    'styleguide' => [
        'parent' => 'admin',
        'position' => ['after' => 'system_reports'],
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
    'styleguide_graphical_functions' => [
        'parent' => 'styleguide',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/styleguide/graphical-functions',
        'labels' => 'styleguide.modules.graphical_functions',
        'routes' => [
            '_default' => [
                'target' => GraphicalFunctionsController::class . '::handleRequest',
            ],
        ],
    ],
];
