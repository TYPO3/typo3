<?php

use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessLifetime;
use TYPO3\CMS\Extensionmanager\Controller\ActionController;
use TYPO3\CMS\Extensionmanager\Controller\DistributionController;
use TYPO3\CMS\Extensionmanager\Controller\DownloadController;
use TYPO3\CMS\Extensionmanager\Controller\ExtensionComposerStatusController;
use TYPO3\CMS\Extensionmanager\Controller\ListController;
use TYPO3\CMS\Extensionmanager\Controller\UpdateFromTerController;
use TYPO3\CMS\Extensionmanager\Controller\UploadExtensionFileController;

/**
 * Definitions for modules provided by EXT:extensionmanager
 */
return [
    'tools_ExtensionmanagerExtensionmanager' => [
        'parent' => 'tools',
        'access' => 'systemMaintainer',
        'iconIdentifier' => 'module-extensionmanager',
        'labels' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Extensionmanager',
        'controllerActions' => [
            ListController::class => [
                'index', 'unresolvedDependencies', 'ter', 'showAllVersions', 'distributions',
            ],
            ActionController::class => [
                'toggleExtensionInstallationState', 'installExtensionWithoutSystemDependencyCheck', 'removeExtension', 'downloadExtensionZip', 'reloadExtensionData',
            ],
            DownloadController::class => [
                'checkDependencies', 'installFromTer', 'installExtensionWithoutSystemDependencyCheck', 'installDistribution', 'updateExtension', 'updateCommentForUpdatableVersions',
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
            ExtensionComposerStatusController::class => [
                'list', 'detail',
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
