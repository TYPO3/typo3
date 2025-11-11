<?php

use TYPO3\CMS\Setup\Controller\SetupModuleController;

/**
 * Definitions for modules provided by EXT:setup
 */
return [
    'user_setup' => [
        'parent' => 'user',
        'access' => 'user',
        'path' => '/module/user/setup',
        'iconIdentifier' => 'module-setup',
        'labels' => 'setup.module',
        'routes' => [
            '_default' => [
                'target' => SetupModuleController::class . '::mainAction',
            ],
        ],
    ],
];
