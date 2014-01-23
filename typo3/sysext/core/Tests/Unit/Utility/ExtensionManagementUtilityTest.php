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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Testcase for ExtensionManagementUtility
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
	 * @var \TYPO3\CMS\Core\Package\PackageManager
	 */
	protected $backUpPackageManager;

	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		$this->createAccessibleProxyClass();
		$this->testFilesToDelete = array();
		$this->backUpPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
	}

	public function tearDown() {
		ExtensionManagementUtility::clearExtensionKeyMap();
		foreach ($this->globals as $key => $value) {
			$GLOBALS[$key] = unserialize($value);
		}
		foreach ($this->testFilesToDelete as $absoluteFileName) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($absoluteFileName);
		}
		if (file_exists(PATH_site . 'typo3temp/test_ext/')) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::rmdir(PATH_site . 'typo3temp/test_ext/', TRUE);
		}
		ExtensionManagementUtilityAccessibleProxy::setPackageManager($this->backUpPackageManager);
		$GLOBALS['TYPO3_LOADED_EXT'] = new \TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray($this->backUpPackageManager);
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
	}

	/**
	 * Create a subclass with protected methods made public
	 *
	 * @return void
	 * @TODO: Move this to a fixture file
	 */
	protected function createAccessibleProxyClass() {
		$className = 'ExtensionManagementUtilityAccessibleProxy';
		if (!class_exists(__NAMESPACE__ . '\\' . $className, FALSE)) {
			eval(
				'namespace ' . __NAMESPACE__ . ';' .
				'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
				'  public static function getPackageManager() {' .
				'    return static::$packageManager;' .
				'  }' .
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

	/**
	 * @param string $packageKey
	 * @param array $packageMethods
	 * @return object
	 */
	protected function createMockPackageManagerWithMockPackage($packageKey, $packageMethods = array('getPackagePath', 'getPackageKey')) {
		$packagePath = PATH_site . 'typo3temp/test_ext/' . $packageKey . '/';
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($packagePath);
		$package = $this->getMockBuilder('TYPO3\\CMS\\Core\\Package\\Package')
				->disableOriginalConstructor()
				->setMethods($packageMethods)
				->getMock();
		$packageManager = $this->getMock(
			'TYPO3\\CMS\\Core\\Package\\PackageManager',
			array('isPackageActive', 'getPackage', 'getActivePackages')
		);
		$package->expects($this->any())
				->method('getPackagePath')
				->will($this->returnValue($packagePath));
		$package->expects($this->any())
				->method('getPackageKey')
				->will($this->returnValue($packageKey));
		$packageManager->expects($this->any())
				->method('isPackageActive')
				->will($this->returnValueMap(array(
					array(NULL, FALSE),
					array($packageKey, TRUE)
				)));
		$packageManager->expects($this->any())
				->method('getPackage')
				->with($this->equalTo($packageKey))
				->will($this->returnValue($package));
		$packageManager->expects($this->any())
				->method('getActivePackages')
				->will($this->returnValue(array($packageKey => $package)));
		return $packageManager;
	}

	///////////////////////////////
	// Tests concerning isLoaded
	///////////////////////////////
	/**
	 * @test
	 */
	public function isLoadedReturnsTrueIfExtensionIsLoaded() {
		$this->assertTrue(ExtensionManagementUtility::isLoaded('cms'));
	}

	/**
	 * @test
	 */
	public function isLoadedReturnsFalseIfExtensionIsNotLoadedAndExitIsDisabled() {
		$this->assertFalse(ExtensionManagementUtility::isLoaded(uniqid('foobar'), FALSE));
	}

	/**
	 * @test
	 * @expectedException \BadFunctionCallException
	 */
	public function isLoadedThrowsExceptionIfExtensionIsNotLoaded() {
		$this->assertFalse(ExtensionManagementUtility::isLoaded(uniqid('foobar'), TRUE));
	}

	///////////////////////////////
	// Tests concerning extPath
	///////////////////////////////
	/**
	 * @test
	 * @expectedException \BadFunctionCallException
	 */
	public function extPathThrowsExceptionIfExtensionIsNotLoaded() {
		$packageManager = $this->getMock('TYPO3\\CMS\\Core\\Package\\PackageManager', array('isPackageActive'));
		$packageManager->expects($this->once())
				->method('isPackageActive')
				->with($this->equalTo('bar'))
				->will($this->returnValue(FALSE));
		ExtensionManagementUtility::setPackageManager($packageManager);
		ExtensionManagementUtility::extPath('bar');
	}

	/**
	 * @test
	 */
	public function extPathAppendsScriptNameToPath() {
		$package = $this->getMockBuilder('TYPO3\\CMS\\Core\\Package\\Package')
				->disableOriginalConstructor()
				->setMethods(array('getPackagePath'))
				->getMock();
		$packageManager = $this->getMock('TYPO3\\CMS\\Core\\Package\\PackageManager', array('isPackageActive', 'getPackage'));
		$package->expects($this->once())
				->method('getPackagePath')
				->will($this->returnValue(PATH_site . 'foo/'));
		$packageManager->expects($this->once())
				->method('isPackageActive')
				->with($this->equalTo('foo'))
				->will($this->returnValue(TRUE));
		$packageManager->expects($this->once())
				->method('getPackage')
				->with('foo')
				->will($this->returnValue($package));
		ExtensionManagementUtility::setPackageManager($packageManager);
		$this->assertSame(PATH_site . 'foo/bar.txt', ExtensionManagementUtility::extPath('foo', 'bar.txt'));
	}

	/**
	 * @test
	 * @expectedException \BadFunctionCallException
	 */
	public function extPathThrowsExceptionIfExtensionIsNotLoadedAndTypo3LoadedExtensionsIsEmpty() {
		unset($GLOBALS['TYPO3_LOADED_EXT']);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = '';
		ExtensionManagementUtility::extPath('bar');
	}

	/**
	 * @test
	 */
	public function extPathSearchesForPathOfExtensionInRequiredExtensionList() {
		$this->setExpectedException('BadFunctionCallException', '', 1365429656);
		unset($GLOBALS['TYPO3_LOADED_EXT']);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = 'foo';
		ExtensionManagementUtility::extPath('foo');
	}

	/**
	 * @test
	 */
	public function extPathSearchesForPathOfExtensionInExtList() {
		$this->setExpectedException('BadFunctionCallException', '', 1365429656);
		unset($GLOBALS['TYPO3_LOADED_EXT']);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'] = '';
		ExtensionManagementUtility::extPath('foo');
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

	/**
	 * Data provider for getClassNamePrefixForExtensionKey.
	 *
	 * @return array
	 */
	public function extensionKeyDataProvider() {
		return array(
			'Without underscores' => array(
				'testkey',
				'tx_testkey'
			),
			'With underscores' => array(
				'this_is_a_test_extension',
				'tx_thisisatestextension'
			),
			'With user prefix and without underscores' => array(
				'user_testkey',
				'user_testkey'
			),
			'With user prefix and with underscores' => array(
				'user_test_key',
				'user_testkey'
			),
		);
	}

	/**
	 * @test
	 * @param string $extensionName
	 * @param string $expectedPrefix
	 * @dataProvider extensionKeyDataProvider
	 */
	public function getClassNamePrefixForExtensionKey($extensionName, $expectedPrefix) {
		$this->assertSame($expectedPrefix, ExtensionManagementUtility::getCN($extensionName));
	}

	/////////////////////////////////////////////
	// Tests concerning getExtensionKeyByPrefix
	/////////////////////////////////////////////
	/**
	 * @test
	 * @see ExtensionManagementUtility::getExtensionKeyByPrefix
	 */
	public function getExtensionKeyByPrefixForLoadedExtensionWithUnderscoresReturnsExtensionKey() {
		ExtensionManagementUtility::clearExtensionKeyMap();
		$uniqueSuffix = uniqid('test');
		$extensionKey = 'tt_news' . $uniqueSuffix;
		$extensionPrefix = 'tx_ttnews' . $uniqueSuffix;
		$package = $this->getMockBuilder('TYPO3\\CMS\\Core\\Package\\Package')
				->disableOriginalConstructor()
				->setMethods(array('getPackageKey'))
				->getMock();
		$package->expects($this->exactly(2))
				->method('getPackageKey')
				->will($this->returnValue($extensionKey));
		$packageManager = $this->getMock('TYPO3\\CMS\\Core\\Package\\PackageManager', array('getActivePackages'));
		$packageManager->expects($this->once())
				->method('getActivePackages')
				->will($this->returnValue(array($extensionKey => $package)));
		ExtensionManagementUtility::setPackageManager($packageManager);
		$this->assertEquals($extensionKey, ExtensionManagementUtility::getExtensionKeyByPrefix($extensionPrefix));
	}

	/**
	 * @test
	 * @see ExtensionManagementUtility::getExtensionKeyByPrefix
	 */
	public function getExtensionKeyByPrefixForLoadedExtensionWithoutUnderscoresReturnsExtensionKey() {
		ExtensionManagementUtility::clearExtensionKeyMap();
		$uniqueSuffix = uniqid('test');
		$extensionKey = 'kickstarter' . $uniqueSuffix;
		$extensionPrefix = 'tx_kickstarter' . $uniqueSuffix;
		$package = $this->getMockBuilder('TYPO3\\CMS\\Core\\Package\\Package')
				->disableOriginalConstructor()
				->setMethods(array('getPackageKey'))
				->getMock();
		$package->expects($this->exactly(2))
				->method('getPackageKey')
				->will($this->returnValue($extensionKey));
		$packageManager = $this->getMock('TYPO3\\CMS\\Core\\Package\\PackageManager', array('getActivePackages'));
		$packageManager->expects($this->once())
				->method('getActivePackages')
				->will($this->returnValue(array($extensionKey => $package)));
		ExtensionManagementUtility::setPackageManager($packageManager);
		$this->assertEquals($extensionKey, ExtensionManagementUtility::getExtensionKeyByPrefix($extensionPrefix));
	}

	/**
	 * @test
	 * @see ExtensionManagementUtility::getExtensionKeyByPrefix
	 */
	public function getExtensionKeyByPrefixForNotLoadedExtensionReturnsFalse() {
		ExtensionManagementUtility::clearExtensionKeyMap();
		$uniqueSuffix = uniqid('test');
		$extensionKey = 'unloadedextension' . $uniqueSuffix;
		$extensionPrefix = 'tx_unloadedextension' . $uniqueSuffix;
		$this->assertFalse(ExtensionManagementUtility::getExtensionKeyByPrefix($extensionPrefix));
	}

	//////////////////////////////////////
	// Tests concerning addToAllTCAtypes
	//////////////////////////////////////
	/**
	 * Tests whether fields can be add to all TCA types and duplicate fields are considered.
	 *
	 * @test
	 * @see ExtensionManagementUtility::addToAllTCAtypes()
	 */
	public function canAddFieldsToAllTCATypesBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'before:fieldD');
		// Checking typeA:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
		// Checking typeB:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
	}

	/**
	 * Tests whether fields can be add to all TCA types and duplicate fields are considered.
	 *
	 * @test
	 * @see ExtensionManagementUtility::addToAllTCAtypes()
	 */
	public function canAddFieldsToAllTCATypesAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:fieldC');
		// Checking typeA:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
		// Checking typeB:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
	}

	/**
	 * Tests whether fields can be add to a TCA type before existing ones
	 *
	 * @test
	 * @see ExtensionManagementUtility::addToAllTCAtypes()
	 */
	public function canAddFieldsToTCATypeBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', 'typeA', 'before:fieldD');
		// Checking typeA:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
		// Checking typeB:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, fieldD', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
	}

	/**
	 * Tests whether fields can be add to a TCA type after existing ones
	 *
	 * @test
	 * @see ExtensionManagementUtility::addToAllTCAtypes()
	 */
	public function canAddFieldsToTCATypeAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', 'typeA', 'after:fieldC');
		// Checking typeA:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, newA, newB, fieldD', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
		// Checking typeB:
		$this->assertEquals('fieldA, fieldB, fieldC;labelC;paletteC;specialC, fieldD', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
	}

	/**
	 * Test wheter replacing other TCA fields works as promissed
	 *
	 * @test
	 * @see ExtensionManagementUtility::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToTCATypeAndReplaceExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		$typesBefore = $GLOBALS['TCA'][$table]['types'];
		ExtensionManagementUtility::addToAllTCAtypes($table, 'fieldZ', '', 'replace:fieldX');
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
	 * @see ExtensionManagementUtility::addFieldsToPalette()
	 */
	public function canAddFieldsToPaletteBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'before:fieldY');
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
	}

	/**
	 * Tests whether fields can be added to a palette after existing elements.
	 *
	 * @test
	 * @see ExtensionManagementUtility::addFieldsToPalette()
	 */
	public function canAddFieldsToPaletteAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:fieldX');
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
	}

	/**
	 * Tests whether fields can be added to a palette after a not existing elements.
	 *
	 * @test
	 * @see ExtensionManagementUtility::addFieldsToPalette()
	 */
	public function canAddFieldsToPaletteAfterNotExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:' . uniqid('notExisting'));
		$this->assertEquals('fieldX, fieldY, newA, newB', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
	}

	/**
	 * Tests whether fields can be added to all palettes of a regular field before existing ones.
	 *
	 * @test
	 * @see ExtensionManagementUtility::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldBeforeExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		ExtensionManagementUtility::addFieldsToAllPalettesOfField($table, 'fieldC', 'newA, newA, newB, fieldX', 'before:fieldY');
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
	}

	/**
	 * Tests whether fields can be added to all palettes of a regular field after existing ones.
	 *
	 * @test
	 * @see ExtensionManagementUtility::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldAfterExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		ExtensionManagementUtility::addFieldsToAllPalettesOfField($table, 'fieldC', 'newA, newA, newB, fieldX', 'after:fieldX');
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
		$this->assertEquals('fieldX, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
	}

	/**
	 * Tests whether fields can be added to all palettes of a regular field after a not existing field.
	 *
	 * @test
	 * @see ExtensionManagementUtility::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldAfterNotExistingOnes() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		ExtensionManagementUtility::addFieldsToAllPalettesOfField($table, 'fieldC', 'newA, newA, newB, fieldX', 'after:' . uniqid('notExisting'));
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
		$this->assertEquals('fieldX, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
		$this->assertEquals('fieldX, fieldY, newA, newB', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
		$this->assertEquals('fieldX, fieldY, newA, newB', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
	}

	/**
	 * Tests whether fields are added to a new palette that did not exist before.
	 *
	 * @test
	 * @see ExtensionManagementUtility::addFieldsToAllPalettesOfField()
	 */
	public function canAddFieldsToAllPalettesOfFieldWithoutPaletteExistingBefore() {
		$table = uniqid('tx_coretest_table');
		$GLOBALS['TCA'] = $this->generateTCAForTable($table);
		ExtensionManagementUtility::addFieldsToAllPalettesOfField($table, 'fieldA', 'newA, newA, newB, fieldX');
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
		ExtensionManagementUtility::addTcaSelectItem(array(), 'foo', array());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfFieldIsNotOfTypeString() {
		ExtensionManagementUtility::addTcaSelectItem('foo', array(), array());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfRelativeToFieldIsNotOfTypeString() {
		ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array(), array());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfRelativePositionIsNotOfTypeString() {
		ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array(), 'foo', array());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function addTcaSelectItemThrowsExceptionIfRelativePositionIsNotOneOfValidKeywords() {
		ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array(), 'foo', 'not allowed keyword');
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function addTcaSelectItemThrowsExceptionIfFieldIsNotFoundInTca() {
		$GLOBALS['TCA'] = array();
		ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array());
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
		ExtensionManagementUtility::addTcaSelectItem('testTable', 'testField', array('insertedElement'), $relativeToField, $relativePosition);
		$this->assertEquals($expectedResultArray, $GLOBALS['TCA']['testTable']['columns']['testField']['config']['items']);
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
		$GLOBALS['TYPO3_LOADED_EXT'] = new \TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray($this->createMockPackageManagerWithMockPackage(uniqid()));
		ExtensionManagementUtility::loadExtLocalconf(FALSE);
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
		ExtensionManagementUtility::loadExtLocalconf(TRUE);
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
		$packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
		$extLocalconfLocation = $packageManager->getPackage($extensionName)->getPackagePath() . 'ext_localconf.php';
		file_put_contents($extLocalconfLocation, "<?php\n\nthrow new RuntimeException('', 1340559079);\n\n?>");
		$GLOBALS['TYPO3_LOADED_EXT'] = new \TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray($packageManager);
		ExtensionManagementUtilityAccessibleProxy::loadSingleExtLocalconfFiles();
	}

	/////////////////////////////////////////
	// Tests concerning addModule
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function newSubmoduleCanBeAddedToTopOfModule() {
		$mainModule = 'foobar';
		$subModule = 'newModule';
		$GLOBALS['TBE_MODULES'][$mainModule] = 'some,modules';

		ExtensionManagementUtility::addModule($mainModule, $subModule, 'top');

		$this->assertEquals('newModule,some,modules', $GLOBALS['TBE_MODULES'][$mainModule]);
	}

	/**
	 * @test
	 */
	public function newSubmoduleCanBeAddedToBottomOfModule() {
		$mainModule = 'foobar';
		$subModule = 'newModule';
		$GLOBALS['TBE_MODULES'][$mainModule] = 'some,modules';

		ExtensionManagementUtility::addModule($mainModule, $subModule, 'bottom');

		$this->assertEquals('some,modules,newModule', $GLOBALS['TBE_MODULES'][$mainModule]);
	}

	/**
	 * @test
	 */
	public function newSubmoduleCanBeAddedAfterSpecifiedSubmodule() {
		$mainModule = 'foobar';
		$subModule = 'newModule';
		$GLOBALS['TBE_MODULES'][$mainModule] = 'some,modules';

		ExtensionManagementUtility::addModule($mainModule, $subModule, 'after:some');

		$this->assertEquals('some,newModule,modules', $GLOBALS['TBE_MODULES'][$mainModule]);
	}

	/**
	 * @test
	 */
	public function newSubmoduleCanBeAddedBeforeSpecifiedSubmodule() {
		$mainModule = 'foobar';
		$subModule = 'newModule';
		$GLOBALS['TBE_MODULES'][$mainModule] = 'some,modules';

		ExtensionManagementUtility::addModule($mainModule, $subModule, 'before:modules');

		$this->assertEquals('some,newModule,modules', $GLOBALS['TBE_MODULES'][$mainModule]);
	}

	/////////////////////////////////////////
	// Tests concerning createExtLocalconfCacheEntry
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function createExtLocalconfCacheEntryWritesCacheEntryWithContentOfLoadedExtensionExtLocalconf() {
		$extensionName = uniqid('foo');
		$packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
		$extLocalconfLocation = $packageManager->getPackage($extensionName)->getPackagePath() . 'ext_localconf.php';
		$this->testFilesToDelete[] = $extLocalconfLocation;
		$uniqueStringInLocalconf = uniqid('foo');
		file_put_contents($extLocalconfLocation, "<?php\n\n" . $uniqueStringInLocalconf . "\n\n?>");
		$GLOBALS['TYPO3_LOADED_EXT'] = new \TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray($packageManager);
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
		ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry();
	}

	/**
	 * @test
	 */
	public function createExtLocalconfCacheEntryWritesCacheEntryWithExtensionContentOnlyIfExtLocalconfExists() {
		$extensionName = uniqid('foo');
		$packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
		$GLOBALS['TYPO3_LOADED_EXT'] = new \TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray($packageManager);
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
		ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry();
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
		$GLOBALS['TYPO3_LOADED_EXT'] = new \TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray($this->createMockPackageManagerWithMockPackage(uniqid()));
		ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry();
	}

	/////////////////////////////////////////
	// Tests concerning getExtLocalconfCacheIdentifier
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function getExtLocalconfCacheIdentifierCreatesSha1WithFourtyCharactersAndPrefix() {
		$prefix = 'ext_localconf_';
		$identifier = ExtensionManagementUtilityAccessibleProxy::getExtLocalconfCacheIdentifier();
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
		ExtensionManagementUtility::loadBaseTca(FALSE);
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
		$mockCache->expects($this->once())->method('get');
		ExtensionManagementUtility::loadBaseTca(TRUE);
	}

	/**
	 * @test
	 */
	public function loadBaseTcaCreatesCacheFileWithContentOfAnExtensionsConfigurationTcaPhpFile() {
		$extensionName = uniqid('test_baseTca_');
		$packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
		$packagePath = $packageManager->getPackage($extensionName)->getPackagePath();
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($packagePath);
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($packagePath . 'Configuration/');
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($packagePath . 'Configuration/TCA/');
		$GLOBALS['TYPO3_LOADED_EXT'] = new \TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray($packageManager);
		ExtensionManagementUtility::setPackageManager($packageManager);
		$uniqueTableName = uniqid('table_name_');
		$uniqueStringInTableConfiguration = uniqid('table_configuration_');
		$tableConfiguration = '<?php return array(\'foo\' => \'' . $uniqueStringInTableConfiguration . '\'); ?>';
		file_put_contents($packagePath . 'Configuration/TCA/' . $uniqueTableName . '.php', $tableConfiguration);
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
		ExtensionManagementUtility::loadBaseTca(TRUE);
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
		ExtensionManagementUtility::loadBaseTca();
	}

	/////////////////////////////////////////
	// Tests concerning getBaseTcaCacheIdentifier
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function getBaseTcaCacheIdentifierCreatesSha1WithFourtyCharactersAndPrefix() {
		$prefix = 'tca_base_';
		$identifier = ExtensionManagementUtilityAccessibleProxy::getBaseTcaCacheIdentifier();
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
		$GLOBALS['TYPO3_LOADED_EXT'] = new \TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray($this->createMockPackageManagerWithMockPackage(uniqid()));
		ExtensionManagementUtility::loadExtLocalconf(FALSE);
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
		ExtensionManagementUtilityAccessibleProxy::resetExtTablesWasReadFromCacheOnceBoolean();
		ExtensionManagementUtility::loadExtTables(TRUE);
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
		ExtensionManagementUtilityAccessibleProxy::createExtTablesCacheEntry();
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
		ExtensionManagementUtilityAccessibleProxy::createExtTablesCacheEntry();
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
		$GLOBALS['TYPO3_LOADED_EXT'] = new \TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray($this->createMockPackageManagerWithMockPackage(uniqid()));
		ExtensionManagementUtilityAccessibleProxy::createExtTablesCacheEntry();
	}

	/////////////////////////////////////////
	// Tests concerning getExtTablesCacheIdentifier
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function getExtTablesCacheIdentifierCreatesSha1WithFourtyCharactersAndPrefix() {
		$prefix = 'ext_tables_';
		$identifier = ExtensionManagementUtilityAccessibleProxy::getExtTablesCacheIdentifier();
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
	public function removeCacheFilesFlushesSystemCaches() {
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('flushCachesInGroup'));
		$GLOBALS['typo3CacheManager']->expects($this->once())->method('flushCachesInGroup')->with('system');
		ExtensionManagementUtility::removeCacheFiles();
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
		ExtensionManagementUtility::loadNewTcaColumnsConfigFiles();
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
		ExtensionManagementUtility::loadNewTcaColumnsConfigFiles();
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
		ExtensionManagementUtility::getExtensionVersion($key);
	}

	/**
	 * @test
	 */
	public function getExtensionVersionForNotLoadedExtensionReturnsEmptyString() {
		ExtensionManagementUtility::clearExtensionKeyMap();
		$uniqueSuffix = uniqid('test');
		$extensionKey = 'unloadedextension' . $uniqueSuffix;
		$this->assertEquals('', ExtensionManagementUtility::getExtensionVersion($extensionKey));
	}

	/**
	 * @test
	 */
	public function getExtensionVersionForLoadedExtensionReturnsExtensionVersion() {
		ExtensionManagementUtility::clearExtensionKeyMap();
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . __NAMESPACE__ .';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
			'  public static function isLoaded() {' .
			'    return TRUE;' .
			'  }' .
			'}'
		);
		$className = __NAMESPACE__ . '\\' . $className;
		ExtensionManagementUtility::clearExtensionKeyMap();
		$uniqueSuffix = uniqid('test');
		$extensionKey = 'unloadedextension' . $uniqueSuffix;
		$packageMetaData = $this->getMock('TYPO3\\Flow\\Package\\MetaData', array('getVersion'), array($extensionKey));
		$packageMetaData->expects($this->any())->method('getVersion')->will($this->returnValue('1.2.3'));
		$packageManager = $this->createMockPackageManagerWithMockPackage($extensionKey, array('getPackagePath', 'getPackageKey', 'getPackageMetaData'));
		/** @var \PHPUnit_Framework_MockObject_MockObject $package */
		$package = $packageManager->getPackage($extensionKey);
		$package->expects($this->any())
				->method('getPackageMetaData')
				->will($this->returnValue($packageMetaData));
		ExtensionManagementUtility::setPackageManager($packageManager);
		$this->assertEquals('1.2.3', ExtensionManagementUtility::getExtensionVersion($extensionKey));
	}

	/////////////////////////////////////////
	// Tests concerning loadExtension
	/////////////////////////////////////////
	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function loadExtensionThrowsExceptionIfExtensionIsLoaded() {
		$extensionKey = uniqid('test');
		$packageManager = $this->createMockPackageManagerWithMockPackage($extensionKey);
		ExtensionManagementUtility::setPackageManager($packageManager);
		ExtensionManagementUtility::loadExtension($extensionKey);
	}

	/////////////////////////////////////////
	// Tests concerning unloadExtension
	/////////////////////////////////////////
	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function unloadExtensionThrowsExceptionIfExtensionIsNotLoaded() {
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . __NAMESPACE__ . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
			'  public static function isLoaded() {' .
			'    return FALSE;' .
			'  }' .
			'}'
		);
		$className = __NAMESPACE__ . '\\' . $className;
		$className::unloadExtension('test');
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function unloadExtensionThrowsExceptionIfExtensionIsRequired() {
		$extensionKey = uniqid('test');
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . __NAMESPACE__ . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility {' .
			'  public static function isLoaded() {' .
			'    return TRUE;' .
			'  }' .
			'  public static function getRequiredExtensionListArray() {' .
			'    return array(\'' . $extensionKey . '\');' .
			'  }' .
			'}'
		);
		$className = __NAMESPACE__ . '\\' . $className;
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
		$className = uniqid('ExtensionManagementUtility');
		eval(
			'namespace ' . __NAMESPACE__ . ';' .
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
		$className = __NAMESPACE__ . '\\' . $className;
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
		ExtensionManagementUtility::makeCategorizable($extensionKey, $tableName);
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
		ExtensionManagementUtility::makeCategorizable($extensionKey, $tableName, $fieldName);
		$registryMock->applyTca();
		$this->assertNotEmpty($GLOBALS['TCA'][$tableName]['columns'][$fieldName]);
	}

}
