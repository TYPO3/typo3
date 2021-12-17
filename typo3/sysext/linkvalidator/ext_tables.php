<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Linkvalidator\Controller\LinkValidatorController;

defined('TYPO3') or die();

ExtensionManagementUtility::addModule(
    'web',
    'linkvalidator',
    'after:info',
    '',
    [
        'routeTarget' => LinkValidatorController::class,
        'access' => 'user,group',
        'name' => 'web_linkvalidator',
        'path' => '/module/page/link-reports',
        'workspaces' => 'online',
        'icon' => 'EXT:linkvalidator/Resources/Public/Icons/Extension.png',
        // @todo Uncomment following line after updating TYPO3/TYPO3.Icons
        // 'iconIdentifier' => 'module-linkvalidator'
        'labels' => 'LLL:EXT:linkvalidator/Resources/Private/Language/Module/locallang_mod.xlf',
    ]
);

ExtensionManagementUtility::addLLrefForTCAdescr(
    'linkvalidator',
    'EXT:linkvalidator/Resources/Private/Language/Module/locallang_csh.xlf'
);
