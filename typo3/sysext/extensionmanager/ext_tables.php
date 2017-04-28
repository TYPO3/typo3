<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'TYPO3.CMS.Extensionmanager',
    'tools',
    'extensionmanager',
    '',
    [
        'List' => 'index,unresolvedDependencies,ter,showAllVersions,distributions',
        'Action' => 'toggleExtensionInstallationState,installExtensionWithoutSystemDependencyCheck,removeExtension,downloadExtensionZip,reloadExtensionData',
        'Configuration' => 'showConfigurationForm,save,saveAndClose',
        'Download' => 'checkDependencies,installFromTer,installExtensionWithoutSystemDependencyCheck,installDistribution,updateExtension,updateCommentForUpdatableVersions',
        'UpdateScript' => 'show',
        'UpdateFromTer' => 'updateExtensionListFromTer',
        'UploadExtensionFile' => 'form,extract',
        'Distribution' => 'show'
    ],
    [
        'access' => 'systemMaintainer',
        'icon' => 'EXT:extensionmanager/Resources/Public/Icons/module-extensionmanager.svg',
        'labels' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_mod.xlf',
    ]
);
