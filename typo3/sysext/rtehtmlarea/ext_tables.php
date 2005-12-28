<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE' && t3lib_extMgm::isLoaded('lorem_ipsum'))	{

		// Create wizard configuration:
	$wizConfig = array(
		'type' => 'userFunc',
		'userFunc' => 'EXT:' . $_EXTKEY . '/class.tx_rtehtmlarea_loremipsum_wiz.php:tx_rtehtmlarea_loremipsum_wiz->main',
		'params' => array()
	);

		// Load affected tables (except "pages"):
	t3lib_div::loadTCA('tt_content');

		// *********************
		// Apply wizards to:
		// *********************

		// Titles:
	$TCA['pages']['columns']['title']['config']['wizards']['tx_loremipsum'] =
	$TCA['pages']['columns']['nav_title']['config']['wizards']['tx_loremipsum'] =
		array_merge($wizConfig,array('params'=>array(
			'type' => 'title'
		)));

		// Subheaders
	$TCA['pages']['columns']['subtitle']['config']['wizards']['tx_loremipsum'] =
	$TCA['tt_content']['columns']['header']['config']['wizards']['tx_loremipsum'] =
	$TCA['tt_content']['columns']['subheader']['config']['wizards']['tx_loremipsum'] =
		array_merge($wizConfig,array('params'=>array(
			'type' => 'header'
		)));

		// Description / Abstract:
	$TCA['pages']['columns']['description']['config']['wizards']['tx_loremipsum'] =
	$TCA['pages']['columns']['abstract']['config']['wizards']['tx_loremipsum'] =
	$TCA['tt_content']['columns']['imagecaption']['config']['wizards']['tx_loremipsum'] =
		array_merge($wizConfig,array('params'=>array(
			'type' => 'description',
			'endSequence' => '46,32',
			'add' => TRUE
		)));

		// Keywords field:
	$TCA['pages']['columns']['keywords']['config']['wizards']['tx_loremipsum'] =
		array_merge($wizConfig,array('params'=>array(
			'type' => 'word',
			'endSequence' => '44,32',
			'add' => TRUE,
			'count' => 30
		)));

		// Bodytext field in Content Elements:
	$TCA['tt_content']['columns']['bodytext']['config']['wizards']['_VERTICAL'] = 1;
	$TCA['tt_content']['columns']['bodytext']['config']['wizards']['tx_loremipsum_2'] =
		array_merge($wizConfig,array('params'=>array(
			'type' => 'loremipsum',
			'endSequence' => '32',
			'add'=>TRUE
		)));
	$TCA['tt_content']['columns']['bodytext']['config']['wizards']['tx_loremipsum'] =
		array_merge($wizConfig,array('params'=>array(
			'type' => 'paragraph',
			'endSequence' => '10',
			'add'=>TRUE
		)));

	$TCA['tt_content']['columns']['image']['config']['wizards']['_POSITION'] = 'bottom';
	$TCA['tt_content']['columns']['image']['config']['wizards']['tx_loremipsum'] =
		array_merge($wizConfig,array('params'=>array(
			'type' => 'images'
		)));
}

	$TCA['tx_rtehtmlarea_acronym'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:rtehtmlarea/locallang_db.php:tx_rtehtmlarea_acronym',
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
