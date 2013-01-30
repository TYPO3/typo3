<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['Recordlist']['modules']['List']['controllers'] = array(
	'FileSearch' => array(
		'actions' => array('results')
	)
);

?>