<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// static_template
$TCA['static_template'] = array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'title' => 'LLL:EXT:statictemplates/locallang_tca.xml:static_template',
		'readOnly' => 1,
		// This should always be TRUE, as it prevents the static templates from being altered
		'adminOnly' => 1,
		// Only admin, if any
		'rootLevel' => 1,
		'is_static' => 1,
		'default_sortby' => 'ORDER BY title',
		'crdate' => 'crdate',
		'iconfile' => 'template_standard.gif',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'tca.php'
	)
);
$tempField = array(
	'include_static' => array(
		'label' => 'LLL:EXT:statictemplates/locallang_tca.xml:include_static',
		'config' => array(
			'type' => 'select',
			'foreign_table' => 'static_template',
			'foreign_table_where' => 'ORDER BY static_template.title DESC',
			'size' => 10,
			'maxitems' => 20,
			'default' => ''
		)
	)
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_template', $tempField, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_template', 'include_static;;2;;5-5-5', '', 'before:includeStaticAfterBasedOn');
?>