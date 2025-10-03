<?php

declare(strict_types=1);

use MyVendor\MyExtension\Task\MyTask;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'My Custom Task',
            'description' => 'Description of what this task does',
            'value' => MyTask::class,
            'icon' => 'my-custom-icon',
            'group' => 'my_extension',
        ],
        $GLOBALS['TCA']['tx_scheduler_task']['0']['showitem'],
        [],
        '',
        'tx_scheduler_task'
    );
}
