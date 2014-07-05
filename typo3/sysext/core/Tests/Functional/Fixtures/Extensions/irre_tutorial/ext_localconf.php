<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'OliverHader.' . $_EXTKEY, 'Irre',
	array(
		'Queue' => 'index',
		'Content' => 'list, show, new, create, edit, update, delete'
	),
	array(
		'Content' => 'create, update, delete'
	)
);
