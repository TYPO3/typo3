<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3. 
*  All credits go to the v5 team.
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

class Tx_Extbase_Persistence_Mapper_DataMap_testcase extends Tx_Extbase_BaseTestCase {
	
	public function setUp() {
		require_once(t3lib_extMgm::extPath('blog_example') . 'Classes/Domain/Model/Blog.php');
	
		$GLOBALS['TSFE']->fe_user = $this->getMock('tslib_feUserAuth');
		$GLOBALS['TSFE'] = $this->getMock('tslib_fe', array('includeTCA'), array(), '', FALSE);
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
		$TCA['tx_blogexample_domain_model_blog'] = array (
			'ctrl' => array (
				'title'             => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog',
				'label'				=> 'name',
				'tstamp'            => 'tstamp',
				'prependAtCopy'     => 'LLL:EXT:lang/locallang_general.xml:LGL.prependAtCopy',
				'delete'            => 'deleted',
				'enablecolumns'     => array (
					'disabled' => 'hidden'
				),
				'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'Resources/Icons/icon_tx_blogexample_domain_model_blog.gif'
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
					'label'   => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog.name',
					'config'  => array(
						'type' => 'input',
						'size' => 20,
						'eval' => 'trim,required',
						'max'  => 256
					)
				),
				'description' => array(
					'exclude' => 1,
					'label'   => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog.description',
					'config'  => array(
						'type' => 'text',
						'eval' => 'required',
						'rows' => 30,
						'cols' => 80,
					)
				),
				'logo' => array(
					'exclude' => 1,
					'label'   => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog.logo',
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
					'label'   => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog.posts',
					'config' => array(
						'type' => 'inline',
						'foreign_class' => 'Tx_BlogExample_Domain_Model_Post',
						'foreign_table' => 'tx_blogexample_domain_model_post',
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
					'label'   => 'LLL:EXT:blog_example/Resources/Language/locallang_db.xml:tx_blogexample_domain_model_blog.author',
					'config' => array(
						'type' => 'select',
						'foreign_class' => 'Tx_BlogExample_Domain_Model_Author',
						'foreign_table' => 'tx_blogexample_domain_model_author',
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

	// public function test_DataMapCanBeInitialized() {
	// 	$dataMap = new Tx_Extbase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Model_Blog');
	// 	$columnMaps = $dataMap->getColumnMaps();
	// 	$this->assertEquals(10, count($columnMaps), 'The data map was not initialized (wrong number of column maps set).');
	// }
	// 
	// public function test_DeletedColumnNameCanBeResolved() {
	// 	$dataMap = new Tx_Extbase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Model_Blog');
	// 	$deletedColumnName = $dataMap->getDeletedColumnName();
	// 	$this->assertEquals($deletedColumnName, 'deleted', 'The deleted column name could not be resolved.');
	// }
	// 
	// public function test_HiddenColumnNameCanBeResolved() {
	// 	$dataMap = new Tx_Extbase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Model_Blog');
	// 	$hiddenColumnName = $dataMap->getHiddenColumnName();
	// 	$this->assertEquals($hiddenColumnName, 'hidden', 'The hidden column name could not be resolved.');
	// }
	// 
	// public function test_ColumnCanBeAdded() {
	// 	$dataMap = new Tx_Extbase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Model_Blog');
	// 	$dataMap->addColumn('test_column');
	// 	$columnMaps = $dataMap->getColumnMaps();
	// 	$columnMap = array_pop($columnMaps);
	// 	$this->assertType('Tx_Extbase_Persistence_Mapper_ColumnMap', $columnMap, 'The column could not be added.');
	// }
	// 
	// public function test_PersistablePropertyCanBeChecked() {
	// 	$dataMap = new Tx_Extbase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Model_Blog');
	// 	$dataMap->addColumn('configured_property');
	// 	$this->assertTrue($dataMap->isPersistableProperty('configuredProperty'), 'The persistable property was marked as unpersistable.');
	// 	$this->assertFalse($dataMap->isPersistableProperty('unconfiguredProperty'), 'The unpersistable property was marked asersistable.');
	// }
	// 
	// public function test_HasManyColumnIsRegisteredForForeignTable() {
	// 	$dataMap = new Tx_Extbase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Model_Blog');
	// 	$this->assertEquals(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY, $dataMap->getColumnMap('posts')->getTypeOfRelation(), 'The posts relation was not of type HAS_MANY.');
	// }
	// 
	// public function test_HasOneColumnIsRegisteredForForeignTableWithMaxsizeOne() {
	// 	$dataMap = new Tx_Extbase_Persistence_Mapper_DataMap('Tx_BlogExample_Domain_Model_Blog');
	// 	$this->assertEquals(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE, $dataMap->getColumnMap('author')->getTypeOfRelation(), 'The author relation was not of type HAS_ONE.');
	// }
	
	/**
	 * @test
	 */
	public function setRelationsDetectsOneToOneRelationOfTypeSelect() {
		$mockColumnMap = $this->getMock('Tx_Extbase_Persistence_Mapper_ColumnMap', array(), array(), '', FALSE);
	    $columnConfiguration = array(
			'type' => 'select',
			'foreign_table' => 'tx_myextension_bar',
			'foreign_field' => 'parentid',
			'foreign_table_field' => 'parenttable',
			'maxitems' => '1'
			);
		$mockDataMap = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Mapper_DataMap'), array('setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'), array(), '', FALSE);
		$mockDataMap->expects($this->once())->method('setOneToOneRelation');
		$mockDataMap->expects($this->never())->method('setOneToManyRelation');
		$mockDataMap->expects($this->never())->method('setManyToManyRelation');
		$mockDataMap->_callRef('setRelations', $mockColumnMap, $columnConfiguration);
	}
	
	/**
	 * @test
	 */
	public function setRelationsDetectsOneToOneRelationOfTypeInline() {
		$mockColumnMap = $this->getMock('Tx_Extbase_Persistence_Mapper_ColumnMap', array(), array(), '', FALSE);
	    $columnConfiguration = array(
			'type' => 'inline',
			'foreign_table' => 'tx_myextension_bar',
			'foreign_field' => 'parentid',
			'foreign_table_field' => 'parenttable',
			'maxitems' => '1'
			);
		$mockDataMap = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Mapper_DataMap'), array('setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'), array(), '', FALSE);
		$mockDataMap->expects($this->once())->method('setOneToOneRelation');
		$mockDataMap->expects($this->never())->method('setOneToManyRelation');
		$mockDataMap->expects($this->never())->method('setManyToManyRelation');
		$mockDataMap->_callRef('setRelations', $mockColumnMap, $columnConfiguration);
	}
	
	/**
	 * @test
	 */
	public function setRelationsDetectsOneToManyRelationOfTypeSelect() {
		$mockColumnMap = $this->getMock('Tx_Extbase_Persistence_Mapper_ColumnMap', array(), array(), '', FALSE);
	    $columnConfiguration = array(
			'type' => 'select',
			'foreign_table' => 'tx_myextension_bar',
			'foreign_field' => 'parentid',
			'foreign_table_field' => 'parenttable'
			);
		$mockDataMap = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Mapper_DataMap'), array('setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'), array(), '', FALSE);
		$mockDataMap->expects($this->never())->method('setOneToOneRelation');
		$mockDataMap->expects($this->once())->method('setOneToManyRelation');
		$mockDataMap->expects($this->never())->method('setManyToManyRelation');
		$mockDataMap->_callRef('setRelations', $mockColumnMap, $columnConfiguration);
	}
	
	/**
	 * @test
	 */
	public function setRelationsDetectsOneToManyRelationWitTypeInline() {
		$mockColumnMap = $this->getMock('Tx_Extbase_Persistence_Mapper_ColumnMap', array(), array(), '', FALSE);
	    $columnConfiguration = array(
			'type' => 'inline',
			'foreign_table' => 'tx_myextension_bar',
			'foreign_field' => 'parentid',
			'foreign_table_field' => 'parenttable'
			);
		$mockDataMap = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Mapper_DataMap'), array('setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'), array(), '', FALSE);
		$mockDataMap->expects($this->never())->method('setOneToOneRelation');
		$mockDataMap->expects($this->once())->method('setOneToManyRelation');
		$mockDataMap->expects($this->never())->method('setManyToManyRelation');
		$mockDataMap->_callRef('setRelations', $mockColumnMap, $columnConfiguration);
	}

	/**
	 * @test
	 */
	public function setRelationsDetectsManyToManyRelationOfTypeSelect() {
		$mockColumnMap = $this->getMock('Tx_Extbase_Persistence_Mapper_ColumnMap', array(), array(), '', FALSE);
	    $columnConfiguration = array(
			'type' => 'select',
			'foreign_table' => 'tx_myextension_bar',
			'MM' => 'tx_myextension_mm'
			);
		$mockDataMap = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Mapper_DataMap'), array('setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'), array(), '', FALSE);
		$mockDataMap->expects($this->never())->method('setOneToOneRelation');
		$mockDataMap->expects($this->never())->method('setOneToManyRelation');
		$mockDataMap->expects($this->once())->method('setManyToManyRelation');
		$mockDataMap->_callRef('setRelations', $mockColumnMap, $columnConfiguration);
	}
	
	/**
	 * @test
	 */
	public function setRelationsDetectsManyToManyRelationOfTypeInline() {
		$mockColumnMap = $this->getMock('Tx_Extbase_Persistence_Mapper_ColumnMap', array(), array(), '', FALSE);
	    $columnConfiguration = array(
			'type' => 'inline',
			'foreign_table' => 'tx_myextension_mm',
			'foreign_field' => 'uid_local',
			'foreign_label' => 'uid_foreign'
			);
		$mockDataMap = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Mapper_DataMap'), array('setOneToOneRelation', 'setOneToManyRelation', 'setManyToManyRelation'), array(), '', FALSE);
		$mockDataMap->expects($this->never())->method('setOneToOneRelation');
		$mockDataMap->expects($this->never())->method('setOneToManyRelation');
		$mockDataMap->expects($this->once())->method('setManyToManyRelation');
		$mockDataMap->_callRef('setRelations', $mockColumnMap, $columnConfiguration);
	}
	
	/**
	 * @test
	 */
	public function columnMapIsInitializedWithManyToManyRelationOfTypeSelect() {
		$leftColumnsDefinition = array(
			'rights' => array(
				'type' => 'select',
				'foreign_class' => 'Tx_MyExtension_RightClass',
				'foreign_table' => 'tx_myextension_righttable',
				'foreign_table_where' => 'WHERE 1=1',
				'MM' => 'tx_myextension_mm',
				'MM_table_where' => 'WHERE 2=2',
				),
			);
		$mockColumnMap = $this->getMock('Tx_Extbase_Persistence_Mapper_ColumnMap', array(), array(), '', FALSE);
		$mockColumnMap->expects($this->once())->method('setTypeOfRelation')->with($this->equalTo(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
		$mockColumnMap->expects($this->once())->method('setChildClassName')->with($this->equalTo('Tx_MyExtension_RightClass'));
		$mockColumnMap->expects($this->once())->method('setChildTableName')->with($this->equalTo('tx_myextension_righttable'));
		$mockColumnMap->expects($this->once())->method('setChildTableWhereStatement')->with($this->equalTo('WHERE 1=1'));
		$mockColumnMap->expects($this->once())->method('setChildSortbyFieldName')->with($this->equalTo('sorting'));
		$mockColumnMap->expects($this->once())->method('setParentKeyFieldName')->with($this->equalTo('uid_local'));
		$mockColumnMap->expects($this->never())->method('setParentTableFieldName');
		$mockColumnMap->expects($this->never())->method('setRelationTableMatchFields');
		$mockColumnMap->expects($this->never())->method('setRelationTableInsertFields');
		
		$mockDataMap = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Mapper_DataMap'), array('dummy'), array(), '', FALSE);
		$mockDataMap->_callRef('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
	}
	
	/**
	 * @test
	 */
	public function columnMapIsInitializedWithOppositeManyToManyRelationOfTypeSelect() {
		$rightColumnsDefinition = array(
			'lefts' => array(
				'type' => 'select',
				'foreign_class' => 'Tx_MyExtension_LeftClass',
				'foreign_table' => 'tx_myextension_lefttable',
				'MM' => 'tx_myextension_mm',
				'MM_opposite_field' => 'rights'
				),
			);
		$leftColumnsDefinition['rights']['MM_opposite_field'] = 'opposite_field';		
		$mockColumnMap = $this->getMock('Tx_Extbase_Persistence_Mapper_ColumnMap', array(), array(), '', FALSE);
		$mockColumnMap->expects($this->once())->method('setTypeOfRelation')->with($this->equalTo(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
		$mockColumnMap->expects($this->once())->method('setChildClassName')->with($this->equalTo('Tx_MyExtension_LeftClass'));
		$mockColumnMap->expects($this->once())->method('setChildTableName')->with($this->equalTo('tx_myextension_lefttable'));
		$mockColumnMap->expects($this->once())->method('setChildTableWhereStatement')->with(NULL);
		$mockColumnMap->expects($this->once())->method('setChildSortbyFieldName')->with($this->equalTo('sorting_foreign'));
		$mockColumnMap->expects($this->once())->method('setParentKeyFieldName')->with($this->equalTo('uid_foreign'));
		$mockColumnMap->expects($this->never())->method('setParentTableFieldName');
		$mockColumnMap->expects($this->never())->method('setRelationTableMatchFields');
		$mockColumnMap->expects($this->never())->method('setRelationTableInsertFields');
		
		$mockDataMap = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Mapper_DataMap'), array('dummy'), array(), '', FALSE);
		$mockDataMap->_callRef('setManyToManyRelation', $mockColumnMap, $rightColumnsDefinition['lefts']);
	}
	
	/**
	 * @test
	 */
	public function columnMapIsInitializedWithManyToManyRelationOfTypeInline() {
	    $leftColumnsDefinition = array(
			'rights' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_myextension_mm',
				'foreign_field' => 'uid_local',
				'foreign_label' => 'uid_foreign',
				'foreign_sortby' => 'sorting'
				)
			);
	    $relationTableColumnsDefiniton = array(
			'uid_local' => array(
				'foreign_class' => 'Tx_MyExtension_LocalClass',
				'foreign_table' => 'tx_myextension_localtable'
				),
			'uid_foreign' => array(
				'foreign_class' => 'Tx_MyExtension_RightClass',
				'foreign_table' => 'tx_myextension_righttable'
				)
			);
	    $rightColumnsDefinition = array(
			'lefts' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_myextension_mm',
				'foreign_field' => 'uid_foreign',
				'foreign_label' => 'uid_local',
				'foreign_sortby' => 'sorting_foreign'
				)
			);
		$mockColumnMap = $this->getMock('Tx_Extbase_Persistence_Mapper_ColumnMap', array(), array(), '', FALSE);
		$mockColumnMap->expects($this->once())->method('setTypeOfRelation')->with($this->equalTo(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY));
		$mockColumnMap->expects($this->once())->method('setChildClassName')->with($this->equalTo('Tx_MyExtension_RightClass'));
		$mockColumnMap->expects($this->once())->method('setChildTableName')->with($this->equalTo('tx_myextension_righttable'));
		$mockColumnMap->expects($this->never())->method('setChildTableWhereStatement');
		$mockColumnMap->expects($this->once())->method('setChildSortbyFieldName')->with($this->equalTo('sorting'));
		$mockColumnMap->expects($this->once())->method('setParentKeyFieldName')->with($this->equalTo('uid_local'));
		$mockColumnMap->expects($this->never())->method('setParentTableFieldName');
		$mockColumnMap->expects($this->never())->method('setRelationTableMatchFields');
		$mockColumnMap->expects($this->never())->method('setRelationTableInsertFields');
		
		$mockDataMap = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Mapper_DataMap'), array('getColumnsDefinition', 'determineChildClassName'), array(), '', FALSE);
		$mockDataMap->expects($this->once())->method('getColumnsDefinition')->with($this->equalTo('tx_myextension_mm'))->will($this->returnValue($relationTableColumnsDefiniton));
		$mockDataMap->expects($this->once())->method('determineChildClassName')->with($this->equalTo($relationTableColumnsDefiniton['uid_foreign']))->will($this->returnValue('Tx_MyExtension_RightClass'));
		$mockDataMap->_callRef('setManyToManyRelation', $mockColumnMap, $leftColumnsDefinition['rights']);
	}
	
}
?>