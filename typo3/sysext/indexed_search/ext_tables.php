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

?>