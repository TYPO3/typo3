<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'web',
        'info',
        '',
        '',
        [
            'routeTarget' => \TYPO3\CMS\Info\Controller\InfoModuleController::class . '::mainAction',
            'access' => 'user,group',
            'name' => 'web_info',
            'icon' => 'EXT:info/Resources/Public/Icons/module-info.svg',
            'labels' => 'LLL:EXT:lang/Resources/Private/Language/locallang_mod_web_info.xlf'
        ]
    );
}
