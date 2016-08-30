<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'web',
        'func',
        '',
        '',
        [
            'routeTarget' => \TYPO3\CMS\Func\Controller\PageFunctionsController::class . '::mainAction',
            'access' => 'user,group',
            'name' => 'web_func',
            'labels' => [
                'tabs_images' => [
                    'tab' => 'EXT:func/Resources/Public/Icons/module-func.svg',
                ],
                'll_ref' => 'LLL:EXT:lang/locallang_mod_web_func.xlf',
            ],
        ]
    );
}
