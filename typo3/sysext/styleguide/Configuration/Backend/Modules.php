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
        'path' => '/module/system/styleguide/components',
        'labels' => [
            'title' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.components.title',
            'shortDescription' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.components.shortDescription',
            'description' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.components.description',
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
            'shortDescription' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.styles.shortDescription',
            'description' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.styles.description',
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
            'shortDescription' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.pageTrees.shortDescription',
            'description' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:module.configuration.pageTrees.description',
        ],
        'routes' => [
            '_default' => [
                'target' => PageTreesController::class . '::handleRequest',
            ],
        ],
    ],
];
