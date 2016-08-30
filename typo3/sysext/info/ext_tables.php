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
            'labels' => [
                'tabs_images' => [
                    'tab' => 'EXT:info/Resources/Public/Icons/module-info.svg',
                ],
                'll_ref' => 'LLL:EXT:lang/locallang_mod_web_info.xlf',
            ],
        ]
    );
}
