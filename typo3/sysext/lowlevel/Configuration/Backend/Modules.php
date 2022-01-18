<?php

use TYPO3\CMS\Lowlevel\Controller\ConfigurationController;
use TYPO3\CMS\Lowlevel\Controller\DatabaseIntegrityController;

/**
 * Definitions for modules provided by EXT:lowlevel
 */
return [
    'system_dbint' => [
        'parent' => 'system',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/dbint',
        'iconIdentifier' => 'module-dbint',
        'labels' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => DatabaseIntegrityController::class . '::handleRequest',
            ],
        ],
    ],
    'system_config' => [
        'parent' => 'system',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/config',
        'iconIdentifier' => 'module-config',
        'labels' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod_configuration.xlf',
        'routes' => [
            '_default' => [
                'target' => ConfigurationController::class . '::handleRequest',
            ],
        ],
    ],
];
