<?php
defined('TYPO3_MODE') or die();

// register extbase plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('TYPO3.CMS.IndexedSearch', 'Pi2', ['Search' => 'form,search,noTypoScript'], ['Search' => 'form,search']);

// Attach to hooks:
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'][] = \TYPO3\CMS\IndexedSearch\Indexer::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache']['tx_indexedsearch'] = \TYPO3\CMS\IndexedSearch\Hook\TypoScriptFrontendHook::class . '->headerNoCache';
// Register with "crawler" extension:
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions']['tx_indexedsearch_reindex'] = 'Re-indexing';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['cli_hooks']['tx_indexedsearch_crawl'] = \TYPO3\CMS\IndexedSearch\Hook\CrawlerHook::class;
// Register with TCEmain:
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['tx_indexedsearch'] = \TYPO3\CMS\IndexedSearch\Hook\CrawlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tx_indexedsearch'] = \TYPO3\CMS\IndexedSearch\Hook\CrawlerHook::class;
// Configure default document parsers:
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'] = [
    'pdf'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'doc'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'docx' => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'dotx' => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'pps'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'ppsx' => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'ppt'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'pptx' => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'potx' => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'xls'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'xlsx' => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'xltx' => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'sxc'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'sxi'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'sxw'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'ods'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'odp'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'odt'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'rtf'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'txt'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'html' => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'htm'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'csv'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'xml'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'jpg'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'jpeg' => \TYPO3\CMS\IndexedSearch\FileContentParser::class,
    'tif'  => \TYPO3\CMS\IndexedSearch\FileContentParser::class
];

// unserializing the configuration so we can use it here:
$extConf = [];
if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search'])) {
    $extConf = unserialize(
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search'],
        ['allowed_classes' => false]
    );
}

if (isset($extConf['useMysqlFulltext']) && $extConf['useMysqlFulltext'] === '1') {
    // Use all index_* tables except "index_rel" and "index_words"
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['use_tables'] =
        'index_phash,index_fulltext,index_section,index_grlist,index_stat_search,index_stat_word,index_debug,index_config';
    // Register schema analyzer slot to hook in required fulltext index definition
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class,
        'tablesDefinitionIsBeingBuilt',
        \TYPO3\CMS\IndexedSearch\Service\DatabaseSchemaService::class,
        'addMysqlFulltextIndex'
    );
    unset($signalSlotDispatcher);
} else {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['use_tables'] =
        'index_phash,index_fulltext,index_rel,index_words,index_section,index_grlist,index_stat_search,index_stat_word,index_debug,index_config';
}

// Add search to new content element wizard
if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
    mod.wizards.newContentElement.wizardItems.forms {
        elements.search {
            iconIdentifier = content-elements-searchform
            title = LLL:EXT:indexed_search/Resources/Private/Language/locallang_pi.xlf:pi_wizard_title
            description = LLL:EXT:indexed_search/Resources/Private/Language/locallang_pi.xlf:pi_wizard_description
            tt_content_defValues {
                CType = list
                list_type = indexedsearch_pi2
            }
        }
        show :=addToList(search)
    }
    ');
}

// Use the advanced doubleMetaphone parser instead of the internal one (usage of metaphone parsers is generally disabled by default)
if (isset($extConf['enableMetaphoneSearch']) && (int)$extConf['enableMetaphoneSearch'] == 2) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['metaphone'] = \TYPO3\CMS\IndexedSearch\Utility\DoubleMetaPhoneUtility::class;
}
unset($extConf);
