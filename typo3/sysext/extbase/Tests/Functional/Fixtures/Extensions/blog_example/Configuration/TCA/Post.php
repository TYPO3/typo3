<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_blogexample_domain_model_post'] = array(
	'ctrl' => $TCA['tx_blogexample_domain_model_post']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title, date, author',
		'maxDBListItems' => 100,
		'maxSingleDBListItems' => 500
	),
	'types' => array(
		'1' => array('showitem' => 'sys_language_uid, hidden, blog, title, date, author, content, tags, comments, related_posts')
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
				'foreign_table' => 'tx_blogexample_domain_model_post',
				'foreign_table_where' => 'AND tx_blogexample_domain_model_post.uid=###REC_FIELD_l18n_parent### AND tx_blogexample_domain_model_post.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array(
			'config'=>array(
				'type'=>'passthrough'
			)
		),
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array(
				'type' => 'check'
			)
		),
		'blog' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.blog',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tx_blogexample_domain_model_blog',
				'maxitems' => 1,
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.title',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim, required',
				'max' => 256
			)
		),
		'date' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.date',
			'config' => array(
				'type' => 'input',
				'size' => 12,
				'checkbox' => 1,
				'eval' => 'datetime, required',
				'default' => time()
			)
		),
		'author' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.author',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_blogexample_domain_model_person',
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
							 'table'=>'tx_blogexample_domain_model_person',
							 'pid' => '###CURRENT_PID###',
							 'setValue' => 'prepend'
						 ),
						 'script' => 'wizard_add.php',
					 ),
				 )
			)
		),
		'content' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.content',
			'config' => array(
				'type' => 'text',
				'rows' => 30,
				'cols' => 80
			)
		),
		'tags' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.tags',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_blogexample_domain_model_tag',
				'MM' => 'tx_blogexample_post_tag_mm',
				'maxitems' => 9999,
				'appearance' => array(
					'useCombination' => 1,
					'useSortable' => 1,
					'collapseAll' => 1,
					'expandSingle' => 1,
				)
			)
		),
		'comments' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.comments',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_blogexample_domain_model_comment',
				'foreign_field' => 'post',
				'size' => 10,
				'maxitems' => 9999,
				'autoSizeMax' => 30,
				'multiple' => 0,
				'appearance' => array(
					'collapseAll' => 1,
					'expandSingle' => 1,
				)
			)
		),
		'related_posts' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_post.related',
			'config' => array(
				'type' => 'select',
				'size' => 10,
				'maxitems' => 9999,
				'autoSizeMax' => 30,
				'multiple' => 0,
				'foreign_table' => 'tx_blogexample_domain_model_post',
				'foreign_table_where' => 'AND ###THIS_UID### != tx_blogexample_domain_model_post.uid',
				'MM' => 'tx_blogexample_post_post_mm',
				'MM_opposite_field' => 'related_posts',
			)
		),
	)
);

?>