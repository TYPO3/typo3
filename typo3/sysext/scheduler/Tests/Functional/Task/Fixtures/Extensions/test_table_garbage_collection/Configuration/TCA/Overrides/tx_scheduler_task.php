<?php

use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

defined('TYPO3') or die();

$GLOBALS['TCA']['tx_scheduler_task']['types'][TableGarbageCollectionTask::class]['taskOptions']['tables']['tx_testtablegarbagecollection_expire'] = [
    'expireField' => 'expire_field',
];
$GLOBALS['TCA']['tx_scheduler_task']['types'][TableGarbageCollectionTask::class]['taskOptions']['tables']['tx_testtablegarbagecollection_composite'] = [
    'dateField' => 'tstamp',
    'expirePeriod' => 30,
];
