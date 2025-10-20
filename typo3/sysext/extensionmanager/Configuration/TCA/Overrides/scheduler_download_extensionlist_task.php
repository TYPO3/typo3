<?php

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Task\UpdateExtensionListTask;

defined('TYPO3') or die();

// Register extension list update task
if (isset($GLOBALS['TCA']['tx_scheduler_task']) && !GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('extensionmanager', 'offlineMode')) {
    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:task.updateExtensionListTask.name',
            'description' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:task.updateExtensionListTask.description',
            'value' => UpdateExtensionListTask::class,
            'icon' => 'mimetypes-x-tx_scheduler_task_group',
            'group' => 'extensionmanager',
        ],
        '
            --div--;core.form.tabs:general,
                tasktype,
                task_group,
                description,
            --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
                execution_details,
                nextexecution,
                --palette--;;lastexecution,
            --div--;core.form.tabs:access,
                disable,
            --div--;core.form.tabs:extended,
        ',
        [],
        '',
        'tx_scheduler_task'
    );
}
