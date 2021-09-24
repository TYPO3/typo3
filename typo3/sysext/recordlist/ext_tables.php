<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Recordlist\Controller\RecordListController;

defined('TYPO3') or die();

ExtensionManagementUtility::addModule(
    'web',
    'list',
    '',
    '',
    [
        'routeTarget' => RecordListController::class . '::mainAction',
        'access' => 'user,group',
        'name' => 'web_list',
        'icon' => 'EXT:recordlist/Resources/Public/Icons/module-list.svg',
        'labels' => 'LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf',
    ]
);
