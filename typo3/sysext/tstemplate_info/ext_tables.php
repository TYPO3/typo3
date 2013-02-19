<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_ts',
		'tx_tstemplateinfo',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Controller/TypoScriptTemplateInformationModuleFunctionController.php',
		'LLL:EXT:tstemplate/ts/locallang.xlf:infoModify'
	);
}
?>