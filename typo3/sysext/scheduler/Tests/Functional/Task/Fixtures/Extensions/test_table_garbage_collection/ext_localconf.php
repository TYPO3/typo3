<?php

use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables']['tx_testtablegarbagecollection_expire'] = [
    'expireField' => 'expire_field',
];
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables']['tx_testtablegarbagecollection_composite'] = [
    'dateField' => 'tstamp',
    'expirePeriod' => 30,
];
