<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$TCA['sys_note'] = array(
	'ctrl' => array(
		'label' => 'subject',
		'default_sortby' => 'ORDER BY crdate',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
		'delete' => 'deleted',
		'title' => 'LLL:EXT:sys_note/locallang_tca.xlf:sys_note',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif',
		'sortby' => 'sorting',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Configuration/Tca/SysNote.php',
	),
);

t3lib_extMgm::allowTableOnStandardPages('sys_note');
t3lib_extMgm::addLLrefForTCAdescr('sys_note', 'EXT:sys_note/locallang_csh_sysnote.xlf');

?>