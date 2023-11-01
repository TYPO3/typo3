<?php

use TYPO3\CMS\Styleguide\Controller\BackendController;

return [
    // Register styleguide backend module in help toolbar
    'help_styleguide' => [
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
                'target' => BackendController::class . '::handleRequest',
            ],
        ],
    ],
];
