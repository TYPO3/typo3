<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tools',
        'txdbalM1',
        '',
        '',
        [
            'routeTarget' => \TYPO3\CMS\Dbal\Controller\ModuleController::class . '::mainAction',
            'access' => 'admin',
            'name' => 'tools_txdbalM1',
            'labels' => [
                'tabs_images' => [
                    'tab' => 'EXT:dbal/Resources/Public/Icons/module-dbal.svg',
                ],
                'll_ref' => 'LLL:EXT:dbal/Resources/Private/Language/locallang_mod.xlf',
            ],
        ]
    );
}
