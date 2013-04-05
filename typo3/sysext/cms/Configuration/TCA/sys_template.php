<?php
return array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'sortby' => 'sorting',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
		'title' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'adminOnly' => 1,
		// Only admin, if any
		'iconfile' => 'template.gif',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'typeicon_column' => 'root',
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-content-template-extension',
			'1' => 'mimetypes-x-content-template'
		),
		'typeicons' => array(
			'0' => 'template_add.gif'
		),
		'dividers2tabs' => 1,
		'searchFields' => 'title,constants,config'
	),
	'interface' => array(
		'showRecordFieldList' => 'title,clear,root,basedOn,nextLevel,sitetitle,description,hidden,starttime,endtime'
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.title',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'max' => '256',
				'eval' => 'required'
			)
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
			'exclude' => 1,
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0'
			)
		),
		'endtime' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
			'exclude' => 1,
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 12, 31, 2020)
				)
			)
		),
		'root' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.root',
			'config' => array(
				'type' => 'check'
			)
		),
		'clear' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.clear',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('Constants', ''),
					array('Setup', '')
				),
				'cols' => 2
			)
		),
		'sitetitle' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.sitetitle',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'max' => '256'
			)
		),
		'constants' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.constants',
			'config' => array(
				'type' => 'text',
				'cols' => '48',
				'rows' => '10',
				'wrap' => 'OFF',
				'softref' => 'TStemplate,email[subst],url[subst]'
			),
			'defaultExtras' => 'fixed-font : enable-tab'
		),
		'nextLevel' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.nextLevel',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'sys_template',
				'show_thumbs' => '1',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0',
				'default' => '',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest'
					)
				)
			)
		),
		'include_static_file' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.include_static_file',
			'config' => array(
				'type' => 'select',
				'size' => 10,
				'maxitems' => 100,
				'items' => array(),
				'softref' => 'ext_fileref'
			)
		),
		'basedOn' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.basedOn',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'sys_template',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '50',
				'autoSizeMax' => 10,
				'minitems' => '0',
				'default' => '',
				'wizards' => array(
					'_PADDING' => 4,
					'_VERTICAL' => 1,
					'suggest' => array(
						'type' => 'suggest'
					),
					'edit' => array(
						'type' => 'popup',
						'title' => 'Edit template',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1'
					),
					'add' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.basedOn_add',
						'icon' => 'add.gif',
						'params' => array(
							'table' => 'sys_template',
							'pid' => '###CURRENT_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php'
					)
				)
			)
		),
		'includeStaticAfterBasedOn' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.includeStaticAfterBasedOn',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'config' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.config',
			'config' => array(
				'type' => 'text',
				'rows' => 10,
				'cols' => 48,
				'wizards' => array(
					'_PADDING' => 4,
					'0' => array(
						'title' => 'TSref online',
						'script' => 'wizard_tsconfig.php?mode=tsref',
						'icon' => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1'
					)
				),
				'wrap' => 'OFF',
				'softref' => 'TStemplate,email[subst],url[subst]'
			),
			'defaultExtras' => 'fixed-font : enable-tab'
		),
		'description' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.description',
			'config' => array(
				'type' => 'text',
				'rows' => 5,
				'cols' => 48
			)
		),
		'static_file_mode' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_template.static_file_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:cms/locallang_tca.xlf:sys_template.static_file_mode.0', '0'),
					array('LLL:EXT:cms/locallang_tca.xlf:sys_template.static_file_mode.1', '1'),
					array('LLL:EXT:cms/locallang_tca.xlf:sys_template.static_file_mode.2', '2'),
					array('LLL:EXT:cms/locallang_tca.xlf:sys_template.static_file_mode.3', '3')
				),
				'default' => '0'
			)
		),
		'tx_impexp_origuid' => array('config' => array('type' => 'passthrough')),
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255'
			)
		)
	),
	'types' => array(
		'1' => array('showitem' => '
			hidden,title;;1;;2-2-2, sitetitle, constants;;;;3-3-3, config, description;;;;4-4-4,
			--div--;LLL:EXT:cms/locallang_tca.xlf:sys_template.tabs.options, clear, root, nextLevel,
			--div--;LLL:EXT:cms/locallang_tca.xlf:sys_template.tabs.include, includeStaticAfterBasedOn,6-6-6, include_static_file, basedOn, static_file_mode,
			--div--;LLL:EXT:cms/locallang_tca.xlf:sys_template.tabs.access, starttime, endtime')
	)
);
?>