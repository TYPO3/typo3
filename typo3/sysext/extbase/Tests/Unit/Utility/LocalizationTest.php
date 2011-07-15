<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Extbase Team
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

/**
 * Testcase for class Tx_Extbase_Utility_Localization
 *
 * @package Extbase
 * @subpackage Utility
 */
class Tx_Extbase_Tests_Unit_Utility_LocalizationTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_Utility_Localization
	 */
	protected $localization;

	public function setUp() {
		$this->localization = $this->getAccessibleMock('Tx_Extbase_Utility_Localization', array('dummy'));
	}

	public function tearDown() {
		$this->localization = NULL;
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function implodeTypoScriptLabelArrayWorks() {
		$expected = array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3.subkey1' => 'subvalue1',
			'key3.subkey2.subsubkey' => 'val'
		);
		$actual = $this->localization->_call('flattenTypoScriptLabelArray', array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => array(
				'subkey1' => 'subvalue1',
				'subkey2' => array(
					'subsubkey' => 'val'
				)
			)
		));
		$this->assertEquals($expected, $actual);
	}
}
?>