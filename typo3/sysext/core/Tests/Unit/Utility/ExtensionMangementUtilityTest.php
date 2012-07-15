<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Oliver Hader <oliver@typo3.org>
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
 * Testcase for \TYPO3\CMS\Core\Utility\ExtensionManagementUtility
 *
 * @author Oliver Hader <oliver@typo3.org>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ExtensionManagementUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * phpunit still needs some globals that are
	 * reconstructed before $backupGlobals is handled. Those
	 * important globals are handled in tearDown() directly.
	 *
	 * @var array
	 */
	protected $globals = array();

	/**
	 * Absolute path to files that must be removed
	 * after a test - handled in tearDown
	 *
	 * @TODO : Check if the tests can use vfs:// instead
	 */
	protected $testFilesToDelete = array();

	/**
	 * @var array Register of temporary extensions in typo3temp
	 */
	protected $fakedExtensions = array();

	public function setUp() {
		$this->createAccessibleProxyClass();
		$this->globals = array(
			'TYPO3_LOADED_EXT' => serialize($GLOBALS['TYPO3_LOADED_EXT'])
		);
		$this->testFilesToDelete = array();
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
	}

	public function tearDown() {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::clearExtensionKeyMap();
		foreach ($this->globals as $key => $value) {
			$GLOBALS[$key] = unserialize($value);
		}
		foreach ($this->testFilesToDelete as $absoluteFileName) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($absoluteFileName);
		}
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
		foreach ($this->fakedExtensions as $extension) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::rmdir(PATH_site . 'typo3temp/' . $extension, TRUE);
		}
	}

	/**
	 * Create a subclass with protected methods made public
	 *
	 * @return void
	 * @TODO: Move this to a fixture file
	 */
	protected function createAccessibleProxyClass() {
		$namespace = 'TYPO3\\CMS\\Core\\Utility';
		$className = 'ExtensionManagementUtilityAccessibleProxy';
		if (!class_exists($namespace . '\\' . $className, FALSE)) {
			eval(
				'namespace ' . $namespace . ';' .
				'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
				'  public static function createTypo3LoadedExtensionInformationArray() {' .
				'    return parent::createTypo3LoadedExtensionInformationArray();' .
				'  }' .
				'  public static function getTypo3LoadedExtensionInformationCacheIdentifier() {' .
				'    return parent::getTypo3LoadedExtensionInformationCacheIdentifier();' .
				'  }' .
				'  public static function getExtLocalconfCacheIdentifier() {' .
				'    return parent::getExtLocalconfCacheIdentifier();' .
				'  }' .
				'  public static function loadSingleExtLocalconfFiles() {' .
				'    return parent::loadSingleExtLocalconfFiles();' .
				'  }' .
				'  public static function getBaseTcaCacheIdentifier() {' .
				'    return parent::getBaseTcaCacheIdentifier();' .
				'  }' .
				'  public static function resetExtTablesWasReadFromCacheOnceBoolean() {' .
				'    self::$extTablesWasReadFromCacheOnce = FALSE;' .
				'  }' .
				'  public static function createExtLocalconfCacheEntry() {' .
				'    return parent::createExtLocalconfCacheEntry();' .
				'  }' .
				'  public static function createExtTablesCacheEntry() {' .
				'    return parent::createExtTablesCacheEntry();' .
				'  }' .
				'  public static function getExtTablesCacheIdentifier() {' .
				'    return parent::getExtTablesCacheIdentifier();' .
				'  }' .
				'}'
			);
		}
	}

	///////////////////////////////
	// Tests concerning isLoaded
	///////////////////////////////
	/**
	 * @test
	 */
	public function isLoadedReturnsTrueIfExtensionIsLoaded() {
		$this->assertTrue(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms'));
	}

	/**
	 * @test
	 */
	public function isLoadedReturnsFalseIfExtensionIsNotLoadedAndExitIsDisabled() {
		$this->assertFalse(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(uniqid('foobar'), FALSE));
	}

	/**
	 * @test
	 * @expectedException \BadFunctionCallException
	 */
	public function isLoadedThrowsExceptionIfExtensionIsNotLoaded() {
		$this->assertFalse(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(uniqid('foobar'), TRUE));
	}

	///////////////////////////////
	// Tests concerning extPath
	///////////////////////////////
	/**
	 * @test
	 * @expectedException \BadFunctionCallException
	 */
	public function extPathThrowsExceptionIfExtensionIsNotLoaded() {
		$GLOBALS['TYPO3_LOADED_EXT']['foo'] = array();
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('bar');
	}

	/**
	 * @test
	 */
	public function extPathAppendsScriptNameToPath() {
		$GLOBALS['TYPO3_LOADED_EXT']['foo']['siteRelPath'] = 'foo/';
		$this->assertSame(PATH_site . 'foo/bar.txt', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('foo', 'bar.txt'));
	}

	/**
	 * @test
	 * @expectedException \BadFunctionCallException
	 */
	public function extPathThrowsExceptionIfExtensionIsNotLoadedAndTypo3LoadedExtensionsIsEmpty() {
		unset($GLOBALS['TYPO3_LOADED_EXT']);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = '';
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = '';
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('bar');
	}

	/**
	 * @test
	 */
	public function extPathSearchesForPathOfExtensionInRequiredExtensionList() {
		$this->setExpectedException('BadFunctionCallException', '', 1294430951);
		unset($GLOBALS['TYPO3_LOADED_EXT']);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = 'foo';
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = '';
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('foo');
	}

	/**
	 * @test
	 */
	public function extPathSearchesForPathOfExtensionInExtList() {
		$this->setExpectedException('BadFunctionCallException', '', 1294430951);
		unset($GLOBALS['TYPO3_LOADED_EXT']);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = '';
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extListArray'] = array('foo');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('foo');
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
			'fieldC' => array()
		);
		$tca[$table]['types'] = array(
			'typeA' => array('showitem' => 'fieldA, fieldB, fieldC;labelC;paletteC;specialC, fieldD'),
			'typeB' => array('showitem' => 'fieldA, fieldB, fieldC;labelC;paletteC;specialC, fieldD'),
			'typeC' => array('showitem' => 'fieldC;;paletteD')
		);
		$tca[$table]['palettes'] = array(
			'paletteA' => array('showitem' => 'fieldX, fieldY'),
			'paletteB' => array('showitem' => 'fieldX, fieldY'),
			'paletteC' => array('showitem' => 'fieldX, fieldY'),
			'paletteD' => array('showitem' => 'fieldX, fieldY')
		);
		return $tca;
	}

	/////////////////////////////////////////////
	// Tests concerning getExtensionKeyByPrefix
	/////////////////////////////////////////////
	/**
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionKeyByPrefix
	 */
	public function getExtensionKeyByPrefixForLoadedExtensionWithUnderscoresReturnsExtensionKey() {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::clearExtensionKeyMap();
		$uniqueSuffix = uniqid('test');
		$extensionKey = 'tt_news' . $uniqueSuffix;
		$extensionPrefix = 'tx_ttnews' . $uniqueSuffix;
		$GLOBALS['TYPO3_LOADED_EXT'][$extensionKey] = array();
		$this->assertEquals($extensionKey, \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionKeyByPrefix($extensionPrefix));
	}

	/**
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionKeyByPrefix
	 */
	public function getExtensionKeyByPrefixForLoadedExtensionWithoutUnderscoresReturnsExtensionKey() {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::clearExtensionKeyMap();
		$uniqueSuffix = uniqid('test');
		$extensionKey = 'kickstarter' . $uniqueSuffix;
		$extensionPrefix = 'tx_kickstarter' . $uniqueSuffix;
		$GLOBALS['TYPO3_LOADED_EXT'][$extensionKey] = array();
		$this->assertEquals($extensionKey, \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionKeyByPrefix($extensionPrefix));
	}

	/**
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionKeyByPrefix
	 */
	public function getExtensionKeyByPrefixForNotLoadedExtensionReturnsFalse() {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::clearExtensionKeyMap();
		$uniqueSuffix = uniqid('test');
		$extensionKey = 'unloadedextension' . $uniqueSuffix;
		$extensionPrefix = 'tx_unloadedextension' . $uniqueSuffix;
		$this->assertFalse(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionKeyByPrefix($extensionPrefix));
	}

	//////////////////////////////////////
	// Tests concerning addToAllTCAtypes
	//////////////////////////////////////
	/**
	 * Tests whether fields can be add to all TCA types and duplicate fields are considered.
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes()
	 */
	public function canAddFieldsToAllTCATypesBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'before:fieldD');
		// Checking typeA:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
		// Checking typeB:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
	}

	/**
	 * Tests whether fields can be add to all TCA types and duplicate fields are considered.
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes()
	 */
	public function canAddFieldsToAllTCATypesAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:fieldC');
		// Checking typeA:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
		// Checking typeB:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
	}

	/**
	 * Tests whether fields can be add to a TCA type before existing ones
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes()
	 */
	public function canAddFieldsToTCATypeBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', 'typeA', 'before:fieldD');
		// Checking typeA:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
		// Checking typeB:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, fieldD', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
	}

	/**
	 * Tests whether fields can be add to a TCA type after existing ones
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes()
	 */
	public function canAddFieldsToTCATypeAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', 'typeA', 'after:fieldC');
		// Checking typeA:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
		// Checking typeB:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, fieldD', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
	}

	/**
	 * Test wheter replacing other TCA fields works as promissed
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToTCATypeAndReplaceExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		$typesBefore = $GLOBALS['TCA'][$table]['types'];
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($table, 'fieldZ', '', 'replace:fieldX');
		$this->assertEquals($typesBefore, $GLOBALS['TCA'][$table]['types'], 'It\'s wrong that the "types" array changes here - the replaced field is only on palettes');
		// unchanged because the palette is not used
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
		// unchanged because the palette is not used
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
		$this->assertEquals('fieldZ, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
		$this->assertEquals('fieldZ, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
	}

	///////////////////////////////////////////////////
	// Tests concerning addFieldsToAllPalettesOfField
	///////////////////////////////////////////////////
	/**
	 * Tests whether fields can be added to a palette before existing elements.
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette()
	 */
	public function canAddFieldsToPaletteBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'before:fieldY');
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
	}

	/**
	 * Tests whether fields can be added to a palette after existing elements.
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette()
	 */
	public function canAddFieldsToPaletteAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:fieldX');
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
	}

	/**
	 * Tests whether fields can be added to a palette after a not existing elements.
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette()
	 */
	public function canAddFieldsToPaletteAfterNotExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:' . uniqid('notExisting'));
		$this->assertEquals('fieldX, fieldY, newA, newB', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
	}

	/**
	 * Tests whether fields can be added to all palettes of a regular field before existing ones.
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField($table, 'fieldC', 'newA, newA, newB, fieldX', 'before:fieldY');
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
	}

	/**
	 * Tests whether fields can be added to all palettes of a regular field after existing ones.
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField($table, 'fieldC', 'newA, newA, newB, fieldX', 'after:fieldX');
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
	}

	/**
	 * Tests whether fields can be added to all palettes of a regular field after a not existing field.
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldAfterNotExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField($table, 'fieldC', 'newA, newA, newB, fieldX', 'after:' . uniqid('notExisting'));
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
		$this->assertEquals('fieldX, fieldY, newA, newB', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
		$this->assertEquals('fieldX, fieldY, newA, newB', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
	}

	/**
	 * Tests whether fields are added to a new palette that did not exist before.
	 *
	 * @test
	 * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldWithoutPaletteExistingBefore() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField($table, 'fieldA', 'newA, newA, newB, fieldX');
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
		$this->assertEquals('newA, newB, fieldX', $GLOBALS['TCA'][$table]['palettes']['generatedFor-fieldA']['showitem']);
	}

	/////////////////////////////////////////
	// Tests concerning addTcaSelectItem
	/////////////////////////////////////////
	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfTableIsNotOfTypeString() {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(array(), 'foo', array());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfFieldIsNotOfTypeString() {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('foo', array(), array());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfRelativeToFieldIsNotOfTypeString() {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array(), array());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfRelativePositionIsNotOfTypeString() {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array(), 'foo', array());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfRelativePositionIsNotOneOfValidKeywords() {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array(), 'foo', 'not allowed keyword');
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function addTcaSelectItemThrowsExceptionIfFieldIsNotFoundInTca() {
		$GLOBALS['TCA'] = array();
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array());
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
					3 => array('insertedElement')
				)
			),
			'replace element' => array(
				'matchMe',
				'replace',
				array(
					0 => array('firstElement'),
					1 => array('insertedElement'),
					2 => array('thirdElement')
				)
			),
			'add element after' => array(
				'matchMe',
				'after',
				array(
					0 => array('firstElement'),
					1 => array('matchMe'),
					2 => array('insertedElement'),
					3 => array('thirdElement')
				)
			),
			'add element before' => array(
				'matchMe',
				'before',
				array(
					0 => array('firstElement'),
					1 => array('insertedElement'),
					2 => array('matchMe'),
					3 => array('thirdElement')
				)
			),
			'add at end if relative position was not found' => array(
				'notExistingItem',
				'after',
				array(
					0 => array('firstElement'),
					1 => array('matchMe'),
					2 => array('thirdElement'),
					3 => array('insertedElement')
				)
			)
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
								2 => array('thirdElement')
							)
						)
					)
				)
			)
		);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('testTable', 'testField', array('insertedElement'), $relativeToField, $relativePosition);
		$this->assertEquals($expectedResultArray, $GLOBALS['TCA']['testTable']['columns']['testField']['config']['items']);
	}

	/////////////////////////////////////////
	// Tests concerning loadTypo3LoadedExtensionInformation
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function loadTypo3LoadedExtensionInformationDoesNotCallCacheIfCachingIsDenied() {
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->never())->method('getCache');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadTypo3LoadedExtensionInformation(FALSE);
	}

	/**
	 * @test
	 */
	public function loadTypo3LoadedExtensionInformationRequiresCacheFileIfExistsAndCachingIsAllowed() {
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(TRUE));
		$mockCache->expects($this->once())->method('requireOnce');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadTypo3LoadedExtensionInformation(TRUE);
	}

	/**
	 * @test
	 */
	public function loadTypo3LoadedExtensionInformationSetsNewCacheEntryIfCacheFileDoesNotExistAndCachingIsAllowed() {
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));
		$mockCache->expects($this->once())->method('set');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadTypo3LoadedExtensionInformation(TRUE);
	}

	/**
	 * @test
	 */
	public function loadTypo3LoadedExtensionInformationSetsNewCacheEntryWithNoTags() {
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));
		$mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->equalTo(array()));
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadTypo3LoadedExtensionInformation(TRUE);
	}

	/////////////////////////////////////////
	// Tests concerning createTypo3LoadedExtensionInformationArray
	/////////////////////////////////////////
	/**
	 * Data provider for createTypo3LoadedExtensionInformationArrayReturnsExpectedInformationForCmsExtension
	 *
	 * @return array
	 */
	public function createTypo3LoadedExtensionInformationArrayReturnsExpectedInformationForCmsExtensionDataProvider() {
		return array(
			'System extension' => array('type', 'S'),
			'Site relative path' => array('siteRelPath', 'typo3/sysext/cms/'),
			'Typo3 relative path' => array('typo3RelPath', 'sysext/cms/'),
			'Path ext_localconf.php' => array('ext_localconf.php', '/typo3/sysext/cms/ext_localconf.php'),
			'Path ext_tables.php' => array('ext_tables.php', '/typo3/sysext/cms/ext_tables.php'),
			'Path ext_tablps.sql' => array('ext_tables.sql', '/typo3/sysext/cms/ext_tables.sql')
		);
	}

	/**
	 * @param string $arrayKeyToTest
	 * @param string $expectedContent
	 * @test
	 * @dataProvider createTypo3LoadedExtensionInformationArrayReturnsExpectedInformationForCmsExtensionDataProvider
	 */
	public function createTypo3LoadedExtensionInformationArrayReturnsExpectedInformationForCmsExtension($arrayKeyToTest, $expectedContent) {
		$actualArray = \TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::createTypo3LoadedExtensionInformationArray();
		$this->assertStringEndsWith($expectedContent, $actualArray['cms'][$arrayKeyToTest]);
	}

	/////////////////////////////////////////
	// Tests concerning getTypo3LoadedExtensionInformationCacheIdentifier
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function getTypo3LoadedExtensionInformationCacheIdentifierCreatesSha1WithFourtyCharactersAndPrefix() {
		$prefix = 'loaded_extensions_';
		$identifier = \TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::getTypo3LoadedExtensionInformationCacheIdentifier();
		$this->assertStringStartsWith($prefix, $identifier);
		$sha1 = str_replace($prefix, '', $identifier);
		$this->assertEquals(40, strlen($sha1));
	}

	/////////////////////////////////////////
	// Tests concerning loadExtLocalconf
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function loadExtLocalconfDoesNotReadFromCacheIfCachingIsDenied() {
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->never())->method('getCache');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadExtLocalconf(FALSE);
	}

	/**
	 * @test
	 */
	public function loadExtLocalconfRequiresCacheFileIfExistsAndCachingIsAllowed() {
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(TRUE));
		$mockCache->expects($this->once())->method('requireOnce');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadExtLocalconf(TRUE);
	}

	/////////////////////////////////////////
	// Tests concerning loadSingleExtLocalconfFiles
	/////////////////////////////////////////
	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function loadSingleExtLocalconfFilesRequiresExtLocalconfFileRegisteredInGlobalTypo3LoadedExt() {
		$extensionName = uniqid('foo');
		$extLocalconfLocation = PATH_site . 'typo3temp/' . uniqid('test_ext_localconf') . '.php';
		$this->testFilesToDelete[] = $extLocalconfLocation;
		file_put_contents($extLocalconfLocation, "<?php\n\nthrow new RuntimeException('', 1340559079);\n\n?>");
		$GLOBALS['TYPO3_LOADED_EXT'] = array(
			$extensionName => array(
				'ext_localconf.php' => $extLocalconfLocation
			)
		);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::loadSingleExtLocalconfFiles();
	}

	/////////////////////////////////////////
	// Tests concerning createExtLocalconfCacheEntry
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function createExtLocalconfCacheEntryWritesCacheEntryWithContentOfLoadedExtensionExtLocalconf() {
		$extensionName = uniqid('foo');
		$extLocalconfLocation = PATH_site . 'typo3temp/' . uniqid('test_ext_localconf') . '.php';
		$this->testFilesToDelete[] = $extLocalconfLocation;
		$uniqueStringInLocalconf = uniqid('foo');
		file_put_contents($extLocalconfLocation, "<?php\n\n" . $uniqueStringInLocalconf . "\n\n?>");
		$GLOBALS['TYPO3_LOADED_EXT'] = array(
			$extensionName => array(
				'ext_localconf.php' => $extLocalconfLocation
			)
		);
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->once())->method('set')->with($this->anything(), $this->stringContains($uniqueStringInLocalconf), $this->anything());
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry();
	}

	/**
	 * @test
	 */
	public function createExtLocalconfCacheEntryWritesCacheEntryWithExtensionContentOnlyIfExtLocalconfExists() {
		$extensionName = uniqid('foo');
		$GLOBALS['TYPO3_LOADED_EXT'] = array(
			$extensionName => array(),
		);
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->once())
			->method('set')
			->with($this->anything(), $this->logicalNot($this->stringContains($extensionName)), $this->anything());
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry();
	}

	/**
	 * @test
	 */
	public function createExtLocalconfCacheEntryWritesCacheEntryWithNoTags() {
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->equalTo(array()));
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry();
	}

	/////////////////////////////////////////
	// Tests concerning getExtLocalconfCacheIdentifier
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function getExtLocalconfCacheIdentifierCreatesSha1WithFourtyCharactersAndPrefix() {
		$prefix = 'ext_localconf_';
		$identifier = \TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::getExtLocalconfCacheIdentifier();
		$this->assertStringStartsWith($prefix, $identifier);
		$sha1 = str_replace($prefix, '', $identifier);
		$this->assertEquals(40, strlen($sha1));
	}

	/////////////////////////////////////////
	// Tests concerning loadBaseTca
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function loadBaseTcaDoesNotReadFromCacheIfCachingIsDenied() {
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->never())->method('getCache');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadBaseTca(FALSE);
	}

	/**
	 * @test
	 */
	public function loadBaseTcaRequiresCacheFileIfExistsAndCachingIsAllowed() {
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(TRUE));
		$mockCache->expects($this->once())->method('requireOnce');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadBaseTca(TRUE);
	}

	/**
	 * @test
	 */
	public function loadBaseTcaCreatesCacheFileWithContentOfAnExtensionsConfigurationTcaPhpFile() {
		$extensionName = uniqid('test_baseTca_');
		$this->fakedExtensions[] = $extensionName;
		$absoluteExtPath = PATH_site . 'typo3temp/' . $extensionName . '/';
		$relativeExtPath = 'typo3temp/' . $extensionName . '/';
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($absoluteExtPath);
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($absoluteExtPath . 'Configuration/');
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($absoluteExtPath . 'Configuration/TCA/');
		$GLOBALS['TYPO3_LOADED_EXT'][$extensionName] = array(
			'siteRelPath' => $relativeExtPath
		);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extListArray'][] = $extensionName;
		$uniqueTableName = uniqid('table_name_');
		$uniqueStringInTableConfiguration = uniqid('table_configuration_');
		$tableConfiguration = '<?php return array(\'foo\' => \'' . $uniqueStringInTableConfiguration . '\'); ?>';
		file_put_contents($absoluteExtPath . 'Configuration/TCA/' . $uniqueTableName . '.php', $tableConfiguration);
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->once())->method('has')->will($this->returnValue(FALSE));
		$mockCache->expects($this->once())->method('set')->with($this->anything(), $this->stringContains($uniqueStringInTableConfiguration), $this->anything());
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadBaseTca(TRUE);
	}

	/**
	 * @test
	 */
	public function loadBaseTcaWritesCacheEntryWithNoTags() {
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->once())->method('has')->will($this->returnValue(FALSE));
		$mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->equalTo(array()));
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadBaseTca();
	}

	/////////////////////////////////////////
	// Tests concerning getBaseTcaCacheIdentifier
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function getBaseTcaCacheIdentifierCreatesSha1WithFourtyCharactersAndPrefix() {
		$prefix = 'tca_base_';
		$identifier = \TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::getBaseTcaCacheIdentifier();
		$this->assertStringStartsWith($prefix, $identifier);
		$sha1 = str_replace($prefix, '', $identifier);
		$this->assertEquals(40, strlen($sha1));
	}

	/////////////////////////////////////////
	// Tests concerning loadExtTables
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function loadExtTablesDoesNotReadFromCacheIfCachingIsDenied() {
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->never())->method('getCache');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadExtLocalconf(FALSE);
	}

	/**
	 * @test
	 */
	public function loadExtTablesRequiresCacheFileIfExistsAndCachingIsAllowed() {
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(TRUE));
		$mockCache->expects($this->once())->method('requireOnce');
		// Reset the internal cache access tracking variable of extMgm
		// This method is only in the ProxyClass!
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::resetExtTablesWasReadFromCacheOnceBoolean();
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadExtTables(TRUE);
	}

	/////////////////////////////////////////
	// Tests concerning createExtTablesCacheEntry
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function createExtTablesCacheEntryWritesCacheEntryWithContentOfLoadedExtensionExtTables() {
		$extensionName = uniqid('foo');
		$extTablesLocation = PATH_site . 'typo3temp/' . uniqid('test_ext_tables') . '.php';
		$this->testFilesToDelete[] = $extTablesLocation;
		$uniqueStringInTables = uniqid('foo');
		file_put_contents($extTablesLocation, "<?php\n\n$uniqueStringInTables\n\n?>");
		$GLOBALS['TYPO3_LOADED_EXT'] = array(
			$extensionName => array(
				'ext_tables.php' => $extTablesLocation
			)
		);
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->once())->method('set')->with($this->anything(), $this->stringContains($uniqueStringInTables), $this->anything());
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::createExtTablesCacheEntry();
	}

	/**
	 * @test
	 */
	public function createExtTablesCacheEntryWritesCacheEntryWithExtensionContentOnlyIfExtTablesExists() {
		$extensionName = uniqid('foo');
		$GLOBALS['TYPO3_LOADED_EXT'] = array(
			$extensionName => array(),
		);
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->once())
			->method('set')
			->with($this->anything(), $this->logicalNot($this->stringContains($extensionName)), $this->anything());
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::createExtTablesCacheEntry();
	}

	/**
	 * @test
	 */
	public function createExtTablesCacheEntryWritesCacheEntryWithNoTags() {
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->equalTo(array()));
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::createExtTablesCacheEntry();
	}

	/////////////////////////////////////////
	// Tests concerning getExtTablesCacheIdentifier
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function getExtTablesCacheIdentifierCreatesSha1WithFourtyCharactersAndPrefix() {
		$prefix = 'ext_tables_';
		$identifier = \TYPO3\CMS\Core\Utility\ExtensionManagementUtilityAccessibleProxy::getExtTablesCacheIdentifier();
		$this->assertStringStartsWith($prefix, $identifier);
		$sha1 = str_replace($prefix, '', $identifier);
		$this->assertEquals(40, strlen($sha1));
	}

	/////////////////////////////////////////
	// Tests concerning removeCacheFiles
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function removeCacheFilesFlushesCache() {
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->once())->method('flush');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::removeCacheFiles();
	}

	/////////////////////////////////////////
	// Tests concerning loadNewTcaColumnsConfigFiles
	/////////////////////////////////////////

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function loadNewTcaColumnsConfigFilesIncludesDefinedDynamicConfigFileIfNoColumnsExist() {
		$GLOBALS['TCA'] = array(
			'test' => array(
				'ctrl' => array(
					'dynamicConfigFile' => __DIR__ . '/Fixtures/RuntimeException.php'
				),
			),
		);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadNewTcaColumnsConfigFiles();
	}

	/**
	 * @test
	 */
	public function loadNewTcaColumnsConfigFilesDoesNotIncludeFileIfColumnsExist() {
		$GLOBALS['TCA'] = array(
			'test' => array(
				'ctrl' => array(
					'dynamicConfigFile' => __DIR__ . '/Fixtures/RuntimeException.php'
				),
				'columns' => array(
					'foo' => 'bar',
				),
			),
		);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadNewTcaColumnsConfigFiles();
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
			array(new \stdClass()),
			array(TRUE)
		);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @dataProvider getExtensionVersionFaultyDataProvider
	 */
	public function getExtensionVersionForFaultyExtensionKeyThrowsException($key) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion($key);
	}

	/**
	 * @test
	 */
	public function getExtensionVersionForNotLoadedExtensionReturnsEmptyString() {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::clearExtensionKeyMap();
		$uniqueSuffix = uniqid('test');
		$extensionKey = 'unloadedextension' . $uniqueSuffix;
		$this->assertEquals('', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion($extensionKey));
	}

	/**
	 * @test
	 */
	public function getExtensionVersionForLoadedExtensionReturnsExtensionVersion() {
		$namespace = 'TYPO3\\CMS\\Core\\Utility';
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . $namespace .';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
			'  public static function isLoaded() {' .
			'    return TRUE;' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::clearExtensionKeyMap();
		$uniqueSuffix = uniqid('test');
		$extensionKey = 'unloadedextension' . $uniqueSuffix;
		$GLOBALS['TYPO3_LOADED_EXT'][$extensionKey] = array(
			'siteRelPath' => 'typo3/sysext/core/Tests/Unit/Utility/Fixtures/',
		);
		$this->assertEquals('1.2.3', $className::getExtensionVersion($extensionKey));
	}

	/////////////////////////////////////////
	// Tests concerning getLoadedExtensionListArray
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function getLoadedExtensionListArrayConsidersExtListAsString() {
		unset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extListArray']);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = 'foo,bar';
		$this->assertEquals(
			array('foo', 'bar'),
			array_intersect(array('foo', 'bar'), \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray())
		);
	}

	/**
	 * @test
	 */
	public function getLoadedExtensionListArrayConsidersExtListAsArray() {
		$extList = array('foo', 'bar');
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extListArray'] = $extList;
		$this->assertEquals(
			$extList,
			array_intersect($extList, \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray())
		);
	}

	/**
	 * @test
	 */
	public function getLoadedExtensionListArrayConsidersRequiredExtensions() {
		$namespace = 'TYPO3\\CMS\\Core\\Utility';
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
			'  public static function getRequiredExtensionListArray() {' .
			'    return array(\'baz\');' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extListArray'] = array();
		$this->assertEquals(array('baz'), $className::getLoadedExtensionListArray());
	}

	/**
	 * @test
	 */
	public function getLoadedExtensionListArrayReturnsUniqueList() {
		$namespace = 'TYPO3\\CMS\\Core\\Utility';
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
			'  public static function getRequiredExtensionListArray() {' .
			'    return array(\'bar\');' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extListArray'] = array('foo', 'bar', 'foo');
		$this->assertSame(array('bar', 'foo'), $className::getLoadedExtensionListArray());
	}

	/////////////////////////////////////////
	// Tests concerning getRequiredExtensionListArray
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function getRequiredExtensionListArrayContainsAdditionalRequiredExtensionsAsString() {
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = 'foo,bar';
		$this->assertEquals(
			array('foo', 'bar'),
			array_intersect(array('foo', 'bar'), \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray())
		);
	}

	/**
	 * @test
	 */
	public function getRequiredExtensionListArrayContainsAdditionalRequiredExtensionsAsArray() {
		$requiredExtList = array('foo', 'bar');
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = $requiredExtList;
		$this->assertEquals(
			$requiredExtList,
			array_intersect($requiredExtList, \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray())
		);
	}

	/**
	 * @test
	 */
	public function getRequiredExtensionListArrayReturnsUniqueList() {
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = 'foo,bar,foo';
		$this->assertEquals(
			array('foo', 'bar'),
			array_intersect(array('foo', 'bar'), \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray())
		);
	}

	/////////////////////////////////////////
	// Tests concerning loadExtension
	/////////////////////////////////////////
	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function loadExtensionThrowsExceptionIfExtensionIsLoaded() {
		$namespace = 'TYPO3\\CMS\\Core\\Utility';
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
			'  public static function isLoaded() {' .
			'    return TRUE;' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$className::loadExtension('test');
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function loadExtensionAddsExtensionToExtList() {
		if (!file_exists((PATH_typo3conf . 'LocalConfiguration.php'))) {
			$this->markTestSkipped('Test is not available until update wizard to transform localconf.php was run.');
		}
		$extensionKeyToLoad = uniqid('loadMe');
		$namespace = 'TYPO3\\CMS\\Core\\Utility';
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
			'  public static function writeNewExtensionList($extList) {' .
			'    if (in_array(' . $extensionKeyToLoad . ', $extList)) {' .
			'      throw new \\RuntimeException(\'test\');' .
			'    }' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$className::loadExtension($extensionKeyToLoad);
	}

	/////////////////////////////////////////
	// Tests concerning unloadExtension
	/////////////////////////////////////////
	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function unloadExtensionThrowsExceptionIfExtensionIsNotLoaded() {
		$namespace = 'TYPO3\\CMS\\Core\\Utility';
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
			'  public static function isLoaded() {' .
			'    return FALSE;' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$className::unloadExtension('test');
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function unloadExtensionThrowsExceptionIfExtensionIsRequired() {
		$extensionKey = uniqid('test');
		$namespace = 'TYPO3\\CMS\\Core\\Utility';
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
			'  public static function isLoaded() {' .
			'    return TRUE;' .
			'  }' .
			'  public static function getRequiredExtensionListArray() {' .
			'    return array(\'' . $extensionKey . '\');' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$className::unloadExtension($extensionKey);
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function unloadExtensionRemovesExtensionFromExtList() {
		if (!file_exists((PATH_typo3conf . 'LocalConfiguration.php'))) {
			$this->markTestSkipped('Test is not available until update wizard to transform localconf.php was run.');
		}
		$extensionKeyToUnload = uniqid('unloadMe');
		$namespace = 'TYPO3\\CMS\\Core\\Utility';
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
			'  public static function isLoaded() {' .
			'    return TRUE;' .
			'  }' .
			'  public static function writeNewExtensionList($extList) {' .
			'    if (!in_array(' . $extensionKeyToUnload . ', $extList)) {' .
			'      throw new \\RuntimeException(\'test\');' .
			'    }' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$className::unloadExtension($extensionKeyToUnload);
	}

	/////////////////////////////////////////
	// Tests concerning makeCategorizable
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function isMakeCategorizableAvailableInRegistryWithDefaultField() {
		$extensionKey = uniqid('extension');
		$tableName = uniqid('table');
		$GLOBALS['TCA'][$tableName] = array(
			'ctrl' => array(),
			'columns' => array()
		);
		$registryMock = $this->getMock('TYPO3\\CMS\\Core\\Category\\CategoryRegistry', array('dummy'));
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Category\\CategoryRegistry', $registryMock);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable($extensionKey, $tableName);
		$registryMock->applyTca();
		$this->assertNotEmpty($GLOBALS['TCA'][$tableName]['columns']['categories']);
	}

	/**
	 * @test
	 */
	public function isMakeCategorizableAvailableInRegistryWithSpecifictField() {
		$extensionKey = uniqid('extension');
		$tableName = uniqid('table');
		$fieldName = uniqid('field');
		$GLOBALS['TCA'][$tableName] = array(
			'ctrl' => array(),
			'columns' => array()
		);
		$registryMock = $this->getMock('TYPO3\\CMS\\Core\\Category\\CategoryRegistry', array('dummy'));
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Category\\CategoryRegistry', $registryMock);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable($extensionKey, $tableName, $fieldName);
		$registryMock->applyTca();
		$this->assertNotEmpty($GLOBALS['TCA'][$tableName]['columns'][$fieldName]);
	}

}
?>
