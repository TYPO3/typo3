<?php

use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables']['tx_realurl_errorlog'] = [
    'dateField' => 'tstamp',
    'expirePeriod' => '180',
];
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables']['tx_realurl_uniqalias'] = [
    'expireField' => 'expire',
];
