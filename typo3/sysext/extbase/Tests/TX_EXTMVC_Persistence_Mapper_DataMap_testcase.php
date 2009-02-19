<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(PATH_tslib . 'class.tslib_content.php');
require_once(t3lib_extMgm::extPath('blogexample') . 'Classes/Domain/TX_Blogexample_Domain_Blog.php');

class TX_EXTMVC_Persistence_Mapper_DataMap_testcase extends tx_phpunit_testcase {
	
	public function setUp() {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
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
						'foreign_class' => 'TX_Blogexample_Domain_Post',
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
		$dataMap = new TX_EXTMVC_Persistence_Mapper_DataMap('TX_Blogexample_Domain_Blog');
		$dataMap->initialize();
		$columnMaps = $dataMap->getColumnMaps();
		$this->assertEquals(11, count($columnMaps), 'The data map was not initialized (wrong number of column maps set).');
	}
	
}
?>