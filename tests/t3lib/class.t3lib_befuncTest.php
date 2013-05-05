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
	 * @var t3lib_BEfunc
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new t3lib_BEfunc();
	}

	public function tearDown() {
		unset($this->fixture);
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