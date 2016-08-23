<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Extbase\Scheduler\Task::class] = [
    'extension' => 'extbase',
    'title' => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:task.name',
    'description' => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:task.description',
    'additionalFields' => \TYPO3\CMS\Extbase\Scheduler\FieldProvider::class
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkFlexFormValue'][] = \TYPO3\CMS\Extbase\Hook\DataHandler\CheckFlexFormValue::class;
