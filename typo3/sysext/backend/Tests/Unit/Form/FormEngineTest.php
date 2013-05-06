<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Sebastian Michaelsen <michaelsen@t3seo.de>
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
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 */
class FormEngineTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Backend\Form\FormEngine
	 */
	protected $fixture;

	/**
	 * Sets up this test case.
	 */
	protected function setUp() {
		$this->fixture = new \TYPO3\CMS\Backend\Form\FormEngine();
	}

	/**
	 * Tears down this test case.
	 */
	protected function tearDown() {
		unset($this->fixture);
	}

	/**
	 * Returns data sets for the test matchConditionStrings
	 * Each dataset is an array with the following elements:
	 * - the condition string
	 * - the current record
	 * - the current flexform value key
	 * - the expected result
	 *
	 * @return array
	 */
	public function conditionStringDataProvider() {
		return array(
			'Invalid condition string' => array(
				'xINVALIDx:',
				array(),
				NULL,
				FALSE,
			),
			'Not loaded extension compares to loaded as FALSE' => array(
				'EXT:neverloadedext:LOADED:TRUE',
				array(),
				NULL,
				FALSE,
			),
			'Not loaded extension compares to not loaded as TRUE' => array(
				'EXT:neverloadedext:LOADED:FALSE',
				array(),
				NULL,
				TRUE,
			),
			'Loaded extension compares to TRUE' => array(
				'EXT:backend:LOADED:TRUE',
				array(),
				NULL,
				TRUE,
			),
			'Loaded extension compares to FALSE' => array(
				'EXT:backend:LOADED:FALSE',
				array(),
				NULL,
				FALSE,
			),
			'Field is not greater zero if not given' => array(
				'FIELD:uid:>:0',
				array(),
				NULL,
				FALSE,
			),
			'Field is not equal 0 if not given' => array(
				'FIELD:uid:=:0',
				array(),
				NULL,
				FALSE,
			),
			'Field value string comparison' => array(
				'FIELD:foo:=:bar',
				array('foo' => 'bar'),
				NULL,
				TRUE,
			),
			'Field value string comparison with colons in value' => array(
				'FIELD:foo:=::bar:',
				array('foo' => ':bar:'),
				NULL,
				TRUE,
			),
			'Field value comparison for required value is false for different value' => array(
				'FIELD:foo:REQ:FALSE',
				array('foo' => 'bar'),
				NULL,
				FALSE,
			),
			'Field value string not equal comparison' => array(
				'FIELD:foo:!=:baz',
				array('foo' => 'bar'),
				NULL,
				TRUE,
			),
			'Field value in range' => array(
				'FIELD:uid:-:3-42',
				array('uid' => '23'),
				NULL,
				TRUE,
			),
			'Field value greater than' => array(
				'FIELD:uid:>=:42',
				array('uid' => '23'),
				NULL,
				FALSE,
			),
			'Flexform value invalid comparison' => array(
				'FIELD:foo:=:bar',
				array(
					'foo' => array(
						'vDEF' => 'bar'
					),
				),
				'vDEF',
				TRUE,
			),
			'Flexform value valid comparison' => array(
				'FIELD:parentRec.foo:=:bar',
				array(
					'parentRec' => array(
						'foo' => 'bar'
					),
				),
				'vDEF',
				TRUE,
			),
			'Field is value for default languge without flexform' => array(
				'HIDE_L10N_SIBLINGS',
				array(),
				NULL,
				FALSE,
			),
			'Field is value for default languge with flexform' => array(
				'HIDE_L10N_SIBLINGS',
				array(),
				'vDEF',
				TRUE,
			),
			'Field is value for default languge with sibling' => array(
				'HIDE_L10N_SIBLINGS',
				array(),
				'vEN',
				FALSE,
			),
			'New is TRUE for new comparison with TRUE' => array(
				'REC:NEW:TRUE',
				array('uid' => NULL),
				NULL,
				TRUE,
			),
			'New is FALSE for new comparison with FALSE' => array(
				'REC:NEW:FALSE',
				array('uid' => NULL),
				NULL,
				FALSE,
			),
			'New is FALSE for not new element' => array(
				'REC:NEW:TRUE',
				array('uid' => 42),
				NULL,
				FALSE,
			),
			'New is TRUE for not new element compared to FALSE' => array(
				'REC:NEW:FALSE',
				array('uid' => 42),
				NULL,
				TRUE,
			),
			'Version is TRUE for versioned row' => array(
				'VERSION:IS:TRUE',
				array(
					'uid' => 42,
					'pid' => -1
				),
				NULL,
				TRUE,
			),
			'Version is TRUE for not versioned row compared with FALSE' => array(
				'VERSION:IS:FALSE',
				array(
					'uid' => 42,
					'pid' => 1
				),
				NULL,
				TRUE,
			),
			'Version is TRUE for NULL row compared with TRUE' => array(
				'VERSION:IS:TRUE',
				array(
					'uid' => NULL,
					'pid' => NULL,
				),
				NULL,
				FALSE,
			),
		);
	}

	/**
	 * @param string $condition
	 * @param array $record
	 * @param string $flexformValueKey
	 * @param string $expectedResult
	 * @dataProvider conditionStringDataProvider
	 * @test
	 */
	public function matchConditionStrings($condition, array $record, $flexformValueKey, $expectedResult) {
		$this->assertEquals($expectedResult, $this->fixture->isDisplayCondition($condition, $record, $flexformValueKey));
	}
}
?>