<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addPlugin(Array('LLL:EXT:indexed_search/locallang.php:mod_indexed_search', $_EXTKEY));
if (TYPO3_MODE=='BE')	t3lib_extMgm::addModule('tools','isearch','after:log',t3lib_extMgm::extPath($_EXTKEY).'mod/');

if (TYPO3_MODE=='BE')    {
    t3lib_extMgm::insertModuleFunction(
        'web_info',
        'tx_indexedsearch_modfunc1',
        t3lib_extMgm::extPath($_EXTKEY).'modfunc1/class.tx_indexedsearch_modfunc1.php',
        'LLL:EXT:indexed_search/locallang.php:mod_indexed_search'
    );
}

t3lib_extMgm::allowTableOnStandardPages('index_config');

$TCA['index_config'] = Array (
    'ctrl' => Array (
        'title' => 'LLL:EXT:indexed_search/locallang_db.php:index_config',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'type' => 'type',
        'default_sortby' => 'ORDER BY crdate',
        'enablecolumns' => Array (
            'disabled' => 'hidden',
            'starttime' => 'starttime',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => 'default.gif',
    ),
    'feInterface' => Array (
        'fe_admin_fieldList' => 'hidden, starttime, title, description, type, depth, table2index, alternative_source_pid, get_params, chashcalc, filepath, extensions',
    )
);

?>