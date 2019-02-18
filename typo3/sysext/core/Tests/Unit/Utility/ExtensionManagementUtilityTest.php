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
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\MetaData;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher as SignalSlotDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ExtensionManagementUtilityTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var \TYPO3\CMS\Core\Package\PackageManager
     */
    protected $backUpPackageManager;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->backUpPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        ExtensionManagementUtilityAccessibleProxy::setPackageManager($this->backUpPackageManager);
        ExtensionManagementUtilityAccessibleProxy::setCacheManager(null);
        ExtensionManagementUtilityAccessibleProxy::setSignalSlotDispatcher(null);
        parent::tearDown();
    }

    /**
     * @param string $packageKey
     * @param array $packageMethods
     * @return PackageManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockPackageManagerWithMockPackage($packageKey, $packageMethods = ['getPackagePath', 'getPackageKey'])
    {
        $packagePath = Environment::getVarPath() . '/tests/' . $packageKey . '/';
        GeneralUtility::mkdir_deep($packagePath);
        $this->testFilesToDelete[] = $packagePath;
        $package = $this->getMockBuilder(Package::class)
                ->disableOriginalConstructor()
                ->setMethods($packageMethods)
                ->getMock();
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive', 'getPackage', 'getActivePackages'])
            ->disableOriginalConstructor()
            ->getMock();
        $package->expects($this->any())
                ->method('getPackagePath')
                ->will($this->returnValue($packagePath));
        $package->expects($this->any())
                ->method('getPackageKey')
                ->will($this->returnValue($packageKey));
        $packageManager->expects($this->any())
                ->method('isPackageActive')
                ->will($this->returnValueMap([
                    [null, false],
                    [$packageKey, true]
                ]));
        $packageManager->expects($this->any())
                ->method('getPackage')
                ->with($this->equalTo($packageKey))
                ->will($this->returnValue($package));
        $packageManager->expects($this->any())
                ->method('getActivePackages')
                ->will($this->returnValue([$packageKey => $package]));
        return $packageManager;
    }

    ///////////////////////////////
    // Tests concerning isLoaded
    ///////////////////////////////
    /**
     * @test
     */
    public function isLoadedReturnsFalseIfExtensionIsNotLoaded()
    {
        $this->assertFalse(ExtensionManagementUtility::isLoaded($this->getUniqueId('foobar')));
    }

    ///////////////////////////////
    // Tests concerning extPath
    ///////////////////////////////
    /**
     * @test
     */
    public function extPathThrowsExceptionIfExtensionIsNotLoaded()
    {
        $this->expectException(\BadFunctionCallException::class);
        $this->expectExceptionCode(1365429656);

        $packageName = $this->getUniqueId('foo');
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive'])
            ->disableOriginalConstructor()
            ->getMock();
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
                ->setMethods(['getPackagePath'])
                ->getMock();
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive', 'getPackage'])
            ->disableOriginalConstructor()
            ->getMock();
        $package->expects($this->once())
                ->method('getPackagePath')
                ->will($this->returnValue(Environment::getPublicPath() . '/foo/'));
        $packageManager->expects($this->once())
                ->method('isPackageActive')
                ->with($this->equalTo('foo'))
                ->will($this->returnValue(true));
        $packageManager->expects($this->once())
                ->method('getPackage')
                ->with('foo')
                ->will($this->returnValue($package));
        ExtensionManagementUtility::setPackageManager($packageManager);
        $this->assertSame(Environment::getPublicPath() . '/foo/bar.txt', ExtensionManagementUtility::extPath('foo', 'bar.txt'));
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
        $tca = [];
        $tca[$table] = [];
        $tca[$table]['columns'] = [
            'fieldA' => [],
            'fieldC' => []
        ];
        $tca[$table]['types'] = [
            'typeA' => ['showitem' => 'fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1'],
            'typeB' => ['showitem' => 'fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1'],
            'typeC' => ['showitem' => 'fieldC;;paletteD']
        ];
        $tca[$table]['palettes'] = [
            'paletteA' => ['showitem' => 'fieldX, fieldX1, fieldY'],
            'paletteB' => ['showitem' => 'fieldX, fieldX1, fieldY'],
            'paletteC' => ['showitem' => 'fieldX, fieldX1, fieldY'],
            'paletteD' => ['showitem' => 'fieldX, fieldX1, fieldY']
        ];
        return $tca;
    }

    /**
     * Data provider for getClassNamePrefixForExtensionKey.
     *
     * @return array
     */
    public function extensionKeyDataProvider()
    {
        return [
            'Without underscores' => [
                'testkey',
                'tx_testkey'
            ],
            'With underscores' => [
                'this_is_a_test_extension',
                'tx_thisisatestextension'
            ],
            'With user prefix and without underscores' => [
                'user_testkey',
                'user_testkey'
            ],
            'With user prefix and with underscores' => [
                'user_test_key',
                'user_testkey'
            ],
        ];
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
        $GLOBALS['TCA'][$table]['types']['typeD'] = ['showitem' => 'fieldY, --palette--;;standard, fieldZ'];
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:--palette--;;standard');
        // Checking typeD:
        $this->assertEquals('fieldY, --palette--;;standard, newA, newB, fieldA, fieldZ', $GLOBALS['TCA'][$table]['types']['typeD']['showitem']);
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
     * @test
     */
    public function canAddFieldWithPartOfAlreadyExistingFieldname()
    {
        $table = $this->getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'field', 'typeA', 'after:fieldD1');

        $this->assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1, field', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
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
        return [
            'Simple' => [
                'field_b, field_d, field_c',
                'field_a, field_b, field_c',
                'field_d'
            ],
            'with linebreaks' => [
                'field_b, --linebreak--, field_d, --linebreak--, field_c',
                'field_a, field_b, field_c',
                '--linebreak--, field_d, --linebreak--'
            ],
            'with linebreaks in list and insertion list' => [
                'field_b, --linebreak--, field_d, --linebreak--, field_c',
                'field_a, field_b, --linebreak--, field_c',
                '--linebreak--, field_d, --linebreak--'
            ],
        ];
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'fieldX, fieldY',
                    ],
                ],
            ],
        ];
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'fieldX, fieldY',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'fieldX, fieldY, dupeA',
                    ],
                ],
            ],
        ];
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'existingA',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'existingA',
                    ],
                ],
            ],
        ];
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA, --palette--;;palette1',
                    ],
                    'typeB' => [
                        'showitem' => 'fieldA;aLabel, --palette--;;palette2',
                    ],
                ],
                'palettes' => [
                    'palette1' => [
                        'showitem' => 'fieldX',
                    ],
                    'palette2' => [
                        'showitem' => 'fieldY',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA, --palette--;;palette1',
                    ],
                    'typeB' => [
                        'showitem' => 'fieldA;aLabel, --palette--;;palette2',
                    ],
                ],
                'palettes' => [
                    'palette1' => [
                        'showitem' => 'fieldX, newA',
                    ],
                    'palette2' => [
                        'showitem' => 'fieldY, newA',
                    ],
                ],
            ],
        ];
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA, --palette--;;palette1',
                    ],
                ],
                'palettes' => [
                    'palette1' => [
                        'showitem' => 'fieldX',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA, --palette--;;palette1',
                    ],
                ],
                'palettes' => [
                    'palette1' => [
                        'showitem' => 'fieldX, newA, newB',
                    ],
                ],
            ],
        ];
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'existingA, existingB',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'existingA, newA, existingB',
                    ],
                ],
            ],
        ];
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'fieldX, fieldY',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'fieldX, fieldY, newA, newB',
                    ],
                ],
            ],
        ];
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'existingA, existingB',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'existingA, newA, existingB',
                    ],
                ],
            ],
        ];
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'existingA, existingB',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;paletteA',
                    ],
                ],
                'palettes' => [
                    'paletteA' => [
                        'showitem' => 'existingA, existingB, newA, newB',
                    ],
                ],
            ],
        ];
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA, --palette--;;generatedFor-fieldA',
                    ],
                ],
                'palettes' => [
                    'generatedFor-fieldA' => [
                        'showitem' => 'newA',
                    ],
                ],
            ],
        ];
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'fieldA' => [],
                ],
                'types' => [
                    'typeA' => [
                        'showitem' => 'fieldA;labelA, --palette--;;generatedFor-fieldA',
                    ],
                ],
                'palettes' => [
                    'generatedFor-fieldA' => [
                        'showitem' => 'newA',
                    ],
                ],
            ],
        ];
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
        return [
            'normal characters' => [
                'tr0',
                'tr0',
            ],
            'newlines' => [
                "test\n",
                'test',
            ],
            'newlines with carriage return' => [
                "test\r\n",
                'test',
            ],
            'tabs' => [
                "test\t",
                'test',
            ],
            'commas' => [
                'test,',
                'test',
            ],
            'multiple commas with trailing spaces' => [
                "test,,\t, \r\n",
                'test',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider executePositionedStringInsertionTrimsCorrectCharactersDataProvider
     * @param $string
     * @param $expectedResult
     */
    public function executePositionedStringInsertionTrimsCorrectCharacters($string, $expectedResult)
    {
        $extensionManagementUtility = $this->getAccessibleMock(ExtensionManagementUtility::class, ['dummy']);
        $string = $extensionManagementUtility->_call('executePositionedStringInsertion', $string, '');
        $this->assertEquals($expectedResult, $string);
    }

    /////////////////////////////////////////
    // Tests concerning addTcaSelectItem
    /////////////////////////////////////////
    /**
     * @test
     */
    public function addTcaSelectItemThrowsExceptionIfTableIsNotOfTypeString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1303236963);

        ExtensionManagementUtility::addTcaSelectItem([], 'foo', []);
    }

    /**
     * @test
     */
    public function addTcaSelectItemThrowsExceptionIfFieldIsNotOfTypeString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1303236964);

        ExtensionManagementUtility::addTcaSelectItem('foo', [], []);
    }

    /**
     * @test
     */
    public function addTcaSelectItemThrowsExceptionIfRelativeToFieldIsNotOfTypeString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1303236965);

        ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', [], []);
    }

    /**
     * @test
     */
    public function addTcaSelectItemThrowsExceptionIfRelativePositionIsNotOfTypeString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1303236966);

        ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', [], 'foo', []);
    }

    /**
     * @test
     */
    public function addTcaSelectItemThrowsExceptionIfRelativePositionIsNotOneOfValidKeywords()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1303236967);

        ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', [], 'foo', 'not allowed keyword');
    }

    /**
     * @test
     */
    public function addTcaSelectItemThrowsExceptionIfFieldIsNotFoundInTca()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1303237468);

        $GLOBALS['TCA'] = [];
        ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', []);
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
        return [
            'add at end of array' => [
                '',
                '',
                [
                    0 => ['firstElement'],
                    1 => ['matchMe'],
                    2 => ['thirdElement'],
                    3 => ['insertedElement']
                ]
            ],
            'replace element' => [
                'matchMe',
                'replace',
                [
                    0 => ['firstElement'],
                    1 => ['insertedElement'],
                    2 => ['thirdElement']
                ]
            ],
            'add element after' => [
                'matchMe',
                'after',
                [
                    0 => ['firstElement'],
                    1 => ['matchMe'],
                    2 => ['insertedElement'],
                    3 => ['thirdElement']
                ]
            ],
            'add element before' => [
                'matchMe',
                'before',
                [
                    0 => ['firstElement'],
                    1 => ['insertedElement'],
                    2 => ['matchMe'],
                    3 => ['thirdElement']
                ]
            ],
            'add at end if relative position was not found' => [
                'notExistingItem',
                'after',
                [
                    0 => ['firstElement'],
                    1 => ['matchMe'],
                    2 => ['thirdElement'],
                    3 => ['insertedElement']
                ]
            ]
        ];
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
        $GLOBALS['TCA'] = [
            'testTable' => [
                'columns' => [
                    'testField' => [
                        'config' => [
                            'items' => [
                                '0' => ['firstElement'],
                                '1' => ['matchMe'],
                                2 => ['thirdElement']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        ExtensionManagementUtility::addTcaSelectItem('testTable', 'testField', ['insertedElement'], $relativeToField, $relativePosition);
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
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects($this->never())->method('getCache');
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $packageManager = $this->createMockPackageManagerWithMockPackage($this->getUniqueId());
        ExtensionManagementUtility::setPackageManager($packageManager);
        ExtensionManagementUtility::loadExtLocalconf(false);
    }

    /**
     * @test
     */
    public function loadExtLocalconfRequiresCacheFileIfExistsAndCachingIsAllowed()
    {
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->any())->method('has')->will($this->returnValue(true));
        $mockCache->expects($this->once())->method('require');
        ExtensionManagementUtility::loadExtLocalconf(true);
    }

    /////////////////////////////////////////
    // Tests concerning loadSingleExtLocalconfFiles
    /////////////////////////////////////////
    /**
     * @test
     */
    public function loadSingleExtLocalconfFilesRequiresExtLocalconfFileRegisteredInGlobalTypo3LoadedExt()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1340559079);

        $extensionName = $this->getUniqueId('foo');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        $extLocalconfLocation = $packageManager->getPackage($extensionName)->getPackagePath() . 'ext_localconf.php';
        file_put_contents($extLocalconfLocation, "<?php\n\nthrow new RuntimeException('', 1340559079);\n\n?>");
        ExtensionManagementUtility::setPackageManager($packageManager);
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
        return [
            'can add new main module if none exists' => [
                'top',
                '',
                'newModule'
            ],
            'can add new sub module if no position specified' => [
                '',
                'some,modules',
                'some,modules,newModule'
            ],
            'can add new sub module to top of module' => [
                'top',
                'some,modules',
                'newModule,some,modules'
            ],
            'can add new sub module if bottom of module' => [
                'bottom',
                'some,modules',
                'some,modules,newModule'
            ],
            'can add new sub module before specified sub module' => [
                'before:modules',
                'some,modules',
                'some,newModule,modules'
            ],
            'can add new sub module after specified sub module' => [
                'after:some',
                'some,modules',
                'some,newModule,modules'
            ],
            'can add new sub module at the bottom if specified sub module to add before does not exist' => [
                'before:modules',
                'some,otherModules',
                'some,otherModules,newModule'
            ],
            'can add new sub module at the bottom if specified sub module to add after does not exist' => [
                'after:some',
                'someOther,modules',
                'someOther,modules,newModule'
            ],
        ];
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
        ExtensionManagementUtility::setPackageManager($packageManager);
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->stringContains($uniqueStringInLocalconf), $this->anything());
        ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry($mockCache);
    }

    /**
     * @test
     */
    public function createExtLocalconfCacheEntryWritesCacheEntryWithExtensionContentOnlyIfExtLocalconfExists()
    {
        $extensionName = $this->getUniqueId('foo');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        ExtensionManagementUtility::setPackageManager($packageManager);
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockCache->expects($this->once())
            ->method('set')
            ->with($this->anything(), $this->logicalNot($this->stringContains($extensionName)), $this->anything());
        ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry($mockCache);
    }

    /**
     * @test
     */
    public function createExtLocalconfCacheEntryWritesCacheEntryWithNoTags()
    {
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->equalTo([]));
        $packageManager = $this->createMockPackageManagerWithMockPackage($this->getUniqueId());
        ExtensionManagementUtility::setPackageManager($packageManager);
        ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry($mockCache);
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
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects($this->never())->method('getCache');
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        ExtensionManagementUtilityAccessibleProxy::loadBaseTca(false);
    }

    /**
     * @test
     */
    public function loadBaseTcaRequiresCacheFileIfExistsAndCachingIsAllowed()
    {
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockCache->expects($this->once())->method('require')->willReturn(['tca' => [], 'categoryRegistry' => \serialize(CategoryRegistry::getInstance())]);
        ExtensionManagementUtilityAccessibleProxy::loadBaseTca(true, $mockCache);
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
        ExtensionManagementUtility::setPackageManager($packageManager);
        $uniqueTableName = $this->getUniqueId('table_name_');
        $uniqueStringInTableConfiguration = $this->getUniqueId('table_configuration_');
        $tableConfiguration = '<?php return array(\'foo\' => \'' . $uniqueStringInTableConfiguration . '\'); ?>';
        file_put_contents($packagePath . 'Configuration/TCA/' . $uniqueTableName . '.php', $tableConfiguration);
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->once())->method('require')->will($this->returnValue(false));
        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->stringContains($uniqueStringInTableConfiguration), $this->anything());

        $mockSignalSlotDispatcher = $this->createMock(SignalSlotDispatcher::class);
        $mockSignalSlotDispatcher->expects($this->once())->method('dispatch')->with($this->anything(), $this->anything(), $this->isType('array'))->will($this->returnArgument(2));
        ExtensionManagementUtilityAccessibleProxy::setSignalSlotDispatcher($mockSignalSlotDispatcher);

        ExtensionManagementUtility::loadBaseTca(true);
    }

    /**
     * @test
     */
    public function loadBaseTcaWritesCacheEntryWithNoTags()
    {
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->once())->method('require')->will($this->returnValue(false));
        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->equalTo([]));
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
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects($this->never())->method('getCache');
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $packageManager = $this->createMockPackageManagerWithMockPackage($this->getUniqueId());
        ExtensionManagementUtility::setPackageManager($packageManager);
        ExtensionManagementUtility::loadExtLocalconf(false);
    }

    /**
     * @test
     */
    public function loadExtTablesRequiresCacheFileIfExistsAndCachingIsAllowed()
    {
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->any())->method('has')->will($this->returnValue(true));
        $mockCache->expects($this->once())->method('require');
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
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        $extensionPath = $packageManager->getPackage($extensionName)->getPackagePath();
        $extTablesLocation = $extensionPath . 'ext_tables.php';
        $uniqueStringInTables = $this->getUniqueId('foo');
        file_put_contents($extTablesLocation, "<?php\n\n$uniqueStringInTables\n\n?>");
        ExtensionManagementUtility::setPackageManager($packageManager);
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
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
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        ExtensionManagementUtility::setPackageManager($packageManager);
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
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
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->equalTo([]));
        $packageManager = $this->createMockPackageManagerWithMockPackage($this->getUniqueId());
        ExtensionManagementUtility::setPackageManager($packageManager);
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
    // Tests concerning getExtensionVersion
    /////////////////////////////////////////
    /**
     * Data provider for negative getExtensionVersion() tests.
     *
     * @return array
     */
    public function getExtensionVersionFaultyDataProvider()
    {
        return [
            [''],
            [0],
            [new \stdClass()],
            [true]
        ];
    }

    /**
     * @test
     * @dataProvider getExtensionVersionFaultyDataProvider
     * @param $key
     * @throws \TYPO3\CMS\Core\Package\Exception
     */
    public function getExtensionVersionForFaultyExtensionKeyThrowsException($key)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1294586096);

        ExtensionManagementUtility::getExtensionVersion($key);
    }

    /**
     * @test
     */
    public function getExtensionVersionForNotLoadedExtensionReturnsEmptyString()
    {
        $uniqueSuffix = $this->getUniqueId('test');
        $extensionKey = 'unloadedextension' . $uniqueSuffix;
        $this->assertEquals('', ExtensionManagementUtility::getExtensionVersion($extensionKey));
    }

    /**
     * @test
     */
    public function getExtensionVersionForLoadedExtensionReturnsExtensionVersion()
    {
        $uniqueSuffix = $this->getUniqueId('test');
        $extensionKey = 'unloadedextension' . $uniqueSuffix;
        $packageMetaData = $this->getMockBuilder(MetaData::class)
            ->setMethods(['getVersion'])
            ->setConstructorArgs([$extensionKey])
            ->getMock();
        $packageMetaData->expects($this->any())->method('getVersion')->will($this->returnValue('1.2.3'));
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionKey, ['getPackagePath', 'getPackageKey', 'getPackageMetaData']);
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
     */
    public function loadExtensionThrowsExceptionIfExtensionIsLoaded()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1342345486);

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
     */
    public function unloadExtensionThrowsExceptionIfExtensionIsNotLoaded()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1342345487);

        $packageName = $this->getUniqueId('foo');
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive'])
            ->disableOriginalConstructor()
            ->getMock();
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
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive', 'deactivatePackage'])
            ->disableOriginalConstructor()
            ->getMock();
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
        $registryMock = $this->getMockBuilder(CategoryRegistry::class)->getMock();
        $registryMock->expects($this->once())->method('add')->with($extensionKey, $tableName, 'categories', []);
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
        $registryMock = $this->getMockBuilder(CategoryRegistry::class)->getMock();
        $registryMock->expects($this->once())->method('add')->with($extensionKey, $tableName, $fieldName, []);
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
        $expectedTCA = [
            [
                'label',
                $extKey,
                'EXT:' . $extKey . '/Resources/Public/Icons/Extension.png'
            ]
        ];
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        ExtensionManagementUtility::addPlugin(['label', $extKey], 'list_type', $extKey);
        $this->assertEquals($expectedTCA, $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items']);
    }

    /**
     * @test
     */
    public function addPluginThrowsExceptionForMissingExtkey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1404068038);

        ExtensionManagementUtility::addPlugin('test');
    }
}
