<?php

declare(strict_types=1);

use MyVendor\MyExtension\Task\MyTask;

if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 14) {
    // Todo: Remove when TYPO3 13 support is dropped
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][MyTask::class] = [
        'extension' => 'my_extension',
        'title' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:myTask.title',
        'description' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:myTask.description',
        'additionalFields' => \MyVendor\MyExtension\Task\MyTaskAdditionalFieldProvider::class,
    ];
}
