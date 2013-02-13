<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Utility;
use TYPO3\CMS\Backend\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2009-2011 Oliver Klee (typo3-coding@oliverklee.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for \TYPO3\CMS\Core\Utility\BackendUtility
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class BackendUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Backend\Utility\BackendUtility
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new Utility\BackendUtility();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	///////////////////////////////////////
	// Tests concerning getProcessedValue
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://bugs.typo3.org/view.php?id=11875
	 */
	public function getProcessedValueForZeroStringIsZero() {
		$this->assertEquals('0', $this->fixture->getProcessedValue('tt_content', 'header', '0'));
	}

	/**
	 * Tests concerning getCommenSelectFields
	 */

	/**
	 * Data provider for getCommonSelectFieldsReturnsCorrectFields
	 *
	 * @return array The test data with $table, $prefix, $presetFields, $tca, $expectedFields
	 */
	public function getCommonSelectFieldsReturnsCorrectFieldsDataProvider() {
		return array(
			'only uid' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(),
				'expectedFields' => 'uid'
			),
			'label set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'label' => 'label'
					)
				),
				'expectedFields' => 'uid,label'
			),
			'label_alt set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'label_alt' => 'label,label2'
					)
				),
				'expectedFields' => 'uid,label,label2'
			),
			'versioningWS set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'versioningWS' => '2'
					)
				),
				'expectedFields' => 'uid,t3ver_id,t3ver_state,t3ver_wsid,t3ver_count'
			),
			'selicon_field set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'selicon_field' => 'field'
					)
				),
				'expectedFields' => 'uid,field'
			),
			'typeicon_column set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'typeicon_column' => 'field'
					)
				),
				'expectedFields' => 'uid,field'
			),
			'enablecolumns set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'enablecolumns' => array(
							'disabled' => 'hidden',
							'starttime' => 'start',
							'endtime' => 'stop',
							'fe_group' => 'groups'
						)
					)
				),
				'expectedFields' => 'uid,hidden,start,stop,groups'
			),
			'label set to uid' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'label' => 'uid'
					)
				),
				'expectedFields' => 'uid'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider getCommonSelectFieldsReturnsCorrectFieldsDataProvider
	 */
	public function getCommonSelectFieldsReturnsCorrectFields($table, $prefix = '', array $presetFields, array $tca, $expectedFields = '') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA($table);
		$tcaBackup = $GLOBALS['TCA'][$table];
		unset($GLOBALS['TCA'][$table]);
		$GLOBALS['TCA'][$table] = $tca;
		$selectFields = $this->fixture->getCommonSelectFields($table, $prefix, $presetFields);
		unset($GLOBALS['TCA'][$table]);
		$GLOBALS['TCA'][$table] = $tcaBackup;
		$this->assertEquals($selectFields, $expectedFields);
	}

	/**
	 * Tests concerning getLabelFromItemlist
	 */

	/**
	 * Data provider for getLabelFromItemlistReturnsCorrectFields
	 *
	 * @return array The test data with $table, $col, $key, $expectedLabel
	 */
	public function getLabelFromItemlistReturnsCorrectFieldsDataProvider() {
		return array(
			'item set' => array(
				'table' => 'tt_content',
				'col' => 'menu_type',
				'key' => '1',
				'tca' => array(
					'columns' => array(
						'menu_type' => array(
							'config' => array(
								'items' => array(
									array('Item 1', '0'),
									array('Item 2', '1'),
									array('Item 3', '3')
								)
							)
						)
					)
				),
				'expectedLabel' => 'Item 2'
			),
			'item set twice' => array(
				'table' => 'tt_content',
				'col' => 'menu_type',
				'key' => '1',
				'tca' => array(
					'columns' => array(
						'menu_type' => array(
							'config' => array(
								'items' => array(
									array('Item 1', '0'),
									array('Item 2a', '1'),
									array('Item 2b', '1'),
									array('Item 3', '3')
								)
							)
						)
					)
				),
				'expectedLabel' => 'Item 2a'
			),
			'item not found' => array(
				'table' => 'tt_content',
				'col' => 'menu_type',
				'key' => '5',
				'tca' => array(
					'columns' => array(
						'menu_type' => array(
							'config' => array(
								'items' => array(
									array('Item 1', '0'),
									array('Item 2', '1'),
									array('Item 3', '2')
								)
							)
						)
					)
				),
				'expectedLabel' => NULL
			)
		);
	}

	/**
	 * @test
	 * @dataProvider getLabelFromItemlistReturnsCorrectFieldsDataProvider
	 */
	public function getLabelFromItemlistReturnsCorrectFields($table, $col = '', $key = '', array $tca, $expectedLabel = '') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA($table);
		$tcaBackup = $GLOBALS['TCA'][$table];
		unset($GLOBALS['TCA'][$table]);
		$GLOBALS['TCA'][$table] = $tca;
		$label = $this->fixture->getLabelFromItemlist($table, $col, $key);
		unset($GLOBALS['TCA'][$table]);
		$GLOBALS['TCA'][$table] = $tcaBackup;
		$this->assertEquals($label, $expectedLabel);
	}

	/**
	 * Tests concerning getLabelFromItemListMerged
	 */

	/**
	 * Data provider for getLabelFromItemListMerged
	 *
	 * @return array The test data with $pageId, $table, $column, $key, $expectedLabel
	 */
	public function getLabelFromItemListMergedReturnsCorrectFieldsDataProvider() {
		return array(
			'no field found' => array(
				'pageId' => '123',
				'table' => 'tt_content',
				'col' => 'menu_type',
				'key' => '10',
				'tca' => array(
					'columns' => array(
						'menu_type' => array(
							'config' => array(
								'items' => array(
									array('Item 1', '0'),
									array('Item 2', '1'),
									array('Item 3', '3')
								)
							)
						)
					)
				),
				'expectedLabel' => ''
			),
			'no tsconfig set' => array(
				'pageId' => '123',
				'table' => 'tt_content',
				'col' => 'menu_type',
				'key' => '1',
				'tca' => array(
					'columns' => array(
						'menu_type' => array(
							'config' => array(
								'items' => array(
									array('Item 1', '0'),
									array('Item 2', '1'),
									array('Item 3', '3')
								)
							)
						)
					)
				),
				'expectedLabel' => 'Item 2'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider getLabelFromItemListMergedReturnsCorrectFieldsDataProvider
	 */
	public function getLabelFromItemListMergedReturnsCorrectFields($pageId, $table, $column = '', $key = '', array $tca, $expectedLabel = '') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA($table);
		$tcaBackup = $GLOBALS['TCA'][$table];
		unset($GLOBALS['TCA'][$table]);
		$GLOBALS['TCA'][$table] = $tca;
		$label = $this->fixture->getLabelFromItemListMerged($pageId, $table, $column, $key);
		unset($GLOBALS['TCA'][$table]);
		$GLOBALS['TCA'][$table] = $tcaBackup;
		$this->assertEquals($label, $expectedLabel);
	}

	/**
	 * Tests concerning getFuncCheck
	 */

	/**
	 * @test
	 */
	public function getFuncCheckReturnsInputTagWithValueAttribute() {
		$this->assertStringMatchesFormat('<input %Svalue="1"%S/>', Utility\BackendUtility::getFuncCheck('params', 'test', TRUE));
	}

	/**
	 * Tests concerning getExcludeFields
	 */

	/**
	 * @return array
	 */
	public function getExcludeFieldsDataProvider() {
		return array(
			'getExcludeFields does not return fields not configured as exclude field' => array(
				array(
					'tx_foo' => array(
						'ctrl' => array(
							'title' => 'foo',
						),
						'columns' => array(
							'bar' => array(
								'label' => 'bar',
								'exclude' => 1
							),
							'baz' => array(
								'label' => 'bar',
							),
						)
					)
				),
				array(
					array(
						'foo: bar',
						'tx_foo:bar',
					),
				)
			),
			'getExcludeFields returns fields from root level tables if root level restriction should be ignored' => array(
				array(
					'tx_foo' => array(
						'ctrl' => array(
							'title' => 'foo',
							'rootLevel' => TRUE,
							'security' => array(
								'ignoreRootLevelRestriction' => TRUE,
							),
						),
						'columns' => array(
							'bar' => array(
								'label' => 'bar',
								'exclude' => 1
							),
						)
					)
				),
				array(
					array(
						'foo: bar',
						'tx_foo:bar',
					),
				)
			),
			'getExcludeFields does not return fields from root level tables' => array(
				array(
					'tx_foo' => array(
						'ctrl' => array(
							'title' => 'foo',
							'rootLevel' => TRUE,
						),
						'columns' => array(
							'bar' => array(
								'label' => 'bar',
								'exclude' => 1
							),
						)
					)
				),
				array()
			),
			'getExcludeFields does not return fields from admin only level tables' => array(
				array(
					'tx_foo' => array(
						'ctrl' => array(
							'title' => 'foo',
							'adminOnly' => TRUE,
						),
						'columns' => array(
							'bar' => array(
								'label' => 'bar',
								'exclude' => 1
							),
						)
					)
				),
				array()
			),
		);
	}

	/**
	 * @param $tca
	 * @param $expected
	 *
	 * @test
	 * @dataProvider getExcludeFieldsDataProvider
	 */
	public function getExcludeFieldsReturnsCorrectFieldList($tca, $expected) {
		$GLOBALS['TCA'] = $tca;
		$this->assertSame($expected, \TYPO3\CMS\Backend\Utility\BackendUtility::getExcludeFields());
	}
}

?>