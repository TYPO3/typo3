<?php
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dbal');
return array(
	'tx_dbal_autoloader' => $extensionPath . 'class.tx_dbal_autoloader.php',
	'tx_dbal_em' => $extensionPath . 'class.tx_dbal_em.php',
	'tx_dbal_installtool' => $extensionPath . 'class.tx_dbal_installtool.php',
	'tx_dbal_querycache' => $extensionPath . 'lib/class.tx_dbal_querycache.php',
	'tx_dbal_tsparserext' => $extensionPath . 'lib/class.tx_dbal_tsparserext.php',
	'tx_dbal_module1' => $extensionPath . 'mod1/index.php',
	'ux_t3lib_db' => $extensionPath . 'class.ux_t3lib_db.php',
	'ux_t3lib_sqlparser' => $extensionPath . 'class.ux_t3lib_sqlparser.php',
	'ux_db_list_extra' => $extensionPath . 'class.ux_db_list_extra.php',
	'ux_TYPO3\CMS\Core\Database\DatabaseConnection' => $extensionPath . 'Classes/Database/DatabaseConnection.php',
	'ux_TYPO3\CMS\Core\Database\SqlParser' => $extensionPath . 'Classes/Database/SqlParser.php',
	'ux_TYPO3\CMS\Core\RecordList\DatabaseRecordList' => $extensionPath . 'Classes/RecordList/DatabaseRecordList.php',
);
?>