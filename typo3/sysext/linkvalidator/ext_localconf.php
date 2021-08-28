<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    "@import 'EXT:linkvalidator/Configuration/TsConfig/Page/pagetsconfig.tsconfig'"
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Linkvalidator\Task\ValidatorTask::class] = [
    'extension' => 'linkvalidator',
    'title' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.name',
    'description' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.description',
    'additionalFields' => \TYPO3\CMS\Linkvalidator\Task\ValidatorTaskAdditionalFieldProvider::class
];

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] ?? null)) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] = [];
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['db'] = \TYPO3\CMS\Linkvalidator\Linktype\InternalLinktype::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['file'] = \TYPO3\CMS\Linkvalidator\Linktype\FileLinktype::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['external'] = \TYPO3\CMS\Linkvalidator\Linktype\ExternalLinktype::class;
