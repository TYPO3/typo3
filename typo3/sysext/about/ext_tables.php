<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'help',
    'AboutAbout',
    'top',
    null,
    [
        'routeTarget' => \TYPO3\CMS\About\Controller\AboutController::class . '::indexAction',
        'access' => 'user,group',
        'name' => 'help_AboutAbout',
        'icon' => 'EXT:about/Resources/Public/Icons/module-about.svg',
        'labels' => 'LLL:EXT:about/Resources/Private/Language/Modules/about.xlf'
    ]
);
