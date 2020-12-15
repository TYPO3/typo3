<?php

defined('TYPO3') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Extensionmanager',
    'tools',
    'extensionmanager',
    '',
    [
        \TYPO3\CMS\Extensionmanager\Controller\ListController::class => 'index,unresolvedDependencies,ter,showAllVersions,distributions',
        \TYPO3\CMS\Extensionmanager\Controller\ActionController::class => 'toggleExtensionInstallationState,installExtensionWithoutSystemDependencyCheck,removeExtension,downloadExtensionZip,reloadExtensionData',
        \TYPO3\CMS\Extensionmanager\Controller\DownloadController::class => 'checkDependencies,installFromTer,installExtensionWithoutSystemDependencyCheck,installDistribution,updateExtension,updateCommentForUpdatableVersions',
        \TYPO3\CMS\Extensionmanager\Controller\UpdateFromTerController::class => 'updateExtensionListFromTer',
        \TYPO3\CMS\Extensionmanager\Controller\UploadExtensionFileController::class => 'form,extract',
        \TYPO3\CMS\Extensionmanager\Controller\DistributionController::class => 'show'
    ],
    [
        'access' => 'systemMaintainer',
        'icon' => 'EXT:extensionmanager/Resources/Public/Icons/module-extensionmanager.svg',
        'labels' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_mod.xlf',
    ]
);
