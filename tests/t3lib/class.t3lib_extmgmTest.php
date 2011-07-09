<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Oliver Hader <oliver@typo3.org>
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
 * @author Oliver Hader <oliver@typo3.org>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_extmgmTest extends tx_phpunit_testcase {

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
	 * phpunit still needs some globals that are
	 * reconstructed before $backupGlobals is handled. Those
	 * important globals are handled in tearDown() directly.
	 *
	 * @var array
	 */
	protected $globals = array();

	public function setUp() {
		$this->globals = array(
			'TYPO3_LOADED_EXT' => serialize($GLOBALS['TYPO3_LOADED_EXT']),
		);
	}

	public function tearDown() {
		t3lib_extMgm::clearExtensionKeyMap();

		foreach ($this->globals as $key => $value) {
			$GLOBALS[$key] = unserialize($value);
		}
	}

	///////////////////////////////
	// Tests concerning extPath
	///////////////////////////////

	/**
	 * @test
	 * @expectedException BadFunctionCallException
	 */
	public function extPathThrowsExceptionIfExtensionIsNotLoaded() {
		$GLOBALS['TYPO3_LOADED_EXT']['foo'] = array();
		t3lib_extMgm::extPath('bar');
	}

	/**
	 * @test
	 */
	public function extPathAppendsScriptNameToPath() {
		$GLOBALS['TYPO3_LOADED_EXT']['foo']['siteRelPath'] = 'foo/';
		$this->assertSame(PATH_site . 'foo/bar.txt', t3lib_extMgm::extPath('foo', 'bar.txt'));
	}

	/**
	 * @test
	 * @expectedException BadFunctionCallException
	 */
	public function extPathThrowsExceptionIfExtensionIsNotLoadedAndTypo3LoadedExtensionsIsEmpty() {
		unset($GLOBALS['TYPO3_LOADED_EXT']);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = '';
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = '';
		t3lib_extMgm::extPath('bar');
	}

	/**
	 * @test
	 */
	public function extPathSearchesForPathOfExtensionInRequiredExtensionList() {
		$this->setExpectedException('Exception', '', 1294430951);

		unset($GLOBALS['TYPO3_LOADED_EXT']);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = 'foo';
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = '';

		t3lib_extMgm::extPath('foo');
	}

	/**
	 * @test
	 */
	public function extPathSearchesForPathOfExtensionInExtList() {
		$this->setExpectedException('Exception', '', 1294430951);

		unset($GLOBALS['TYPO3_LOADED_EXT']);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = '';
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = 'foo';

		t3lib_extMgm::extPath('foo');
	}

	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * Generates a basic TCA for a given table.
	 *
	 * @param string $table name of the table, must not be empty
	 * @return array generated TCA for the given table, will not be empty
	 */
	private function generateTCAForTable($table) {
		$tca = array();
		$tca[$table] = array();
		$tca[$table]['columns'] = array(
			'fieldA' => array(),
			'fieldC' => array(),
		);
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

	/**
	 * Returns the fixtures path for this testcase relative to PATH_site.
	 *
	 * @return string the fixtures path for this testcase, will not be empty
	 */
	private function determineFixturesPath() {
		return t3lib_div::makeInstance('Tx_Phpunit_Service_TestFinder')
			->getRelativeCoreTestsPath() . 't3lib/fixtures/';
	}


	/////////////////////////////////////////////
	// Tests concerning getExtensionKeyByPrefix
	/////////////////////////////////////////////

	/**
	 * @test
	 * @see t3lib_extMgm::getExtensionKeyByPrefix
	 */
	public function getExtensionKeyByPrefixForLoadedExtensionWithUnderscoresReturnsExtensionKey() {
		t3lib_extMgm::clearExtensionKeyMap();

		$uniqueSuffix = uniqid('test');
		$extensionKey = 'tt_news' . $uniqueSuffix;
		$extensionPrefix = 'tx_ttnews' . $uniqueSuffix;

		$GLOBALS['TYPO3_LOADED_EXT'][$extensionKey] = array();

		$this->assertEquals(
			$extensionKey,
			t3lib_extMgm::getExtensionKeyByPrefix($extensionPrefix)
		);
	}

	/**
	 * @test
	 * @see t3lib_extMgm::getExtensionKeyByPrefix
	 */
	public function getExtensionKeyByPrefixForLoadedExtensionWithoutUnderscoresReturnsExtensionKey() {
		t3lib_extMgm::clearExtensionKeyMap();

		$uniqueSuffix = uniqid('test');
		$extensionKey = 'kickstarter' . $uniqueSuffix;
		$extensionPrefix = 'tx_kickstarter' . $uniqueSuffix;

		$GLOBALS['TYPO3_LOADED_EXT'][$extensionKey] = array();

		$this->assertEquals(
			$extensionKey,
			t3lib_extMgm::getExtensionKeyByPrefix($extensionPrefix)
		);
	}

	/**
	 * @test
	 * @see t3lib_extMgm::getExtensionKeyByPrefix
	 */
	public function getExtensionKeyByPrefixForNotLoadedExtensionReturnsFalse(){
		t3lib_extMgm::clearExtensionKeyMap();

		$uniqueSuffix = uniqid('test');
		$extensionKey = 'unloadedextension' . $uniqueSuffix;
		$extensionPrefix = 'tx_unloadedextension' . $uniqueSuffix;

		$this->assertFalse(
			t3lib_extMgm::getExtensionKeyByPrefix($extensionPrefix)
		);
	}


	//////////////////////////////////////
	// Tests concerning addToAllTCAtypes
	//////////////////////////////////////

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
	 * Test wheter replacing other TCA fields works as promissed
	 * @test
	 * @see t3lib_extMgm::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToTCATypeAndReplaceExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		$typesBefore = $GLOBALS['TCA'][$table]['types'];

		t3lib_extMgm::addToAllTCAtypes($table, 'fieldZ', '', 'replace:fieldX');

		$this->assertEquals(
			$typesBefore,
			$GLOBALS['TCA'][$table]['types'],
			'It\'s wrong that the "types" array changes here - the replaced field is only on palettes'
		);

			// unchanged because the palette is not used
		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']
		);
			// unchanged because the palette is not used
		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']
		);

		$this->assertEquals(
			'fieldZ, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']
		);

		$this->assertEquals(
			'fieldZ, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']
		);
	}



	///////////////////////////////////////////////////
	// Tests concerning addFieldsToAllPalettesOfField
	///////////////////////////////////////////////////

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
	 * Tests whether fields can be added to a palette after a not existing elements.
	 * @test
	 * @see t3lib_extMgm::addFieldsToPalette()
	 */
	public function canAddFieldsToPaletteAfterNotExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:' . uniqid('notExisting'));

		$this->assertEquals(
			'fieldX, fieldY, newA, newB',
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
	 * Tests whether fields can be added to all palettes of a regular field after existing ones.
	 * @test
	 * @see t3lib_extMgm::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addFieldsToAllPalettesOfField($table, 'fieldC', 'newA, newA, newB, fieldX', 'after:fieldX');

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
	 * Tests whether fields can be added to all palettes of a regular field after a not existing field.
	 * @test
	 * @see t3lib_extMgm::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldAfterNotExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addFieldsToAllPalettesOfField($table, 'fieldC', 'newA, newA, newB, fieldX', 'after:' . uniqid('notExisting'));

		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']
		);
		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']
		);
		$this->assertEquals(
			'fieldX, fieldY, newA, newB', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']
		);
		$this->assertEquals(
			'fieldX, fieldY, newA, newB', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']
		);
	}

	/**
	 * Tests whether fields are added to a new palette that did not exist before.
	 * @test
	 * @see t3lib_extMgm::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldWithoutPaletteExistingBefore() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);

		t3lib_extMgm::addFieldsToAllPalettesOfField($table, 'fieldA', 'newA, newA, newB, fieldX');

		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']
		);
		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']
		);
		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']
		);
		$this->assertEquals(
			'fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']
		);
		$this->assertEquals(
			'newA, newB, fieldX', $GLOBALS['TCA'][$table]['palettes']['generatedFor-fieldA']['showitem']
		);
	}


	/////////////////////////////////////////
	// Tests concerning addTcaSelectItem
	/////////////////////////////////////////

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfTableIsNotOfTypeString() {
		t3lib_extMgm::addTcaSelectItem(array(), 'foo', array());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfFieldIsNotOfTypeString() {
		t3lib_extMgm::addTcaSelectItem('foo', array(), array());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfRelativeToFieldIsNotOfTypeString() {
		t3lib_extMgm::addTcaSelectItem('foo', 'bar', array(), array());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfRelativePositionIsNotOfTypeString() {
		t3lib_extMgm::addTcaSelectItem('foo', 'bar', array(), 'foo', array());
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfRelativePositionIsNotOneOfValidKeywords() {
		t3lib_extMgm::addTcaSelectItem('foo', 'bar', array(), 'foo', 'not allowed keyword');
	}

	/**
	 * @test
	 * @expectedException RuntimeException
	 */
	public function addTcaSelectItemThrowsExceptionIfFieldIsNotFoundInTca() {
		$GLOBALS['TCA'] = array();
		t3lib_extMgm::addTcaSelectItem('foo', 'bar', array());
	}

	/**
	 * Data provider for addTcaSelectItemInsertsItemAtSpecifiedPosition
	 */
	public function addTcaSelectItemDataProvider() {
			// Every array splits into:
			// - relativeToField
			// - relativePosition
			// - expectedResultArray
		return array(
			'add at end of array' => array(
				'',
				'',
				array(
					0 => array('firstElement'),
					1 => array('matchMe'),
					2 => array('thirdElement'),
					3 => array('insertedElement'),
				),
			),
			'replace element' => array(
				'matchMe',
				'replace',
				array(
					0 => array('firstElement'),
					1 => array('insertedElement'),
					2 => array('thirdElement'),
				),
			),
			'add element after' => array(
				'matchMe',
				'after',
				array(
					0 => array('firstElement'),
					1 => array('matchMe'),
					2 => array('insertedElement'),
					3 => array('thirdElement'),
				),
			),
			'add element before' => array(
				'matchMe',
				'before',
				array(
					0 => array('firstElement'),
					1 => array('insertedElement'),
					2 => array('matchMe'),
					3 => array('thirdElement'),
				),
			),
			'add at end if relative position was not found' => array(
				'notExistingItem',
				'after',
				array(
					0 => array('firstElement'),
					1 => array('matchMe'),
					2 => array('thirdElement'),
					3 => array('insertedElement'),
				),
			),
		);
	}

	/**
	 * @test
	 * @dataProvider addTcaSelectItemDataProvider
	 */
	public function addTcaSelectItemInsertsItemAtSpecifiedPosition($relativeToField, $relativePosition, $expectedResultArray) {
		$GLOBALS['TCA'] = array(
			'testTable' => array(
				'columns' => array(
					'testField' => array(
						'config' => array(
							'items' => array(
								'0' => array('firstElement'),
								'1' => array('matchMe'),
								2 => array('thirdElement'),
							),
						),
					),
				),
			),
		);
		t3lib_extMgm::addTcaSelectItem('testTable', 'testField', array('insertedElement'), $relativeToField, $relativePosition);
		$this->assertEquals($expectedResultArray, $GLOBALS['TCA']['testTable']['columns']['testField']['config']['items']);
	}


	/////////////////////////////////////////
	// Tests concerning getExtensionVersion
	/////////////////////////////////////////

	/**
	 * Data provider for negative getExtensionVersion() tests.
	 *
	 * @return array
	 */
	public function getExtensionVersionFaultyDataProvider() {
		return array(
			array(''),
			array(0),
			array(new stdClass()),
			array(TRUE),
		);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @dataProvider getExtensionVersionFaultyDataProvider
	 */
	public function getExtensionVersionForFaultyExtensionKeyThrowsException($key) {
		t3lib_extMgm::getExtensionVersion($key);
	}

	/**
	 * @test
	 */
	public function getExtensionVersionForNotLoadedExtensionReturnsEmptyString() {
		t3lib_extMgm::clearExtensionKeyMap();

		$uniqueSuffix = uniqid('test');
		$extensionKey = 'unloadedextension' . $uniqueSuffix;

		$this->assertEquals(
			'',
			t3lib_extMgm::getExtensionVersion($extensionKey)
		);
	}

	/**
	 * @test
	 */
	public function getExtensionVersionForLoadedExtensionReturnsExtensionVersion() {
		t3lib_extMgm::clearExtensionKeyMap();

		$uniqueSuffix = uniqid('test');
		$extensionKey = 'unloadedextension' . $uniqueSuffix;

		$GLOBALS['TYPO3_LOADED_EXT'][$extensionKey] = array(
			'siteRelPath' => $this->determineFixturesPath(),
		);
		$this->assertEquals(
			'1.2.3',
			t3lib_extMgm::getExtensionVersion($extensionKey)
		);
	}

	/**
	 * @test
	 */
	public function getEnabledExtensionListConsidersRequiredExtensions() {
		$testrequiRedExtension = uniqid('test');
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = $testrequiRedExtension;

		$extensions = explode(',', t3lib_extMgm::getEnabledExtensionList());
		$this->assertTrue(in_array($testrequiRedExtension, $extensions));
	}

	/**
	 * @test
	 */
	public function getEnabledExtensionListConsidersRequiredAndIgnoredExtensions() {
		$testRequiredExtension = uniqid('test');
		$testIgnoredExtension = uniqid('test');
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = $testRequiredExtension . ',' . $testIgnoredExtension;
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['ignoredExt'] = $testIgnoredExtension;

		$extensions = explode(',', t3lib_extMgm::getEnabledExtensionList());
		$this->assertTrue(in_array($testRequiredExtension, $extensions));
		$this->assertFalse(in_array($testIgnoredExtension, $extensions));
	}
}
?>