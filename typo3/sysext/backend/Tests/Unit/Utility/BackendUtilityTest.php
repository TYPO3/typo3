<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Utility;
use TYPO3\CMS\Backend\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2009-2013 Oliver Klee (typo3-coding@oliverklee.de)
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
	// Tests concerning calcAge
	///////////////////////////////////////
	/**
	 * Data provider for calcAge function
	 *
	 * @return array
	 */
	public function calcAgeDataProvider() {
		return array(
			'Single year' => array(
				'seconds' => 60 * 60 * 24 * 365,
				'expectedLabel' => '1 year'
			),
			'Plural years' => array(
				'seconds' => 60 * 60 * 24 * 365 * 2,
				'expectedLabel' => '2 yrs'
			),
			'Single negative year' => array(
				'seconds' => 60 * 60 * 24 * 365 * -1,
				'expectedLabel' => '-1 year'
			),
			'Plural negative years' => array(
				'seconds' => 60 * 60 * 24 * 365 * 2 * -1,
				'expectedLabel' => '-2 yrs'
			),
			'Single day' => array(
				'seconds' => 60 * 60 * 24,
				'expectedLabel' => '1 day'
			),
			'Plural days' => array(
				'seconds' => 60 * 60 * 24 * 2,
				'expectedLabel' => '2 days'
			),
			'Single negative day' => array(
				'seconds' => 60 * 60 * 24 * -1,
				'expectedLabel' => '-1 day'
			),
			'Plural negative days' => array(
				'seconds' => 60 * 60 * 24 * 2 * -1,
				'expectedLabel' => '-2 days'
			),
			'Single hour' => array(
				'seconds' => 60 * 60,
				'expectedLabel' => '1 hour'
			),
			'Plural hours' => array(
				'seconds' => 60 * 60 * 2,
				'expectedLabel' => '2 hrs'
			),
			'Single negative hour' => array(
				'seconds' => 60 * 60 * -1,
				'expectedLabel' => '-1 hour'
			),
			'Plural negative hours' => array(
				'seconds' => 60 * 60 * 2 * -1,
				'expectedLabel' => '-2 hrs'
			),
			'Single minute' => array(
				'seconds' => 60,
				'expectedLabel' => '1 min'
			),
			'Plural minutes' => array(
				'seconds' => 60 * 2,
				'expectedLabel' => '2 min'
			),
			'Single negative minute' => array(
				'seconds' => 60 * -1,
				'expectedLabel' => '-1 min'
			),
			'Plural negative minutes' => array(
				'seconds' => 60 * 2 * -1,
				'expectedLabel' => '-2 min'
			),
			'Zero seconds' => array(
				'seconds' => 0,
				'expectedLabel' => '0 min'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider calcAgeDataProvider
	 */
	public function calcAgeReturnsExpectedValues($seconds, $expectedLabel) {
		$this->assertSame($expectedLabel, $this->fixture->calcAge($seconds));
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
	 * @test
	 */
	public function getProcessedValueForGroup() {
		$this->assertSame('1, 2', $this->fixture->getProcessedValue('tt_content', 'multimedia', '1,2'));
	}

	/**
	 * @test
	 */
	public function getProcessedValueForGroupWithOneAllowedTable() {
		/** @var \PHPUnit_Framework_MockObject_MockObject|Utility\BackendUtility $fixture */
		$fixture = $this->getMock('TYPO3\\CMS\\Backend\\Utility\\BackendUtility', array('getRecordWSOL'));
		$fixture->staticExpects($this->at(0))->method('getRecordWSOL')->will($this->returnValue(array('title' => 'Page 1')));
		$fixture->staticExpects($this->at(1))->method('getRecordWSOL')->will($this->returnValue(array('title' => 'Page 2')));
		$this->assertSame('Page 1, Page 2', $fixture->getProcessedValue('tt_content', 'pages', '1,2'));
	}

	/**
	 * @test
	 */
	public function getProcessedValueForGroupWithMultipleAllowedTables() {
		/** @var \PHPUnit_Framework_MockObject_MockObject|Utility\BackendUtility $fixture */
		$fixture = $this->getMock('TYPO3\\CMS\\Backend\\Utility\\BackendUtility', array('getRecordWSOL'));
		$fixture->staticExpects($this->at(0))->method('getRecordWSOL')->will($this->returnValue(array('title' => 'Page 1')));
		$fixture->staticExpects($this->at(1))->method('getRecordWSOL')->will($this->returnValue(array('header' => 'Content 2')));
		$this->assertSame('Page 1, Content 2', $fixture->getProcessedValue('sys_category', 'items', 'pages_1,tt_content_2'));
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
			'getExcludeFields sorts tables and properties with flexform fields properly' => array(
				array(
					'tx_foo' => array(
						'ctrl' => array(
							'title' => 'foo'
						),
						'columns' => array(
							'foo' => array(
								'label' => 'foo',
								'exclude' => 1
							),
							'bar' => array(
								'label' => 'bar',
								'exclude' => 1
							),
							'abarfoo' => array(
								'label' => 'abarfoo',
								'config' => array(
									'type' => 'flex',
									'ds' => array(
										'*,dummy' => '<?xml version="1.0" encoding="utf-8"?>
<T3DataStructure>
	<sheets>
		<sGeneral>
			<ROOT>
				<type>array</type>
				<el>
					<xmlTitle>
						<TCEforms>
							<exclude>1</exclude>
							<label>The Title:</label>
							<config>
								<type>input</type>
								<size>48</size>
							</config>
						</TCEforms>
					</xmlTitle>
				</el>
			</ROOT>
		</sGeneral>
	</sheets>
</T3DataStructure>'
									)
								)
							)
						)
					),
					'tx_foobar' => array(
						'ctrl' => array(
							'title' => 'foobar'
						),
						'columns' => array(
							'foo' => array(
								'label' => 'foo',
								'exclude' => 1
							),
							'bar' => array(
								'label' => 'bar',
								'exclude' => 1
							)
						)
					),
					'tx_bar' => array(
						'ctrl' => array(
							'title' => 'bar'
						),
						'columns' => array(
							'foo' => array(
								'label' => 'foo',
								'exclude' => 1
							),
							'bar' => array(
								'label' => 'bar',
								'exclude' => 1
							)
						)
					)
				),
				array(
					array(
						'bar: bar',
						'tx_bar:bar'
					),
					array(
						'bar: foo',
						'tx_bar:foo'
					),
					array(
						'abarfoo dummy: The Title:',
						'tx_foo:abarfoo;dummy;sGeneral;xmlTitle'
					),
					array(
						'foo: bar',
						'tx_foo:bar'
					),
					array(
						'foo: foo',
						'tx_foo:foo'
					),
					array(
						'foobar: bar',
						'tx_foobar:bar'
					),
					array(
						'foobar: foo',
						'tx_foobar:foo'
					),
				)
			)
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