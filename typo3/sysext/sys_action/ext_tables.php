<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'user_task',
		'tx_sysaction',
		t3lib_extMgm::extPath($_EXTKEY).'class.tx_sysaction.php',
		'LLL:EXT:sys_action/locallang_tca.php:tx_sys_action'
	);

	$TCA['sys_action'] = Array (
		'ctrl' => Array (
			'label' => 'title',
			'tstamp' => 'tstamp',
			'default_sortby' => 'ORDER BY title',
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'title' => 'LLL:EXT:sys_action/locallang_tca.php:sys_action',
			'crdate' => 'crdate',
			'cruser_id' => 'cruser_id',
			'adminOnly' => 1,
			'rootLevel' => -1,
			'setToDefaultOnCopy' => 'assign_to_groups',
			'enablecolumns' => Array (
				'disabled' => 'hidden'
			),
			'type' => 'type',
			'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'sys_action.gif',
			'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php'
		)
	);
}

t3lib_extMgm::addLLrefForTCAdescr('sys_action','EXT:sys_action/locallang_csh_sysaction.php');

?>