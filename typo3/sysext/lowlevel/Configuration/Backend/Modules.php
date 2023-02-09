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
        'labels' => [
            'title' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:module.dbint.title',
            'shortDescription' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:module.dbint.shortDescription',
            'description' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:module.dbint.description',
        ],
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
        'labels' => [
            'title' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:module.configuration.title',
            'shortDescription' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:module.configuration.shortDescription',
            'description' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:module.configuration.description',
        ],
        'routes' => [
            '_default' => [
                'target' => ConfigurationController::class . '::indexAction',
            ],
        ],
        'moduleData' => [
            'tree' => '',
        ],
    ],
];
