<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_blogexample_domain_model_blog'] = array(
	'ctrl' => $TCA['tx_blogexample_domain_model_blog']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title, posts, administrator'
	),
	'columns' => array(
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_blogexample_domain_model_blog',
				'foreign_table_where' => 'AND tx_blogexample_domain_model_blog.uid=###REC_FIELD_l18n_parent### AND tx_blogexample_domain_model_blog.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array(
			'config'=>array(
				'type'=>'passthrough'
			)
		),
		't3ver_label' => Array (
			'displayCond' => 'FIELD:t3ver_label:REQ:true',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.versionLabel',
			'config' => Array (
				'type'=>'none',
				'cols' => 27
			)
		),
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array(
				'type' => 'check'
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.title',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim,required',
				'max' => 256
			)
		),
		'description' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.description',
			'config' => array(
				'type' => 'text',
				'eval' => 'required',
				'rows' => 30,
				'cols' => 80,
			)
		),
		'logo' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.logo',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => 3000,
				'uploadfolder' => 'uploads/pics',
				'show_thumbs' => 1,
				'size' => 1,
				'maxitems' => 1,
				'minitems' => 0
			)
		),
		'posts' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.posts',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_blogexample_domain_model_post',
				'foreign_field' => 'blog',
				'foreign_sortby' => 'sorting',
				'maxitems' => 999999,
				'appearance' => array(
					'collapseAll' => 1,
					'expandSingle' => 1,
				),
			)
		),
		'administrator' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_blog.administrator',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'fe_users',
				'foreign_table_where' => "AND fe_users.tx_extbase_type='Tx_BlogExample_Domain_Model_Administrator'",
				'items' => array(
					array('--none--', 0),
					),
				'wizards' => Array(
					 '_PADDING' => 1,
					 '_VERTICAL' => 1,
					 'edit' => Array(
						 'type' => 'popup',
						 'title' => 'Edit',
						 'script' => 'wizard_edit.php',
						 'icon' => 'edit2.gif',
						 'popup_onlyOpenIfSelected' => 1,
						 'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					 ),
					 'add' => Array(
						 'type' => 'script',
						 'title' => 'Create new',
						 'icon' => 'add.gif',
						 'params' => Array(
							 'table'=>'fe_users',
							 'pid' => '###CURRENT_PID###',
							 'setValue' => 'prepend'
						 ),
						 'script' => 'wizard_add.php',
					 ),
				 )
			)
		),
	),
	'types' => array(
		'1' => array('showitem' => 'sys_language_uid, hidden, title, description, logo, posts, administrator')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);