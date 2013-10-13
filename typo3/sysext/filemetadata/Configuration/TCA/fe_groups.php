<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

$tca = array(
	// Opposite relation for sys_file_metadata.fe_groups.
	// The field is configured but not displayed by default for a FE Group.
	// To display it update your TCA as example:
	// $tca['types'][0]['showitem'] = $GLOBALS['TCA']['fe_groups']['types'][0]['showitem'] . ',files'
	'columns' => array(
		'files' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:fe_groups.files',
			'config' => array(
				'type' => 'select',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 9999,
				'autoSizeMax' => 30,
				'multiple' => 0,
				'foreign_table' => 'sys_file_metadata',
				'MM' => 'sys_file_fegroups_mm',
				'MM_opposite_field' => 'fe_groups',
			),
		),
	),
);

return \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($GLOBALS['TCA']['fe_groups'], $tca);
?>