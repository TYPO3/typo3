<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class Tx_Extbase_Persistence_Mapper_ObjectRelationalMapper_testcase extends Tx_Extbase_Base_testcase {

	public function setUp() {
		// $GLOBALS['TSFE'] = $this->getMock('tslib_fe', array('includeTCA'), array(), '', FALSE);
		// $this->setupTca();
		// 
		// $GLOBALS['TSFE']->expects($this->any())
		// 	->method('includeTCA')
		// 	->will($this->returnValue(NULL));		
	}
	
	// public function setupTCA() {
	// 	global $TCA;
	// 	global $_EXTKEY;
	// 	$TCA['tx_blogexample_domain_model_blog'] = array (
	// 		'ctrl' => array (
	// 			'title'             => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog',
	// 			'label'				=> 'name',
	// 			'tstamp'            => 'tstamp',
	// 			'prependAtCopy'     => 'LLL:EXT:lang/locallang_general.xml:LGL.prependAtCopy',
	// 			'delete'            => 'deleted',
	// 			'enablecolumns'     => array (
	// 				'disabled' => 'hidden'
	// 			),
	// 			'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'Resources/Icons/icon_tx_blogexample_domain_model_blog.gif'
	// 		),
	// 		'interface' => array(
	// 			'showRecordFieldList' => 'hidden, name, description, logo, posts'
	// 		),
	// 		'columns' => array(
	// 			'hidden' => array(
	// 				'exclude' => 1,
	// 				'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
	// 				'config'  => array(
	// 					'type' => 'check'
	// 				)
	// 			),
	// 			'name' => array(
	// 				'exclude' => 0,
	// 				'label'   => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog.name',
	// 				'config'  => array(
	// 					'type' => 'input',
	// 					'size' => 20,
	// 					'eval' => 'trim,required',
	// 					'max'  => 256
	// 				)
	// 			),
	// 			'description' => array(
	// 				'exclude' => 1,
	// 				'label'   => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog.description',
	// 				'config'  => array(
	// 					'type' => 'text',
	// 					'eval' => 'required',
	// 					'rows' => 30,
	// 					'cols' => 80,
	// 				)
	// 			),
	// 			'logo' => array(
	// 				'exclude' => 1,
	// 				'label'   => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog.logo',
	// 				'config'  => array(
	// 					'type'          => 'group',
	// 					'internal_type' => 'file',
	// 					'allowed'       => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
	// 					'max_size'      => 3000,
	// 					'uploadfolder'  => 'uploads/pics',
	// 					'show_thumbs'   => 1,
	// 					'size'          => 1,
	// 					'maxitems'      => 1,
	// 					'minitems'      => 0
	// 				)
	// 			),
	// 			'posts' => array(
	// 				'exclude' => 1,
	// 				'label'   => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog.posts',
	// 				'config' => array(
	// 					'type' => 'inline',
	// 					// TODO is 'foreign_class' in $TCA the best way?
	// 					'foreign_class' => 'Tx_BlogExample_Domain_Model_Post',
	// 					'foreign_table' => 'tx_blogexample_domain_model_post',
	// 					'foreign_field' => 'blog',
	// 					'foreign_table_field' => 'blog_table',
	// 					'appearance' => array(
	// 						'newRecordLinkPosition' => 'bottom',
	// 						'collapseAll' => 1,
	// 						'expandSingle' => 1,
	// 					),
	// 				)
	// 			),
	// 			'author' => array(
	// 				'exclude' => 1,
	// 				'label'   => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog.author',
	// 				'config' => array(
	// 					'type' => 'select',
	// 					'foreign_class' => 'Tx_BlogExample_Domain_Model_Author',
	// 					'foreign_table' => 'tx_blogexample_domain_model_author',
	// 					'maxitems' => 1,
	// 				)
	// 			),
	// 		),
	// 		'types' => array(
	// 			'1' => array('showitem' => 'hidden, name, description, logo, posts')
	// 		),
	// 		'palettes' => array(
	// 			'1' => array('showitem' => '')
	// 		)
	// 	);
	// }
	
	public function tearDown() {
	}

	public function test_QueryWithPlaceholdersCanBeBuild() {
		// $mapper = new Tx_Extbase_Persistence_Mapper_ObjectRelationalMapper($GLOBALS['TYPO3_DB']);
		// 		
		// $query = $mapper->buildQuery('Tx_BlogExample_Domain_Blog',
		// 	array(
		// 		array('name LIKE ? OR name LIKE ?', 'foo', 'bar'),
		// 		array('hidden = ?', FALSE)
		// 	));
		// 
		// $this->assertEquals("(name LIKE 'foo' OR name LIKE 'bar') AND (hidden = 0)", $query);
	}

	public function test_QueryWithExampleCanBeBuild() {
		// $mapper = new Tx_Extbase_Persistence_Mapper_ObjectRelationalMapper($GLOBALS['TYPO3_DB']);
		// $query = $mapper->buildQuery('Tx_BlogExample_Domain_Model_Blog',
		// 	array(
		// 		'name' => 'foo',
		// 		'hidden' => FALSE
		// 	));
		// 
		// $this->assertEquals("(tx_blogexample_domain_model_blog.name = 'foo') AND (tx_blogexample_domain_model_blog.hidden = 0)", $query);
	}
	
	public function test_QueryWithNestedExampleCanBeBuild() {
		// $mapper = new Tx_Extbase_Persistence_Mapper_ObjectRelationalMapper($GLOBALS['TYPO3_DB']);
		// $query = $mapper->buildQuery('Tx_BlogExample_Domain_Model_Blog',
		// 	array(
		// 		'hidden' => FALSE,
		// 		'posts' => array(
		// 			'title' => 'foo'
		// 		)
		// 	));
		// 
		// $this->assertEquals("(tx_blogexample_domain_model_blog.hidden = 0) AND ((tx_blogexample_domain_model_post.title = 'foo'))", $query);
	}
	
}
?>