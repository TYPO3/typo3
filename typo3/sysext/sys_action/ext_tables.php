<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE')	{
	$TCA['sys_action'] = array(
		'ctrl' => array(
			'label' => 'title',
			'tstamp' => 'tstamp',
			'default_sortby' => 'ORDER BY title',
			'sortby' => 'sorting',
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'title' => 'LLL:EXT:sys_action/locallang_tca.php:sys_action',
			'crdate' => 'crdate',
			'cruser_id' => 'cruser_id',
			'adminOnly' => 1,
			'rootLevel' => -1,
			'setToDefaultOnCopy' => 'assign_to_groups',
			'enablecolumns' => array(
				'disabled' => 'hidden'
			),
			'typeicon_classes' => array(
				'default' => 'mimetypes-x-sys_action',
			), 
			'type' => 'type',
			'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'x-sys_action.png',
			'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php'
		)
	);

	$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = t3lib_extMgm::extPath('sys_action') . 'toolbarmenu/registerToolbarItem.php';

	t3lib_extMgm::addLLrefForTCAdescr('sys_action','EXT:sys_action/locallang_csh_sysaction.xml');

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']['sys_action']['tx_sysaction_task'] = array(
		'title'       => 'LLL:EXT:sys_action/locallang_tca.xml:sys_action',
		'description' => 'LLL:EXT:sys_action/locallang_csh_sysaction.xml:.description',
		'icon'		  => 'EXT:sys_action/x-sys_action.png',
	);
}
?>