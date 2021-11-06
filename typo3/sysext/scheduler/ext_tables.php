<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

defined('TYPO3') or die();

// Add module
ExtensionManagementUtility::addModule(
    'system',
    'txschedulerM1',
    '',
    '',
    [
        'routeTarget' => SchedulerModuleController::class . '::mainAction',
        'access' => 'admin',
        'name' => 'system_txschedulerM1',
        'iconIdentifier' => 'module-scheduler',
        'labels' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_mod.xlf',
    ]
);

// Add context sensitive help (csh) to the backend module
ExtensionManagementUtility::addLLrefForTCAdescr(
    '_MOD_system_txschedulerM1',
    'EXT:scheduler/Resources/Private/Language/locallang_csh_scheduler.xlf'
);
