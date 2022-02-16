<?php

declare(strict_types=1);

use TYPO3\CMS\Linkvalidator\Task\ValidatorTask;
use TYPO3\CMS\Linkvalidator\Task\ValidatorTaskAdditionalFieldProvider;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][ValidatorTask::class] = [
    'extension' => 'linkvalidator',
    'title' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.name',
    'description' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.description',
    'additionalFields' => ValidatorTaskAdditionalFieldProvider::class,
];
