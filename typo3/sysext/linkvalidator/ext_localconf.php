<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:linkvalidator/res/pagetsconfig.txt">');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_linkvalidator_tasks_Validator'] = array(
    'extension'        => $_EXTKEY,
    'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:tasks.validate.name',
    'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:tasks.validate.description',
    'additionalFields' => 'tx_linkvalidator_tasks_ValidatorAdditionalFieldProvider'
);

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] = array();
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['db'] = 'tx_linkvalidator_linktype_Internal';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['file'] = 'tx_linkvalidator_linktype_File';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['external'] = 'tx_linkvalidator_linktype_External';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['linkhandler'] = 'tx_linkvalidator_linktype_LinkHandler';

?>