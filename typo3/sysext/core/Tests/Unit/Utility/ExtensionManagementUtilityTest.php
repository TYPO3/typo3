<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend;
use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray;
use TYPO3\CMS\Core\Package\MetaData;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class ExtensionManagementUtilityTest extends UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = array();

    /**
     * @var \TYPO3\CMS\Core\Package\PackageManager
     */
    protected $backUpPackageManager;

    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->backUpPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
    }

    protected function tearDown()
    {
        ExtensionManagementUtility::clearExtensionKeyMap();
        ExtensionManagementUtilityAccessibleProxy::setPackageManager($this->backUpPackageManager);
        ExtensionManagementUtilityAccessibleProxy::setCacheManager(null);
        $GLOBALS['TYPO3_LOADED_EXT'] = new LoadedExtensionsArray($this->backUpPackageManager);
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @param string $packageKey
     * @param array $packageMethods
     * @return PackageManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockPackageManagerWithMockPackage($packageKey, $packageMethods = array('getPackagePath', 'getPackageKey'))
    {
        $packagePath = PATH_site . 'typo3temp/' . $packageKey . '/';
        GeneralUtility::mkdir_deep($packagePath);
        $this->testFilesToDelete[] = $packagePath;
        $package = $this->getMockBuilder(Package::class)
                ->disableOriginalConstructor()
                ->setMethods($packageMethods)
                ->getMock();
        $packageManager = $this->getMock(
            PackageManager::class,
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
                    array(null, false),
                    array($packageKey, true)
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
    public function isLoadedReturnsFalseIfExtensionIsNotLoadedAndExitIsDisabled()
    {
        $this->assertFalse(ExtensionManagementUtility::isLoaded($this->getUniqueId('foobar'), false));
    }

    /**
     * @test
     * @expectedException \BadFunctionCallException
     */
    public function isLoadedThrowsExceptionIfExtensionIsNotLoaded()
    {
        $this->assertFalse(ExtensionManagementUtility::isLoaded($this->getUniqueId('foobar'), true));
    }

    ///////////////////////////////
    // Tests concerning extPath
    ///////////////////////////////
    /**
     * @test
     * @expectedException \BadFunctionCallException
     */
    public function extPathThrowsExceptionIfExtensionIsNotLoaded()
    {
        $packageName = $this->getUniqueId('foo');
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMock(PackageManager::class, array('isPackageActive'));
        $packageManager->expects($this->once())
                ->method('isPackageActive')
                ->with($this->equalTo($packageName))
                ->will($this->returnValue(false));
        ExtensionManagementUtility::setPackageManager($packageManager);
        ExtensionManagementUtility::extPath($packageName);
    }

    /**
     * @test
     */
    public function extPathAppendsScriptNameToPath()
    {
        $package = $this->getMockBuilder(Package::class)
                ->disableOriginalConstructor()
                ->setMethods(array('getPackagePath'))
                ->getMock();
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMock(PackageManager::class, array('isPackageActive', 'getPackage'));
        $package->expects($this->once())
                ->method('getPackagePath')
                ->will($this->returnValue(PATH_site . 'foo/'));
        $packageManager->expects($this->once())
                ->method('isPackageActive')
                ->with($this->equalTo('foo'))
                ->will($this->returnValue(true));
        $packageManager->expects($this->once())
                ->method('getPackage')
                ->with('foo')
                ->will($this->returnValue($package));
        ExtensionManagementUtility::setPackageManager($packageManager);
        $this->assertSame(PATH_site . 'foo/bar.txt', ExtensionManagementUtility::extPath('foo', 'bar.txt'));
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
    private function generateTCAForTable($table)
    {
        $tca = array();
        $tca[$table] = array();
        $tca[$table]['columns'] = array(
            'fieldA' => array(),
            'fieldC' => array()
        );
        $tca[$table]['types'] = array(
            'typeA' => array('showitem' => 'fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1'),
            'typeB' => array('showitem' => 'fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1'),
            'typeC' => array('showitem' => 'fieldC;;paletteD')
        );
        $tca[$table]['palettes'] = array(
            'paletteA' => array('showitem' => 'fieldX, fieldX1, fieldY'),
            'paletteB' => array('showitem' => 'fieldX, fieldX1, fieldY'),
            'paletteC' => array('showitem' => 'fieldX, fieldX1, fieldY'),
            'paletteD' => array('showitem' => 'fieldX, fieldX1, fieldY')
        );
        return $tca;
    }

    /**
     * Data provider for getClassNamePrefixForExtensionKey.
     *
     * @return array
     */
    public function extensionKeyDataProvider()
    {
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
    public function getClassNamePrefixForExtensionKey($extensionName, $expectedPrefix)
    {
        $this->assertSame($expectedPrefix, ExtensionManagementUtility::getCN($extensionName));
    }

    /////////////////////////////////////////////
    // Tests concerning getExtensionKeyByPrefix
    /////////////////////////////////////////////
    /**
     * @test
     * @see ExtensionManagementUtility::getExtensionKeyByPrefix
     */
    public function getExtensionKeyByPrefixForLoadedExtensionWithUnderscoresReturnsExtensionKey()
    {
        ExtensionManagementUtility::clearExtensionKeyMap();
        $uniqueSuffix = $this->getUniqueId('test');
        $extensionKey = 'tt_news' . $uniqueSuffix;
        $extensionPrefix = 'tx_ttnews' . $uniqueSuffix;
        $package = $this->getMockBuilder(Package::class)
                ->disableOriginalConstructor()
                ->setMethods(array('getPackageKey'))
                ->getMock();
        $package->expects($this->exactly(2))
                ->method('getPackageKey')
                ->will($this->returnValue($extensionKey));
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMock(PackageManager::class, array('getActivePackages'));
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
    public function getExtensionKeyByPrefixForLoadedExtensionWithoutUnderscoresReturnsExtensionKey()
    {
        ExtensionManagementUtility::clearExtensionKeyMap();
        $uniqueSuffix = $this->getUniqueId('test');
        $extensionKey = 'kickstarter' . $uniqueSuffix;
        $extensionPrefix = 'tx_kickstarter' . $uniqueSuffix;
        $package = $this->getMockBuilder(Package::class)
                ->disableOriginalConstructor()
                ->setMethods(array('getPackageKey'))
                ->getMock();
        $package->expects($this->exactly(2))
                ->method('getPackageKey')
                ->will($this->returnValue($extensionKey));
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMock(PackageManager::class, array('getActivePackages'));
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
    public function getExtensionKeyByPrefixForNotLoadedExtensionReturnsFalse()
    {
        ExtensionManagementUtility::clearExtensionKeyMap();
        $uniqueSuffix = $this->getUniqueId('test');
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
    public function canAddFieldsToAllTCATypesBeforeExistingOnes()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'before:fieldD');
        // Checking typeA:
        $this->assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, newA, newB, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
        // Checking typeB:
        $this->assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, newA, newB, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
    }

    /**
     * Tests whether fields can be add to all TCA types and duplicate fields are considered.
     *
     * @test
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    public function canAddFieldsToAllTCATypesAfterExistingOnes()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:fieldC');
        // Checking typeA:
        $this->assertEquals('fieldA, fieldB, fieldC;labelC, newA, newB, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
        // Checking typeB:
        $this->assertEquals('fieldA, fieldB, fieldC;labelC, newA, newB, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
    }

    /**
     * Tests whether fields can be add to all TCA types and duplicate fields are considered.
     *
     * @test
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    public function canAddFieldsToAllTCATypesRespectsPalettes()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        $GLOBALS['TCA'][$table]['types']['typeD'] = ['showitem' => 'fieldY, --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.standard;standard, fieldZ'];
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.standard;standard');
        // Checking typeD:
        $this->assertEquals('fieldY, --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.standard;standard, newA, newB, fieldA, fieldZ', $GLOBALS['TCA'][$table]['types']['typeD']['showitem']);
    }

    /**
     * Tests whether fields can be add to all TCA types and fields in pallets are respected.
     *
     * @test
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    public function canAddFieldsToAllTCATypesRespectsPositionFieldInPalette()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:fieldX1');
        // Checking typeA:
        $this->assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, newA, newB, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
    }

    /**
     * Tests whether fields can be add to a TCA type before existing ones
     *
     * @test
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    public function canAddFieldsToTCATypeBeforeExistingOnes()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', 'typeA', 'before:fieldD');
        // Checking typeA:
        $this->assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, newA, newB, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
        // Checking typeB:
        $this->assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
    }

    /**
     * Tests whether fields can be add to a TCA type after existing ones
     *
     * @test
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    public function canAddFieldsToTCATypeAfterExistingOnes()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', 'typeA', 'after:fieldC');
        // Checking typeA:
        $this->assertEquals('fieldA, fieldB, fieldC;labelC, newA, newB, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
        // Checking typeB:
        $this->assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
    }

    /**
     * Test wheter replacing other TCA fields works as promissed
     *
     * @test
     * @see ExtensionManagementUtility::addFieldsToAllPalettesOfField()
     */
    public function canAddFieldsToTCATypeAndReplaceExistingOnes()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        $typesBefore = $GLOBALS['TCA'][$table]['types'];
        ExtensionManagementUtility::addToAllTCAtypes($table, 'fieldZ', '', 'replace:fieldX');
        $this->assertEquals($typesBefore, $GLOBALS['TCA'][$table]['types'], 'It\'s wrong that the "types" array changes here - the replaced field is only on palettes');
        // unchanged because the palette is not used
        $this->assertEquals('fieldX, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
        $this->assertEquals('fieldX, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
        // changed
        $this->assertEquals('fieldZ, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
        $this->assertEquals('fieldZ, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
    }

    /**
     * @test
     */
    public function addToAllTCAtypesReplacesExistingOnes()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        $typesBefore = $GLOBALS['TCA'][$table]['types'];
        ExtensionManagementUtility::addToAllTCAtypes($table, 'fieldX, --palette--;;foo', '', 'replace:fieldX');
        $this->assertEquals($typesBefore, $GLOBALS['TCA'][$table]['types'], 'It\'s wrong that the "types" array changes here - the replaced field is only on palettes');
        // unchanged because the palette is not used
        $this->assertEquals('fieldX, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
        $this->assertEquals('fieldX, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
        // changed
        $this->assertEquals('fieldX, --palette--;;foo, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
        $this->assertEquals('fieldX, --palette--;;foo, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
    }

    /**
     * Tests whether fields can be added to a palette before existing elements.
     *
     * @test
     * @see ExtensionManagementUtility::addFieldsToPalette()
     */
    public function canAddFieldsToPaletteBeforeExistingOnes()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'before:fieldY');
        $this->assertEquals('fieldX, fieldX1, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
    }

    /**
     * Tests whether fields can be added to a palette after existing elements.
     *
     * @test
     * @see ExtensionManagementUtility::addFieldsToPalette()
     */
    public function canAddFieldsToPaletteAfterExistingOnes()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:fieldX');
        $this->assertEquals('fieldX, newA, newB, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
    }

    /**
     * Tests whether fields can be added to a palette after a not existing elements.
     *
     * @test
     * @see ExtensionManagementUtility::addFieldsToPalette()
     */
    public function canAddFieldsToPaletteAfterNotExistingOnes()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:' . $this->getUniqueId('notExisting'));
        $this->assertEquals('fieldX, fieldX1, fieldY, newA, newB', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
    }

    /**
     * @return array
     */
    public function removeDuplicatesForInsertionRemovesDuplicatesDataProvider()
    {
        return array(
            'Simple' => array(
                'field_b, field_d, field_c',
                'field_a, field_b, field_c',
                'field_d'
            ),
            'with linebreaks' => array(
                'field_b, --linebreak--, field_d, --linebreak--, field_c',
                'field_a, field_b, field_c',
                '--linebreak--, field_d, --linebreak--'
            ),
            'with linebreaks in list and insertion list' => array(
                'field_b, --linebreak--, field_d, --linebreak--, field_c',
                'field_a, field_b, --linebreak--, field_c',
                '--linebreak--, field_d, --linebreak--'
            ),
        );
    }

    /**
     * @test
     * @dataProvider removeDuplicatesForInsertionRemovesDuplicatesDataProvider
     * @param $insertionList
     * @param $list
     * @param $expected
     */
    public function removeDuplicatesForInsertionRemovesDuplicates($insertionList, $list, $expected)
    {
        $result = ExtensionManagementUtilityAccessibleProxy::removeDuplicatesForInsertion($insertionList, $list);
        $this->assertSame($expected, $result);
    }

    ///////////////////////////////////////////////////
    // Tests concerning addFieldsToAllPalettesOfField
    ///////////////////////////////////////////////////

    /**
     * @test
     */
    public function addFieldsToAllPalettesOfFieldDoesNotAddAnythingIfFieldIsNotRegisteredInColumns()
    {
        $GLOBALS['TCA'] = array(
            'aTable' => array(
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'fieldX, fieldY',
                    ),
                ),
            ),
        );
        $expected = $GLOBALS['TCA'];
        ExtensionManagementUtility::addFieldsToAllPalettesOfField(
            'aTable',
            'fieldA',
            'newA'
        );
        $this->assertEquals($expected, $GLOBALS['TCA']);
    }

    /**
     * @test
     */
    public function addFieldsToAllPalettesOfFieldAddsFieldsToPaletteAndSuppressesDuplicates()
    {
        $GLOBALS['TCA'] = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'fieldX, fieldY',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'fieldX, fieldY, dupeA',
                    ),
                ),
            ),
        );
        ExtensionManagementUtility::addFieldsToAllPalettesOfField(
            'aTable',
            'fieldA',
            'dupeA, dupeA' // Duplicate
        );
        $this->assertEquals($expected, $GLOBALS['TCA']);
    }

    /**
     * @test
     */
    public function addFieldsToAllPalettesOfFieldDoesNotAddAFieldThatIsPartOfPaletteAlready()
    {
        $GLOBALS['TCA'] = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'existingA',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'existingA',
                    ),
                ),
            ),
        );
        ExtensionManagementUtility::addFieldsToAllPalettesOfField(
            'aTable',
            'fieldA',
            'existingA'
        );
        $this->assertEquals($expected, $GLOBALS['TCA']);
    }

    /**
     * @test
     */
    public function addFieldsToAllPalettesOfFieldAddsFieldsToMultiplePalettes()
    {
        $GLOBALS['TCA'] = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA, --palette--;;palette1',
                    ),
                    'typeB' => array(
                        'showitem' => 'fieldA;aLabel, --palette--;;palette2',
                    ),
                ),
                'palettes' => array(
                    'palette1' => array(
                        'showitem' => 'fieldX',
                    ),
                    'palette2' => array(
                        'showitem' => 'fieldY',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA, --palette--;;palette1',
                    ),
                    'typeB' => array(
                        'showitem' => 'fieldA;aLabel, --palette--;;palette2',
                    ),
                ),
                'palettes' => array(
                    'palette1' => array(
                        'showitem' => 'fieldX, newA',
                    ),
                    'palette2' => array(
                        'showitem' => 'fieldY, newA',
                    ),
                ),
            ),
        );
        ExtensionManagementUtility::addFieldsToAllPalettesOfField(
            'aTable',
            'fieldA',
            'newA'
        );
        $this->assertEquals($expected, $GLOBALS['TCA']);
    }

    /**
     * @test
     */
    public function addFieldsToAllPalettesOfFieldAddsMultipleFields()
    {
        $GLOBALS['TCA'] = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA, --palette--;;palette1',
                    ),
                ),
                'palettes' => array(
                    'palette1' => array(
                        'showitem' => 'fieldX',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA, --palette--;;palette1',
                    ),
                ),
                'palettes' => array(
                    'palette1' => array(
                        'showitem' => 'fieldX, newA, newB',
                    ),
                ),
            ),
        );
        ExtensionManagementUtility::addFieldsToAllPalettesOfField(
            'aTable',
            'fieldA',
            'newA, newB'
        );
        $this->assertEquals($expected, $GLOBALS['TCA']);
    }

    /**
     * @test
     */
    public function addFieldsToAllPalettesOfFieldAddsBeforeExistingIfRequested()
    {
        $GLOBALS['TCA'] = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'existingA, existingB',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'existingA, newA, existingB',
                    ),
                ),
            ),
        );
        ExtensionManagementUtility::addFieldsToAllPalettesOfField(
            'aTable',
            'fieldA',
            'newA',
            'before:existingB'
        );
        $this->assertEquals($expected, $GLOBALS['TCA']);
    }

    /**
     * @test
     */
    public function addFieldsToAllPalettesOfFieldAddsFieldsAtEndIfBeforeRequestedDoesNotExist()
    {
        $GLOBALS['TCA'] = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'fieldX, fieldY',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'fieldX, fieldY, newA, newB',
                    ),
                ),
            ),
        );
        ExtensionManagementUtility::addFieldsToAllPalettesOfField(
            'aTable',
            'fieldA',
            'newA, newB',
            'before:notExisting'
        );
        $this->assertEquals($expected, $GLOBALS['TCA']);
    }

    /**
     * @test
     */
    public function addFieldsToAllPalettesOfFieldAddsAfterExistingIfRequested()
    {
        $GLOBALS['TCA'] = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'existingA, existingB',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'existingA, newA, existingB',
                    ),
                ),
            ),
        );
        ExtensionManagementUtility::addFieldsToAllPalettesOfField(
            'aTable',
            'fieldA',
            'newA',
            'after:existingA'
        );
        $this->assertEquals($expected, $GLOBALS['TCA']);
    }

    /**
     * @test
     */
    public function addFieldsToAllPalettesOfFieldAddsFieldsAtEndIfAfterRequestedDoesNotExist()
    {
        $GLOBALS['TCA'] = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'existingA, existingB',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ),
                ),
                'palettes' => array(
                    'paletteA' => array(
                        'showitem' => 'existingA, existingB, newA, newB',
                    ),
                ),
            ),
        );
        ExtensionManagementUtility::addFieldsToAllPalettesOfField(
            'aTable',
            'fieldA',
            'newA, newB',
            'after:notExistingA'
        );
        $this->assertEquals($expected, $GLOBALS['TCA']);
    }

    /**
     * @test
     */
    public function addFieldsToAllPalettesOfFieldAddsNewPaletteIfFieldHasNoPaletteYet()
    {
        $GLOBALS['TCA'] = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA, --palette--;;generatedFor-fieldA',
                    ),
                ),
                'palettes' => array(
                    'generatedFor-fieldA' => array(
                        'showitem' => 'newA',
                    ),
                ),
            ),
        );
        ExtensionManagementUtility::addFieldsToAllPalettesOfField(
            'aTable',
            'fieldA',
            'newA'
        );
        $this->assertEquals($expected, $GLOBALS['TCA']);
    }

    /**
     * @test
     */
    public function addFieldsToAllPalettesOfFieldAddsNewPaletteIfFieldHasNoPaletteYetAndKeepsExistingLabel()
    {
        $GLOBALS['TCA'] = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'fieldA' => array(),
                ),
                'types' => array(
                    'typeA' => array(
                        'showitem' => 'fieldA;labelA, --palette--;;generatedFor-fieldA',
                    ),
                ),
                'palettes' => array(
                    'generatedFor-fieldA' => array(
                        'showitem' => 'newA',
                    ),
                ),
            ),
        );
        ExtensionManagementUtility::addFieldsToAllPalettesOfField(
            'aTable',
            'fieldA',
            'newA'
        );
        $this->assertEquals($expected, $GLOBALS['TCA']);
    }

    ///////////////////////////////////////////////////
    // Tests concerning executePositionedStringInsertion
    ///////////////////////////////////////////////////

    /**
     * Data provider for executePositionedStringInsertionTrimsCorrectCharacters
     * @return array
     */
    public function executePositionedStringInsertionTrimsCorrectCharactersDataProvider()
    {
        return array(
            'normal characters' => array(
                'tr0',
                'tr0',
            ),
            'newlines' => array(
                "test\n",
                'test',
            ),
            'newlines with carriage return' => array(
                "test\r\n",
                'test',
            ),
            'tabs' => array(
                "test\t",
                'test',
            ),
            'commas' => array(
                'test,',
                'test',
            ),
            'multiple commas with trailing spaces' => array(
                "test,,\t, \r\n",
                'test',
            ),
        );
    }

    /**
     * @test
     * @dataProvider executePositionedStringInsertionTrimsCorrectCharactersDataProvider
     * @param $string
     * @param $expectedResult
     */
    public function executePositionedStringInsertionTrimsCorrectCharacters($string, $expectedResult)
    {
        $extensionManagementUtility = $this->getAccessibleMock(ExtensionManagementUtility::class, array('dummy'));
        $string = $extensionManagementUtility->_call('executePositionedStringInsertion', $string, '');
        $this->assertEquals($expectedResult, $string);
    }

    /////////////////////////////////////////
    // Tests concerning addTcaSelectItem
    /////////////////////////////////////////
    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function addTcaSelectItemThrowsExceptionIfTableIsNotOfTypeString()
    {
        ExtensionManagementUtility::addTcaSelectItem(array(), 'foo', array());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function addTcaSelectItemThrowsExceptionIfFieldIsNotOfTypeString()
    {
        ExtensionManagementUtility::addTcaSelectItem('foo', array(), array());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function addTcaSelectItemThrowsExceptionIfRelativeToFieldIsNotOfTypeString()
    {
        ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array(), array());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function addTcaSelectItemThrowsExceptionIfRelativePositionIsNotOfTypeString()
    {
        ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array(), 'foo', array());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function addTcaSelectItemThrowsExceptionIfRelativePositionIsNotOneOfValidKeywords()
    {
        ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array(), 'foo', 'not allowed keyword');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function addTcaSelectItemThrowsExceptionIfFieldIsNotFoundInTca()
    {
        $GLOBALS['TCA'] = array();
        ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', array());
    }

    /**
     * Data provider for addTcaSelectItemInsertsItemAtSpecifiedPosition
     */
    public function addTcaSelectItemDataProvider()
    {
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
     * @param $relativeToField
     * @param $relativePosition
     * @param $expectedResultArray
     */
    public function addTcaSelectItemInsertsItemAtSpecifiedPosition($relativeToField, $relativePosition, $expectedResultArray)
    {
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
    public function loadExtLocalconfDoesNotReadFromCacheIfCachingIsDenied()
    {
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->never())->method('getCache');
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $GLOBALS['TYPO3_LOADED_EXT'] = new LoadedExtensionsArray($this->createMockPackageManagerWithMockPackage($this->getUniqueId()));
        ExtensionManagementUtility::loadExtLocalconf(false);
    }

    /**
     * @test
     */
    public function loadExtLocalconfRequiresCacheFileIfExistsAndCachingIsAllowed()
    {
        $mockCache = $this->getMock(
            AbstractFrontend::class,
            array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
            array(),
            '',
            false
        );
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->any())->method('has')->will($this->returnValue(true));
        $mockCache->expects($this->once())->method('requireOnce');
        ExtensionManagementUtility::loadExtLocalconf(true);
    }

    /////////////////////////////////////////
    // Tests concerning loadSingleExtLocalconfFiles
    /////////////////////////////////////////
    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function loadSingleExtLocalconfFilesRequiresExtLocalconfFileRegisteredInGlobalTypo3LoadedExt()
    {
        $extensionName = $this->getUniqueId('foo');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        $extLocalconfLocation = $packageManager->getPackage($extensionName)->getPackagePath() . 'ext_localconf.php';
        file_put_contents($extLocalconfLocation, "<?php\n\nthrow new RuntimeException('', 1340559079);\n\n?>");
        $GLOBALS['TYPO3_LOADED_EXT'] = new LoadedExtensionsArray($packageManager);
        ExtensionManagementUtilityAccessibleProxy::loadSingleExtLocalconfFiles();
    }

    /////////////////////////////////////////
    // Tests concerning addModule
    /////////////////////////////////////////

    /**
     * Data provider for addModule tests
     * @return array
     */
    public function addModulePositionTestsDataProvider()
    {
        return array(
            'can add new main module if none exists' => array(
                'top',
                '',
                'newModule'
            ),
            'can add new sub module if no position specified' => array(
                '',
                'some,modules',
                'some,modules,newModule'
            ),
            'can add new sub module to top of module' => array(
                'top',
                'some,modules',
                'newModule,some,modules'
            ),
            'can add new sub module if bottom of module' => array(
                'bottom',
                'some,modules',
                'some,modules,newModule'
            ),
            'can add new sub module before specified sub module' => array(
                'before:modules',
                'some,modules',
                'some,newModule,modules'
            ),
            'can add new sub module after specified sub module' => array(
                'after:some',
                'some,modules',
                'some,newModule,modules'
            ),
            'can add new sub module at the bottom if specified sub module to add before does not exist' => array(
                'before:modules',
                'some,otherModules',
                'some,otherModules,newModule'
            ),
            'can add new sub module at the bottom if specified sub module to add after does not exist' => array(
                'after:some',
                'someOther,modules',
                'someOther,modules,newModule'
            ),
        );
    }

    /**
     * @test
     * @dataProvider addModulePositionTestsDataProvider
     * @param $position
     * @param $existing
     * @param $expected
     */
    public function addModuleCanAddModule($position, $existing, $expected)
    {
        $mainModule = 'foobar';
        $subModule = 'newModule';
        if ($existing) {
            $GLOBALS['TBE_MODULES'][$mainModule] = $existing;
        }

        ExtensionManagementUtility::addModule($mainModule, $subModule, $position);

        $this->assertTrue(isset($GLOBALS['TBE_MODULES'][$mainModule]));
        $this->assertEquals($expected, $GLOBALS['TBE_MODULES'][$mainModule]);
    }

    /////////////////////////////////////////
    // Tests concerning createExtLocalconfCacheEntry
    /////////////////////////////////////////
    /**
     * @test
     */
    public function createExtLocalconfCacheEntryWritesCacheEntryWithContentOfLoadedExtensionExtLocalconf()
    {
        $extensionName = $this->getUniqueId('foo');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        $extLocalconfLocation = $packageManager->getPackage($extensionName)->getPackagePath() . 'ext_localconf.php';
        $uniqueStringInLocalconf = $this->getUniqueId('foo');
        file_put_contents($extLocalconfLocation, "<?php\n\n" . $uniqueStringInLocalconf . "\n\n?>");
        $GLOBALS['TYPO3_LOADED_EXT'] = new LoadedExtensionsArray($packageManager);
        $mockCache = $this->getMock(
            AbstractFrontend::class,
            array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
            array(),
            '',
            false
        );
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->stringContains($uniqueStringInLocalconf), $this->anything());
        ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry();
    }

    /**
     * @test
     */
    public function createExtLocalconfCacheEntryWritesCacheEntryWithExtensionContentOnlyIfExtLocalconfExists()
    {
        $extensionName = $this->getUniqueId('foo');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        $GLOBALS['TYPO3_LOADED_EXT'] = new LoadedExtensionsArray($packageManager);
        $mockCache = $this->getMock(
            AbstractFrontend::class,
            array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
            array(),
            '',
            false
        );
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->once())
            ->method('set')
            ->with($this->anything(), $this->logicalNot($this->stringContains($extensionName)), $this->anything());
        ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry();
    }

    /**
     * @test
     */
    public function createExtLocalconfCacheEntryWritesCacheEntryWithNoTags()
    {
        $mockCache = $this->getMock(
            AbstractFrontend::class,
            array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
            array(),
            '',
            false
        );
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->equalTo(array()));
        $GLOBALS['TYPO3_LOADED_EXT'] = new LoadedExtensionsArray($this->createMockPackageManagerWithMockPackage($this->getUniqueId()));
        ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry();
    }

    /////////////////////////////////////////
    // Tests concerning getExtLocalconfCacheIdentifier
    /////////////////////////////////////////
    /**
     * @test
     */
    public function getExtLocalconfCacheIdentifierCreatesSha1WithFourtyCharactersAndPrefix()
    {
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
    public function loadBaseTcaDoesNotReadFromCacheIfCachingIsDenied()
    {
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->never())->method('getCache');
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        ExtensionManagementUtilityAccessibleProxy::loadBaseTca(false);
    }

    /**
     * @test
     */
    public function loadBaseTcaRequiresCacheFileIfExistsAndCachingIsAllowed()
    {
        $mockCache = $this->getMock(
            AbstractFrontend::class,
            array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
            array(),
            '',
            false
        );
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->any())->method('has')->will($this->returnValue(true));
        $mockCache->expects($this->once())->method('get')->willReturn('<?php ' . serialize(array('tca' => array(), 'categoryRegistry' => CategoryRegistry::getInstance())) . '?>');
        ExtensionManagementUtilityAccessibleProxy::loadBaseTca(true);
    }

    /**
     * @test
     */
    public function loadBaseTcaCreatesCacheFileWithContentOfAnExtensionsConfigurationTcaPhpFile()
    {
        $extensionName = $this->getUniqueId('test_baseTca_');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        $packagePath = $packageManager->getPackage($extensionName)->getPackagePath();
        GeneralUtility::mkdir($packagePath);
        GeneralUtility::mkdir($packagePath . 'Configuration/');
        GeneralUtility::mkdir($packagePath . 'Configuration/TCA/');
        $GLOBALS['TYPO3_LOADED_EXT'] = new LoadedExtensionsArray($packageManager);
        ExtensionManagementUtility::setPackageManager($packageManager);
        $uniqueTableName = $this->getUniqueId('table_name_');
        $uniqueStringInTableConfiguration = $this->getUniqueId('table_configuration_');
        $tableConfiguration = '<?php return array(\'foo\' => \'' . $uniqueStringInTableConfiguration . '\'); ?>';
        file_put_contents($packagePath . 'Configuration/TCA/' . $uniqueTableName . '.php', $tableConfiguration);
        $mockCache = $this->getMock(
            AbstractFrontend::class,
            array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
            array(),
            '',
            false
        );
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->once())->method('has')->will($this->returnValue(false));
        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->stringContains($uniqueStringInTableConfiguration), $this->anything());
        ExtensionManagementUtility::loadBaseTca(true);
    }

    /**
     * @test
     */
    public function loadBaseTcaWritesCacheEntryWithNoTags()
    {
        $mockCache = $this->getMock(
            AbstractFrontend::class,
            array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
            array(),
            '',
            false
        );
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->once())->method('has')->will($this->returnValue(false));
        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->equalTo(array()));
        ExtensionManagementUtilityAccessibleProxy::loadBaseTca();
    }

    /////////////////////////////////////////
    // Tests concerning getBaseTcaCacheIdentifier
    /////////////////////////////////////////

    /**
     * @test
     */
    public function getBaseTcaCacheIdentifierCreatesSha1WithFourtyCharactersAndPrefix()
    {
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
    public function loadExtTablesDoesNotReadFromCacheIfCachingIsDenied()
    {
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->never())->method('getCache');
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $GLOBALS['TYPO3_LOADED_EXT'] = new LoadedExtensionsArray($this->createMockPackageManagerWithMockPackage($this->getUniqueId()));
        ExtensionManagementUtility::loadExtLocalconf(false);
    }

    /**
     * @test
     */
    public function loadExtTablesRequiresCacheFileIfExistsAndCachingIsAllowed()
    {
        $mockCache = $this->getMock(
            AbstractFrontend::class,
            array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
            array(),
            '',
            false
        );
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->any())->method('has')->will($this->returnValue(true));
        $mockCache->expects($this->once())->method('requireOnce');
        // Reset the internal cache access tracking variable of extMgm
        // This method is only in the ProxyClass!
        ExtensionManagementUtilityAccessibleProxy::resetExtTablesWasReadFromCacheOnceBoolean();
        ExtensionManagementUtility::loadExtTables(true);
    }

    /////////////////////////////////////////
    // Tests concerning createExtTablesCacheEntry
    /////////////////////////////////////////
    /**
     * @test
     */
    public function createExtTablesCacheEntryWritesCacheEntryWithContentOfLoadedExtensionExtTables()
    {
        $extensionName = $this->getUniqueId('foo');
        $extTablesLocation = PATH_site . 'typo3temp/' . $this->getUniqueId('test_ext_tables') . '.php';
        $this->testFilesToDelete[] = $extTablesLocation;
        $uniqueStringInTables = $this->getUniqueId('foo');
        file_put_contents($extTablesLocation, "<?php\n\n$uniqueStringInTables\n\n?>");
        $GLOBALS['TYPO3_LOADED_EXT'] = array(
            $extensionName => array(
                'ext_tables.php' => $extTablesLocation
            )
        );
        $mockCache = $this->getMock(
            AbstractFrontend::class,
            array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
            array(),
            '',
            false
        );
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->stringContains($uniqueStringInTables), $this->anything());
        ExtensionManagementUtilityAccessibleProxy::createExtTablesCacheEntry();
    }

    /**
     * @test
     */
    public function createExtTablesCacheEntryWritesCacheEntryWithExtensionContentOnlyIfExtTablesExists()
    {
        $extensionName = $this->getUniqueId('foo');
        $GLOBALS['TYPO3_LOADED_EXT'] = array(
            $extensionName => array(),
        );
        $mockCache = $this->getMock(
            AbstractFrontend::class,
            array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
            array(),
            '',
            false
        );
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->once())
            ->method('set')
            ->with($this->anything(), $this->logicalNot($this->stringContains($extensionName)), $this->anything());
        ExtensionManagementUtilityAccessibleProxy::createExtTablesCacheEntry();
    }

    /**
     * @test
     */
    public function createExtTablesCacheEntryWritesCacheEntryWithNoTags()
    {
        $mockCache = $this->getMock(
            AbstractFrontend::class,
            array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
            array(),
            '',
            false
        );
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('getCache'));
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->equalTo(array()));
        $GLOBALS['TYPO3_LOADED_EXT'] = new LoadedExtensionsArray($this->createMockPackageManagerWithMockPackage($this->getUniqueId()));
        ExtensionManagementUtilityAccessibleProxy::createExtTablesCacheEntry();
    }

    /////////////////////////////////////////
    // Tests concerning getExtTablesCacheIdentifier
    /////////////////////////////////////////
    /**
     * @test
     */
    public function getExtTablesCacheIdentifierCreatesSha1WithFourtyCharactersAndPrefix()
    {
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
    public function removeCacheFilesFlushesSystemCaches()
    {
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, array('flushCachesInGroup'));
        $mockCacheManager->expects($this->once())->method('flushCachesInGroup')->with('system');
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        ExtensionManagementUtility::removeCacheFiles();
    }

    /////////////////////////////////////////
    // Tests concerning loadNewTcaColumnsConfigFiles
    /////////////////////////////////////////

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function loadNewTcaColumnsConfigFilesIncludesDefinedDynamicConfigFileIfNoColumnsExist()
    {
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
    public function loadNewTcaColumnsConfigFilesDoesNotIncludeFileIfColumnsExist()
    {
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
    public function getExtensionVersionFaultyDataProvider()
    {
        return array(
            array(''),
            array(0),
            array(new \stdClass()),
            array(true)
        );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @dataProvider getExtensionVersionFaultyDataProvider
     * @param $key
     * @throws \TYPO3\CMS\Core\Package\Exception
     */
    public function getExtensionVersionForFaultyExtensionKeyThrowsException($key)
    {
        ExtensionManagementUtility::getExtensionVersion($key);
    }

    /**
     * @test
     */
    public function getExtensionVersionForNotLoadedExtensionReturnsEmptyString()
    {
        ExtensionManagementUtility::clearExtensionKeyMap();
        $uniqueSuffix = $this->getUniqueId('test');
        $extensionKey = 'unloadedextension' . $uniqueSuffix;
        $this->assertEquals('', ExtensionManagementUtility::getExtensionVersion($extensionKey));
    }

    /**
     * @test
     */
    public function getExtensionVersionForLoadedExtensionReturnsExtensionVersion()
    {
        ExtensionManagementUtility::clearExtensionKeyMap();
        $uniqueSuffix = $this->getUniqueId('test');
        $extensionKey = 'unloadedextension' . $uniqueSuffix;
        $packageMetaData = $this->getMock(MetaData::class, array('getVersion'), array($extensionKey));
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
    public function loadExtensionThrowsExceptionIfExtensionIsLoaded()
    {
        $extensionKey = $this->getUniqueId('test');
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
    public function unloadExtensionThrowsExceptionIfExtensionIsNotLoaded()
    {
        $packageName = $this->getUniqueId('foo');
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMock(PackageManager::class, array('isPackageActive'));
        $packageManager->expects($this->once())
            ->method('isPackageActive')
            ->with($this->equalTo($packageName))
            ->will($this->returnValue(false));
        ExtensionManagementUtility::setPackageManager($packageManager);
        ExtensionManagementUtility::unloadExtension($packageName);
    }

    /**
     * @test
     */
    public function unloadExtensionCallsPackageManagerToDeactivatePackage()
    {
        $packageName = $this->getUniqueId('foo');
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMock(
            PackageManager::class,
            array('isPackageActive', 'deactivatePackage')
        );
        $packageManager->expects($this->any())
            ->method('isPackageActive')
            ->will($this->returnValue(true));
        $packageManager->expects($this->once())
            ->method('deactivatePackage')
            ->with($packageName);
        ExtensionManagementUtility::setPackageManager($packageManager);
        ExtensionManagementUtility::unloadExtension($packageName);
    }

    /////////////////////////////////////////
    // Tests concerning makeCategorizable
    /////////////////////////////////////////
    /**
     * @test
     */
    public function doesMakeCategorizableCallsTheCategoryRegistryWithDefaultFieldName()
    {
        $extensionKey = $this->getUniqueId('extension');
        $tableName = $this->getUniqueId('table');

        /** @var CategoryRegistry|\PHPUnit_Framework_MockObject_MockObject $registryMock */
        $registryMock = $this->getMock(CategoryRegistry::class);
        $registryMock->expects($this->once())->method('add')->with($extensionKey, $tableName, 'categories', array());
        GeneralUtility::setSingletonInstance(CategoryRegistry::class, $registryMock);
        ExtensionManagementUtility::makeCategorizable($extensionKey, $tableName);
    }

    /**
     * @test
     */
    public function doesMakeCategorizableCallsTheCategoryRegistryWithFieldName()
    {
        $extensionKey = $this->getUniqueId('extension');
        $tableName = $this->getUniqueId('table');
        $fieldName = $this->getUniqueId('field');

        /** @var CategoryRegistry|\PHPUnit_Framework_MockObject_MockObject $registryMock */
        $registryMock = $this->getMock(CategoryRegistry::class);
        $registryMock->expects($this->once())->method('add')->with($extensionKey, $tableName, $fieldName, array());
        GeneralUtility::setSingletonInstance(CategoryRegistry::class, $registryMock);
        ExtensionManagementUtility::makeCategorizable($extensionKey, $tableName, $fieldName);
    }

    ///////////////////////////////
    // Tests concerning addPlugin
    ///////////////////////////////

    /**
     * @test
     */
    public function addPluginSetsTcaCorrectlyForGivenExtKeyAsParameter()
    {
        $extKey = 'indexed_search';
        $GLOBALS['TYPO3_LOADED_EXT'] = array();
        $GLOBALS['TYPO3_LOADED_EXT'][$extKey]['ext_icon'] = 'foo.gif';
        $expectedTCA = array(
            array(
                'label',
                $extKey,
                'EXT:' . $extKey . '/foo.gif'
            )
        );
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = array();
        ExtensionManagementUtility::addPlugin(array('label', $extKey), 'list_type', $extKey);
        $this->assertEquals($expectedTCA, $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items']);
    }

    /**
     * @test
     */
    public function addPluginSetsTcaCorrectlyForGivenExtKeyAsGlobal()
    {
        $extKey = 'indexed_search';
        $GLOBALS['TYPO3_LOADED_EXT'] = array();
        $GLOBALS['TYPO3_LOADED_EXT'][$extKey]['ext_icon'] = 'foo.gif';
        $GLOBALS['_EXTKEY'] = $extKey;
        $expectedTCA = array(
            array(
                'label',
                $extKey,
                'EXT:' . $extKey . '/foo.gif'
            )
        );
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = array();
        ExtensionManagementUtility::addPlugin(array('label', $extKey));
        $this->assertEquals($expectedTCA, $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items']);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function addPluginThrowsExceptionForMissingExtkey()
    {
        ExtensionManagementUtility::addPlugin('test');
    }
}
