<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// ext_tables.php is split to each single part of application
require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY)).'Configuration/ExtTables/ext_tables.general.php';
require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY)).'Configuration/ExtTables/ext_tables.1nff.php';
require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY)).'Configuration/ExtTables/ext_tables.mnasym.php';
require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY)).'Configuration/ExtTables/ext_tables.mnmmasym.php';
require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY)).'Configuration/ExtTables/ext_tables.mnsym.php';
require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY)).'Configuration/ExtTables/ext_tables.mnattr.php';
require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY)).'Configuration/ExtTables/ext_tables.1ncsv.php';

?>