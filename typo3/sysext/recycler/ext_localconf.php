<?php

declare(strict_types=1);

use TYPO3\CMS\Recycler\Task\CleanerFieldProvider;
use TYPO3\CMS\Recycler\Task\CleanerTask;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][CleanerTask::class] = [
    'extension' => 'recycler',
    'title' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskTitle',
    'description' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskDescription',
    'additionalFields' => CleanerFieldProvider::class,
];
