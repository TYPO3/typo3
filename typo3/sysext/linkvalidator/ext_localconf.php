<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Linkvalidator\Linktype\ExternalLinktype;
use TYPO3\CMS\Linkvalidator\Linktype\FileLinktype;
use TYPO3\CMS\Linkvalidator\Linktype\InternalLinktype;
use TYPO3\CMS\Linkvalidator\Task\ValidatorTask;
use TYPO3\CMS\Linkvalidator\Task\ValidatorTaskAdditionalFieldProvider;

defined('TYPO3') or die();

ExtensionManagementUtility::addPageTSConfig(
    "@import 'EXT:linkvalidator/Configuration/TsConfig/Page/pagetsconfig.tsconfig'"
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][ValidatorTask::class] = [
    'extension' => 'linkvalidator',
    'title' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.name',
    'description' => 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.description',
    'additionalFields' => ValidatorTaskAdditionalFieldProvider::class,
];

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] ?? null)) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] = [];
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['db'] = InternalLinktype::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['file'] = FileLinktype::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['external'] = ExternalLinktype::class;
