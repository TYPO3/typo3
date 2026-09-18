<?php

use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessLifetime;
use TYPO3\CMS\Extensionmanager\Controller\ActionController;
use TYPO3\CMS\Extensionmanager\Controller\DistributionController;
use TYPO3\CMS\Extensionmanager\Controller\DownloadController;
use TYPO3\CMS\Extensionmanager\Controller\ListController;
use TYPO3\CMS\Extensionmanager\Controller\UpdateFromTerController;
use TYPO3\CMS\Extensionmanager\Controller\UploadExtensionFileController;

/**
 * Definitions for modules provided by EXT:extensionmanager
 */
return [
    'extensionmanager' => [
        'parent' => 'system',
        'access' => 'systemMaintainer',
        'iconIdentifier' => 'module-extensionmanager',
        'position' => ['before' => '*'],
        'labels' => 'extensionmanager.module',
        'aliases' => ['tools_ExtensionmanagerExtensionmanager'],
        'path' => '/module/extensions',
        'extensionName' => 'Extensionmanager',
        'controllerActions' => [
            ListController::class => [
                'index', 'ter', 'showAllVersions', 'distributions',
            ],
            ActionController::class => [
                'checkExtensionDependencies', 'toggleExtensionInstallationState', 'installExtensionWithoutSystemDependencyCheck', 'removeExtension', 'downloadExtensionZip', 'reloadExtensionData',
            ],
            DownloadController::class => [
                'checkDependencies', 'checkDistributionDependencies', 'installFromTer', 'installExtensionWithoutSystemDependencyCheck', 'installDistribution', 'installDistributionWithoutDependencyCheck', 'updateExtension', 'updateCommentForUpdatableVersions',
            ],
            UpdateFromTerController::class => [
                'updateExtensionListFromTer',
            ],
            UploadExtensionFileController::class => [
                'form', 'extract',
            ],
            DistributionController::class => [
                'show',
            ],
        ],
        'routeOptions' => [
            'sudoMode' => [
                'group' => 'systemMaintainer',
                'lifetime' => AccessLifetime::medium,
            ],
        ],
        'moduleData' => [
            'filter' => '',
        ],
    ],
];
