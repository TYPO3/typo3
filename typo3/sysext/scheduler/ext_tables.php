<?php
defined('TYPO3_MODE') or die();

// Add module
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'system',
    'txschedulerM1',
    '',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController::class . '::mainAction',
        'access' => 'admin',
        'name' => 'system_txschedulerM1',
        'icon' => 'EXT:scheduler/Resources/Public/Icons/module-scheduler.svg',
        'labels' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_mod.xlf'
    ]
);

// Add context sensitive help (csh) to the backend module
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    '_MOD_system_txschedulerM1',
    'EXT:scheduler/Resources/Private/Language/locallang_csh_scheduler.xlf'
);
