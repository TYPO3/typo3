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
        'labels' => 'LLL:EXT:setup/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => SetupModuleController::class . '::mainAction',
            ],
        ],
    ],
];
