<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
	$TCA['tx_rtehtmlarea_acronym'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:rtehtmlarea/locallang_db.xml:tx_rtehtmlarea_acronym',
		'label' => 'term',
		'default_sortby' => 'ORDER BY term',
		'sortby' => 'sorting',
		'rootLevel' => 1,
		'delete' => 'deleted',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'htmlarea/skins/default/images/Acronym/ed_acronym.gif',
		)
	);
	 
	t3lib_extMgm::allowTableOnStandardPages('tx_rtehtmlarea_acronym');
	t3lib_extMgm::addToInsertRecords('tx_rtehtmlarea_acronym');
?>
