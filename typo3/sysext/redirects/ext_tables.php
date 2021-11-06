<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Redirects\Controller\ManagementController;

defined('TYPO3') or die();

ExtensionManagementUtility::addModule(
    'site',
    'redirects',
    '',
    '',
    [
        'routeTarget' => ManagementController::class . '::handleRequest',
        'access' => 'group,user',
        'name' => 'site_redirects',
        'iconIdentifier' => 'module-redirects',
        'labels' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf',
    ]
);
