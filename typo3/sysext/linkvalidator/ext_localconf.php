<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:linkvalidator/res/pageTSconfig.txt">');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_linkvalidator_tasks_Validate'] = array(
    'extension'        => $_EXTKEY,
    'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:tasks.validate.name',
    'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:tasks.validate.description',
    'additionalFields' => 'tx_linkvalidator_tasks_ValidateAdditionalFieldProvider'
);

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] = array();
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['db'] = 'EXT:linkvalidator/classes/linktypes/class.tx_linkvalidator_linktypes_internal.php:tx_linkvalidator_linkTypes_Internal';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['file'] = 'EXT:linkvalidator/classes/linktypes/class.tx_linkvalidator_linktypes_file.php:tx_linkvalidator_linkTypes_File';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['external'] = 'EXT:linkvalidator/classes/linktypes/class.tx_linkvalidator_linktypes_external.php:tx_linkvalidator_linkTypes_External';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']['linkhandler'] = 'EXT:linkvalidator/classes/linktypes/class.tx_linkvalidator_linktypes_linkhandler.php:tx_linkvalidator_linkTypes_LinkHandler';

?>