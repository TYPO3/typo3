<?php

use TYPO3\CMS\Styleguide\Controller\BackendController;

return [
    // Register styleguide backend module in help toolbar
    'help_styleguide' => [
        'parent' => 'help',
        'access' => 'user',
        'path' => '/module/help/styleguide',
        'iconIdentifier' => 'module-styleguide',
        'labels' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf',
        'routes' => [
            '_default' => [
                'target' => BackendController::class . '::handleRequest',
            ],
        ],
    ],
];
