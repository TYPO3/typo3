<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'web',
    'list',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Recordlist\Controller\RecordListController::class . '::mainAction',
        'access' => 'user,group',
        'name' => 'web_list',
        'icon' => 'EXT:recordlist/Resources/Public/Icons/module-list.svg',
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf'
    ]
);
