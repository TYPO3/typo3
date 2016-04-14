<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('indexed_search')) {
        $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']['tx_indexed_search_pi_wizicon'] =
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Resources/PHP/class.tx_indexed_search_pi_wizicon.php';
    }
}
