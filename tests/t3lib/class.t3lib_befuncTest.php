<?php
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
 * Testcase for the t3lib_BEfunc class in the TYPO3 core.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class t3lib_befuncTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 * @var t3lib_BEfunc
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new t3lib_BEfunc();
	}

	public function tearDown() {
		unset($this->fixture);
	}


	///////////////////////////////////////
	// Tests concerning getProcessedValue
	///////////////////////////////////////

	/**
	 * @test
	 *
	 * @see http://bugs.typo3.org/view.php?id=11875
	 */
	public function getProcessedValueForZeroStringIsZero() {
		$this->assertEquals(
			'0',
			$this->fixture->getProcessedValue(
				'tt_content', 'header', '0'
			)
		);
	}


	////////////////////////////////////////////
	// Tests concerning getCommenSelectFields
	////////////////////////////////////////////

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
				'expectedFields' => 'uid',
			),
			'label set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'label'=> 'label',
					)
				),
				'expectedFields' => 'uid,label',
			),
			'label_alt set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'label_alt'=> 'label,label2',
					)
				),
				'expectedFields' => 'uid,label,label2',
			),
			'versioningWS set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'versioningWS'=> '2',
					)
				),
				'expectedFields' => 'uid,t3ver_id,t3ver_state,t3ver_wsid,t3ver_count',
			),
			'selicon_field set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'selicon_field'=> 'field',
					)
				),
				'expectedFields' => 'uid,field',
			),
			'typeicon_column set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'typeicon_column'=> 'field',
					)
				),
				'expectedFields' => 'uid,field',
			),
			'enablecolumns set' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'enablecolumns'=> array(
							'disabled' => 'hidden',
							'starttime' => 'start',
							'endtime' => 'stop',
							'fe_group' => 'groups',
						),
					)
				),
				'expectedFields' => 'uid,hidden,start,stop,groups',
			),
			'label set to uid' => array(
				'table' => 'test_table',
				'prefix' => '',
				'presetFields' => array(),
				'tca' => array(
					'ctrl' => array(
						'label'=> 'uid',
					)
				),
				'expectedFields' => 'uid',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getCommonSelectFieldsReturnsCorrectFieldsDataProvider
	 */
	public function getCommonSelectFieldsReturnsCorrectFields($table, $prefix = '', array $presetFields, array $tca, $expectedFields = '') {
		t3lib_div::loadTCA($table);
		$tcaBackup = $GLOBALS['TCA'][$table];
		unset($GLOBALS['TCA'][$table]);
		$GLOBALS['TCA'][$table] = $tca;

		$selectFields = $this->fixture->getCommonSelectFields($table, $prefix, $presetFields);

		unset($GLOBALS['TCA'][$table]);
		$GLOBALS['TCA'][$table] = $tcaBackup;

		$this->assertEquals($selectFields, $expectedFields);
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
		$this->assertSame($expected, t3lib_BEfunc::getExcludeFields());
	}
}
?>