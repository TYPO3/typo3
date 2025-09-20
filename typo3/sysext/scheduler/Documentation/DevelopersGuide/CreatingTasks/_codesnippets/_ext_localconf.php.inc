<?php

declare(strict_types=1);

use MyVendor\MyExtension\Tasks\MyTask;
use MyVendor\MyExtension\Tasks\MyTaskAdditionalFieldProvider;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][MyTask::class] = [
    'extension' => 'scheduler',
    'title' => 'Some title or LLL:EXT reference',
    'description' => 'Some description or LLL:EXT reference',
    'additionalFields' => MyTaskAdditionalFieldProvider::class,
];
