<?php

use TYPO3\CMS\Styleguide\Controller\ComponentsController;
use TYPO3\CMS\Styleguide\Controller\PageTreesController;
use TYPO3\CMS\Styleguide\Controller\StylesController;

return [
    'styleguide' => [
        'parent' => 'system',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/styleguide',
        'iconIdentifier' => 'module-styleguide',
        'labels' => [
            'title' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.title',
            'shortDescription' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.shortDescription',
            'description' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.description',
        ],
        'routes' => [
            '_default' => [
                'target' => StylesController::class . '::handleRequest',
            ],
        ],
        'aliases' => ['help_styleguide'],
    ],
    'styleguide_components' => [
        'parent' => 'styleguide',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/styleguide/components',
        'labels' => [
            'title' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.components.title',
        ],
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
        'path' => '/module/system/styleguide/styles',
        'labels' => [
            'title' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.styles.title',
        ],
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
        'path' => '/module/system/styleguide/manage-page-trees',
        'labels' => [
            'title' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.pageTrees.title',
        ],
        'routes' => [
            '_default' => [
                'target' => PageTreesController::class . '::handleRequest',
            ],
        ],
    ],
];
