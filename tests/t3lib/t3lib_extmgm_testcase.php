<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Oliver Hader <oliver@typo3.org>
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
 * Testcase for class t3lib_extMgm
 *
 * @author	Oliver Hader <oliver@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_extmgm_testcase extends tx_phpunit_testcase {
	/**
	 * Contains backup of defined GLOBALS
	 * @var array
	 */
	protected $globals = array();

	public function setUp() {
		$this->globals = array(
			'TYPO3_LOADED_EXT' => serialize($GLOBALS['TYPO3_LOADED_EXT']),
		);
	}

	/**
	 * @test
	 * @see t3lib_extMgm::getExtensionKeyByPrefix
	 */
	public function checkGetExtensionKeyByPrefix() {
		$uniqueSuffix = uniqid('test');
		$GLOBALS['TYPO3_LOADED_EXT']['tt_news' . $uniqueSuffix] = array();
		$GLOBALS['TYPO3_LOADED_EXT']['kickstarter' . $uniqueSuffix] = array();

		$this->assertEquals(
			'tt_news' . $uniqueSuffix,
			t3lib_extMgm::getExtensionKeyByPrefix('tx_ttnews' . $uniqueSuffix)
		);
		$this->assertEquals(
			'kickstarter' . $uniqueSuffix,
			t3lib_extMgm::getExtensionKeyByPrefix('tx_kickstarter' . $uniqueSuffix)
		);
		$this->assertFalse(
			t3lib_extMgm::getExtensionKeyByPrefix('tx_unloadedextension' . $uniqueSuffix)
		);
	}

	/**
	 * Tests whether fields can be add to all TCA types and duplicate fields are considered.
	 * @test
	 * @see t3lib_extMgm::addToAllTCAtypes()
	 */
	public function canAddFieldsToAllTCATypesBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'before:fieldD');

			// Checking typeA:
		$this->assertEquals(
			'fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD',
			$GLOBALS['TCA'][$table]['types']['typeA']['showitem']
		);
			// Checking typeB:
		$this->assertEquals(
			'fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD',
			$GLOBALS['TCA'][$table]['types']['typeB']['showitem']
		);
	}

	/**
	 * Tests whether fields can be add to all TCA types and duplicate fields are considered.
	 * @test
	 * @see t3lib_extMgm::addToAllTCAtypes()
	 */
	public function canAddFieldsToAllTCATypesAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:fieldC');

			// Checking typeA:
		$this->assertEquals(
			'fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD',
			$GLOBALS['TCA'][$table]['types']['typeA']['showitem']
		);
			// Checking typeB:
		$this->assertEquals(
			'fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD',
			$GLOBALS['TCA'][$table]['types']['typeB']['showitem']
		);
	}

	/**
	 * Tests whether fields can be add to a TCA type before existing ones
	 * @test
	 * @see t3lib_extMgm::addToAllTCAtypes()
	 */
	public function canAddFieldsToTCATypeBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', 'typeA', 'before:fieldD');

			// Checking typeA:
		$this->assertEquals(
			'fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD',
			$GLOBALS['TCA'][$table]['types']['typeA']['showitem']
		);
			// Checking typeB:
		$this->assertEquals(
			'fieldA, fieldB, fieldC;labelC;paletteC;specialC, fieldD',
			$GLOBALS['TCA'][$table]['types']['typeB']['showitem']
		);
	}

	/**
	 * Tests whether fields can be add to a TCA type after existing ones
	 * @test
	 * @see t3lib_extMgm::addToAllTCAtypes()
	 */
	public function canAddFieldsToTCATypeAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', 'typeA', 'after:fieldC');

			// Checking typeA:
		$this->assertEquals(
			'fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD',
			$GLOBALS['TCA'][$table]['types']['typeA']['showitem']
		);
			// Checking typeB:
		$this->assertEquals(
			'fieldA, fieldB, fieldC;labelC;paletteC;specialC, fieldD',
			$GLOBALS['TCA'][$table]['types']['typeB']['showitem']
		);
	}

	/**
	 * Tests whether fields can be added to a palette before existing elements.
	 * @test
	 * @see t3lib_extMgm::addFieldsToPalette()
	 */
	public function canAddFieldsToPaletteBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'before:fieldY');

		$this->assertEquals(
			'fieldX, newA, newB, fieldY',
			$GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']
		);
	}

	/**
	 * Tests whether fields can be added to a palette after existing elements.
	 * @test
	 * @see t3lib_extMgm::addFieldsToPalette()
	 */
	public function canAddFieldsToPaletteAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:fieldX');

		$this->assertEquals(
			'fieldX, newA, newB, fieldY',
			$GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']
		);
	}

	/**
	 * Tests whether fields can be added to all palettes of a regular field before existing ones.
	 * @test
	 * @see t3lib_extMgm::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addFieldsToAllPalettesOfField($table, 'fieldC', 'newA, newA, newB, fieldX', 'before:fieldY');

		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']
		);
		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']
		);
		$this->assertEquals(
			'fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']
		);
		$this->assertEquals(
			'fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']
		);
	}

	/**
	 * Tests whether fields can be added to all palettes of a regular field before existing ones.
	 * @test
	 * @see t3lib_extMgm::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addFieldsToAllPalettesOfField($table, 'fieldC', 'newA, newB, fieldX', 'after:fieldX');

		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']
		);
		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']
		);
		$this->assertEquals(
			'fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']
		);
		$this->assertEquals(
			'fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']
		);
	}

	public function tearDown() {
		foreach ($this->globals as $key => $value) {
			$GLOBALS[$key] = unserialize($value);
		}
	}

	/**
	 * Generates a basic TCA for a given table.
	 *
	 * @param	string		$table: Name of the table
	 * @return	array		Generated TCA for the given table
	 */
	private function generateTCAForTable($table) {
		$tca = array();
		$tca[$table] = array();
		$tca[$table]['columns']['fieldC'] = array();
		$tca[$table]['types'] = array(
			'typeA' => array('showitem' => 'fieldA, fieldB, fieldC;labelC;paletteC;specialC, fieldD'),
			'typeB' => array('showitem' => 'fieldA, fieldB, fieldC;labelC;paletteC;specialC, fieldD'),
			'typeC' => array('showitem' => 'fieldC;;paletteD'),
		);
		$tca[$table]['palettes'] = array(
			'paletteA' => array('showitem' => 'fieldX, fieldY'),
			'paletteB' => array('showitem' => 'fieldX, fieldY'),
			'paletteC' => array('showitem' => 'fieldX, fieldY'),
			'paletteD' => array('showitem' => 'fieldX, fieldY'),
		);

		return $tca;
	}
}

?>