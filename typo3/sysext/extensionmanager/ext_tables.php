<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Extensionmanager\Controller\ActionController;
use TYPO3\CMS\Extensionmanager\Controller\DistributionController;
use TYPO3\CMS\Extensionmanager\Controller\DownloadController;
use TYPO3\CMS\Extensionmanager\Controller\ExtensionComposerStatusController;
use TYPO3\CMS\Extensionmanager\Controller\ListController;
use TYPO3\CMS\Extensionmanager\Controller\UpdateFromTerController;
use TYPO3\CMS\Extensionmanager\Controller\UploadExtensionFileController;

defined('TYPO3') or die();

ExtensionUtility::registerModule(
    'Extensionmanager',
    'tools',
    'extensionmanager',
    '',
    [
        ListController::class => 'index,unresolvedDependencies,ter,showAllVersions,distributions',
        ActionController::class => 'toggleExtensionInstallationState,installExtensionWithoutSystemDependencyCheck,removeExtension,downloadExtensionZip,reloadExtensionData',
        DownloadController::class => 'checkDependencies,installFromTer,installExtensionWithoutSystemDependencyCheck,installDistribution,updateExtension,updateCommentForUpdatableVersions',
        UpdateFromTerController::class => 'updateExtensionListFromTer',
        UploadExtensionFileController::class => 'form,extract',
        DistributionController::class => 'show',
        ExtensionComposerStatusController::class => 'list,detail',
    ],
    [
        'access' => 'systemMaintainer',
        'iconIdentifier' => 'module-extensionmanager',
        'labels' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_mod.xlf',
    ]
);
