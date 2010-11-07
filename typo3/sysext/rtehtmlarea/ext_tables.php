<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

		// Add static template for Click-enlarge rendering
	t3lib_extMgm::addStaticFile($_EXTKEY,'static/clickenlarge/','Clickenlarge Rendering');

	$TCA['tx_rtehtmlarea_acronym'] = Array (
		'ctrl' => Array (
			'title' => 'LLL:EXT:rtehtmlarea/locallang_db.xml:tx_rtehtmlarea_acronym',
			'label' => 'term',
			'default_sortby' => 'ORDER BY term',
			'sortby' => 'sorting',
			'delete' => 'deleted',
			'enablecolumns' => Array (
				'disabled' => 'hidden',
				'starttime' => 'starttime',
				'endtime' => 'endtime',
			),
			'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
			'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'extensions/Acronym/skin/images/acronym.gif',
		),
	);
	t3lib_extMgm::allowTableOnStandardPages('tx_rtehtmlarea_acronym');

		// Add contextual help files
	foreach ($TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['plugins'] as $pluginName => $config) {
		if ($config['contextHelpFile']) {
			t3lib_extMgm::addLLrefForTCAdescr('xEXT_' . $_EXTKEY . '_' . $pluginName, $TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['plugins']['RemoveFormat']['contextHelpFile']);
		}
	}
?>