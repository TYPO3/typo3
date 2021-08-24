<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'web',
    'ViewpageView',
    'after:layout',
    null,
    [
        'routeTarget' => \TYPO3\CMS\Viewpage\Controller\ViewModuleController::class . '::showAction',
        'access' => 'user,group',
        'name' => 'web_ViewpageView',
        'icon' => 'EXT:viewpage/Resources/Public/Icons/module-viewpage.svg',
        'labels' => 'LLL:EXT:viewpage/Resources/Private/Language/locallang_mod.xlf',
    ]
);
