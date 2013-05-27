<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
	'<INCLUDE_TYPOSCRIPT: source="FILE:EXT:linkvalidator/Configuration/TsConfig/Page/pagetsconfig.txt">'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Linkvalidator\\Task\\ValidatorTask'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:tasks.validate.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:tasks.validate.description',
	'additionalFields' => 'TYPO3\\CMS\\Linkvalidator\\Task\\ValidatorTaskAdditionalFieldProvider'
);

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] = array();
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['db'] = 'TYPO3\\CMS\\Linkvalidator\\Linktype\\InternalLinktype';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['file'] = 'TYPO3\\CMS\\Linkvalidator\\Linktype\\FileLinktype';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['external'] = 'TYPO3\\CMS\\Linkvalidator\\Linktype\\ExternalLinktype';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['linkhandler'] = 'TYPO3\\CMS\\Linkvalidator\\Linktype\\LinkHandler';
?>