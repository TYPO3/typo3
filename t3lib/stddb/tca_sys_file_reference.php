<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['sys_file_reference'] = array (
	'ctrl' => $TCA['sys_file_reference']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,uid_local,uid_foreign,tablenames,fieldname,sorting_foreign,table_local,title,description'
	),
	'feInterface' => $TCA['sys_file_reference']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'uid_local' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.uid_local',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'size' => 1,
				'maxitems' => 1,
				'minitems' => 0,
				'allowed' => 'sys_file',
			),
		),
		'uid_foreign' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.uid_foreign',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('',0),
				),
				'foreign_table' => 'tt_content',
				'foreign_table_where' => 'ORDER BY tt_content.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'tablenames' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.tablenames',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'fieldname' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.fieldname',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'sorting_foreign' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.sorting_foreign',
			'config' => array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array (
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
		'table_local' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.table_local',
			'config' => array (
				'type' => 'input',
				'size' => '20',
				'default' => 'sys_file',
			)
		),
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.title',
			'config' => array (
				'type' => 'input',
				'size' => '22',
				'placeholder' => '__row|uid_local|name',
			)
		),
		'link' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.link',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'description' => array ( // This is used for captions in the frontend
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.description',
			'config' => array (
				'type' => 'text',
				'cols' => '24',
				'rows' => '5',
			)
		),
		'alternative' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.alternative',
			'config' => array (
				'type' => 'input',
				'size' => '22',
				'placeholder' => '__row|uid_local|name',
			)
		),
	),
	'types' => array (
			// Note that at the moment we define the same fields for every media type.
			// We leave the extensive definition of each type here anyway, to make clear that you can use it to differentiate between the types.
		'0' => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette',
		),
		t3lib_file_File::FILETYPE_TEXT => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette',
		),
		t3lib_file_File::FILETYPE_IMAGE => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette',
		),
		t3lib_file_File::FILETYPE_AUDIO => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette',
		),
		t3lib_file_File::FILETYPE_VIDEO => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette',
		),
		t3lib_file_File::FILETYPE_SOFTWARE => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette',
		),
	),
	'palettes' => array(
			// used for basic overlays: having a file list etc
		'basicoverlayPalette' => array(
			'showitem' => 'title,description',
			'canNotCollapse' => TRUE,
		),

			// used for everything that is an image (because it has a link and a alternative text)
		'imageoverlayPalette' => array(
			'showitem' => '
				title,alternative;;;;3-3-3,--linebreak--,
				link,description
				',
			'canNotCollapse' => TRUE,
		),

			// file palette, hidden but needs to be included all the time
		'filePalette' => array(
			'showitem' => 'uid_local',
			'isHiddenPalette' => TRUE,
		),
	)
);

?>
