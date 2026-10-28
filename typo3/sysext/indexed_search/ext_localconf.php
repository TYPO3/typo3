<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\IndexedSearch\Controller\SearchController;
use TYPO3\CMS\IndexedSearch\Hook\RecordIndexingDataHandlerHook;

defined('TYPO3') or die();

// register plugin
ExtensionUtility::configurePlugin(
    'IndexedSearch',
    'Pi2',
    [SearchController::class => ['form', 'search', 'noTypoScript']],
    [SearchController::class => ['form', 'search']],
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['indexed_search'] = RecordIndexingDataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['indexed_search'] = RecordIndexingDataHandlerHook::class;
