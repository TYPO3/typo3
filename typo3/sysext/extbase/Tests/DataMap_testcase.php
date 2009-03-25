<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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

require_once(PATH_tslib . 'class.tslib_content.php');

require_once('Base_testcase.php');

class Tx_ExtBase_Persistence_Mapper_DataMap_testcase extends Tx_ExtBase_Base_testcase {
	
	public function setUp() {
		require_once(t3lib_extMgm::extPath('blogexample') . 'Classes/Domain/Blog.php');
	
		$GLOBALS['TSFE']->fe_user = $this->getMock('tslib_feUserAuth');
		$GLOBALS['TSFE'] = $this->getMock('tslib_fe', array('includeTCA'));
		$this->setupTca();
		$GLOBALS['TSFE']->expects($this->any())
			->method('includeTCA')
			->will($this->returnValue(NULL));
		
		
		$GLOBALS['TSFE']->fe_user->user['uid'] = 999;
		$GLOBALS['TSFE']->id = 42;		
	}
	
	public function setupTCA() {
		global $TCA;
		global $_EXTKEY;
		$TCA['tx_blogexample_domain_blog'] = array (
			'ctrl' => array (
				'title'             => 'LLL:EXT:blogexample/Resources/Language/locallang_db.xml:tx_blogexample_domain_blog',
				'label'				=> 'name',
				'tstamp'            => 'tstamp',
				'prependAtCopy'     => 'LLL:EXT:lang/locallang_general.xml:LGL.prependAtCopy',
				'delete'            => 'deleted',
				'enablecolumns'     => array (
					'disabled' => 'hidden'
				),
				'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'Resources/Icons/icon_tx_blogexample_domain_blog.gif'
			),
			'interface' => array(
				'showRecordFieldList' => 'hidden, name, description, logo, posts'
			),
			'columns' => array(
				'hidden' => array(
					'exclude' => 1,
					'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
					'config'  => array(
						'type' => 'check'
					)
				),
				'name' => array(
					'exclude' => 0,
					'label'   => 'LLL:EXT:blogexample/Resources/Language/locallang_db.xml:tx_blogexample_domain_blog.name',
					'config'  => array(
						'type' => 'input',
						'size' => 20,
						'eval' => 'trim,required',
						'max'  => 256
					)
				),
				'description' => array(
					'exclude' => 1,
					'label'   => 'LLL:EXT:blogexample/Resources/Language/locallang_db.xml:tx_blogexample_domain_blog.description',
					'config'  => array(
						'type' => 'text',
						'eval' => 'required',
						'rows' => 30,
						'cols' => 80,
					)
				),
				'logo' => array(
					'exclude' => 1,
					'label'   => 'LLL:EXT:blogexample/Resources/Language/locallang_db.xml:tx_blogexample_domain_blog.logo',
					'config'  => array(
						'type'          => 'group',
						'internal_type' => 'file',
						'allowed'       => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
						'max_size'      => 3000,
						'uploadfolder'  => 'uploads/pics',
						'show_thumbs'   => 1,
						'size'          => 1,
						'maxitems'      => 1,
						'minitems'      => 0
					)
				),
				'posts' => array(
					'exclude' => 1,
					'label'   => 'LLL:EXT:blogexample/Resources/Language/locallang_db.xml:tx_blogexample_domain_blog.posts',
					'config' => array(
						'type' => 'inline',
						// TODO is 'foreign_class' in $TCA the best way?
						'foreign_class' => 'Tx_BlogExample_Domain_Post',
						'foreign_table' => 'tx_blogexample_domain_post',
						'foreign_field' => 'blog',
						'foreign_table_field' => 'blog_table',
						'appearance' => array(
							'newRecordLinkPosition' => 'bottom',
							'collapseAll' => 1,
							'expandSingle' => 1,
						),
					)
				),
				'author' => array(
					'exclude' => 1,
					'label'   => 'LLL:EXT:blogexample/Resources/Language/locallang_db.xml:tx_blogexample_domain_blog.author',
					'config' => array(
						'type' => 'select',
						'foreign_class' => 'Tx_BlogExample_Domain_Author',
						'foreign_table' => 'tx_blogexample_domain_author',
						'maxitems' => 1,
					)
				),
			),
			'types' => array(
				'1' => array('showitem' => 'hidden, name, description, logo, posts')
			),
			'palettes' => array(
				'1' => array('showitem' => '')
			)
		);
	}

	public function test_DataMapCanBeInitialized() {
		$dataMap = new Tx_ExtBase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Blog');
		$dataMap->initialize();
		$columnMaps = $dataMap->getColumnMaps();
		$this->assertEquals(12, count($columnMaps), 'The data map was not initialized (wrong number of column maps set).');
	}
	
	public function test_DeletedColumnNameCanBeResolved() {
		$dataMap = new Tx_ExtBase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Blog');
		$deletedColumnName = $dataMap->getDeletedColumnName();
		$this->assertEquals($deletedColumnName, 'deleted', 'The deleted column name could not be resolved.');
	}
	
	public function test_HiddenColumnNameCanBeResolved() {
		$dataMap = new Tx_ExtBase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Blog');
		$hiddenColumnName = $dataMap->getHiddenColumnName();
		$this->assertEquals($hiddenColumnName, 'hidden', 'The hidden column name could not be resolved.');
	}
	
	public function test_ColumnCanBeAdded() {
		$dataMap = new Tx_ExtBase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Blog');
		$dataMap->addColumn('test_column');
		$columnMaps = $dataMap->getColumnMaps();
		$columnMap = array_pop($columnMaps);
		$this->assertType('Tx_ExtBase_Persistence_Mapper_ColumnMap', $columnMap, 'The column could not be added.');
	}
	
	public function test_ColumnListCanBeRetrieved() {
		$dataMap = new Tx_ExtBase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Blog');
		$dataMap->addColumn('column1');
		$dataMap->addColumn('column2');
		$dataMap->addColumn('column3');
		$dataMap->addColumn('column4');
		$columnList = $dataMap->getColumnList();
		$this->assertEquals($columnList, 'column1,column2,column3,column4', 'The column list could not be retrieved.');
	}
	
	public function test_PersistablePropertyCanBeChecked() {
		$dataMap = new Tx_ExtBase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Blog');
		$dataMap->addColumn('configured_property');
		$this->assertTrue($dataMap->isPersistableProperty('configuredProperty'), 'The persistable property was marked as unpersistable.');
		$this->assertFalse($dataMap->isPersistableProperty('unconfiguredProperty'), 'The unpersistable property was marked asersistable.');
	}
	
	public function test_HasManyColumnIsRegisteredForForeignTable() {
		$dataMap = new Tx_ExtBase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Blog');
		$dataMap->initialize();
		$this->assertEquals(Tx_ExtBase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY, $dataMap->getColumnMap('posts')->getTypeOfRelation(), 'The posts relation was not of type HAS_MANY.');
	}
	
	public function test_HasOneColumnIsRegisteredForForeignTableWithMaxsizeOne() {
		$dataMap = new Tx_ExtBase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Blog');
		$dataMap->initialize();
		$this->assertEquals(Tx_ExtBase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE, $dataMap->getColumnMap('author')->getTypeOfRelation(), 'The author relation was not of type HAS_ONE.');
	}
}
?>