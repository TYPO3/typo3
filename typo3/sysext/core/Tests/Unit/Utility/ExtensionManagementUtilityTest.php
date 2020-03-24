<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
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
use TYPO3\CMS\Core\Utility\StringUtility;
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
    protected function setUp(): void
    {
        parent::setUp();
        $this->backUpPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
    }

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        ExtensionManagementUtilityAccessibleProxy::setPackageManager($this->backUpPackageManager);
        ExtensionManagementUtilityAccessibleProxy::setCacheManager(null);
        parent::tearDown();
    }

    /**
     * @param string $packageKey
     * @param array $packageMethods
     * @return PackageManager|\PHPUnit\Framework\MockObject\MockObject
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
        $package->expects(self::any())
                ->method('getPackagePath')
                ->willReturn($packagePath);
        $package->expects(self::any())
                ->method('getPackageKey')
                ->willReturn($packageKey);
        $packageManager->expects(self::any())
                ->method('isPackageActive')
                ->willReturnMap([
                    [null, false],
                    [$packageKey, true]
                ]);
        $packageManager->expects(self::any())
                ->method('getPackage')
                ->with(self::equalTo($packageKey))
                ->willReturn($package);
        $packageManager->expects(self::any())
                ->method('getActivePackages')
                ->willReturn([$packageKey => $package]);
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
        self::assertFalse(ExtensionManagementUtility::isLoaded(StringUtility::getUniqueId('foobar')));
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

        $packageName = StringUtility::getUniqueId('foo');
        /** @var PackageManager|\PHPUnit\Framework\MockObject\MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive'])
            ->disableOriginalConstructor()
            ->getMock();
        $packageManager->expects(self::once())
                ->method('isPackageActive')
                ->with(self::equalTo($packageName))
                ->willReturn(false);
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
        /** @var PackageManager|\PHPUnit\Framework\MockObject\MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive', 'getPackage'])
            ->disableOriginalConstructor()
            ->getMock();
        $package->expects(self::once())
                ->method('getPackagePath')
                ->willReturn(Environment::getPublicPath() . '/foo/');
        $packageManager->expects(self::once())
                ->method('isPackageActive')
                ->with(self::equalTo('foo'))
                ->willReturn(true);
        $packageManager->expects(self::once())
                ->method('getPackage')
                ->with('foo')
                ->willReturn($package);
        ExtensionManagementUtility::setPackageManager($packageManager);
        self::assertSame(Environment::getPublicPath() . '/foo/bar.txt', ExtensionManagementUtility::extPath('foo', 'bar.txt'));
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
        self::assertSame($expectedPrefix, ExtensionManagementUtility::getCN($extensionName));
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
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'before:fieldD');
        // Checking typeA:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, newA, newB, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
        // Checking typeB:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, newA, newB, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
    }

    /**
     * Tests whether fields can be add to all TCA types and duplicate fields are considered.
     *
     * @test
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    public function canAddFieldsToAllTCATypesAfterExistingOnes()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:fieldC');
        // Checking typeA:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, newA, newB, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
        // Checking typeB:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, newA, newB, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
    }

    /**
     * Tests whether fields can be add to all TCA types and duplicate fields are considered.
     *
     * @test
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    public function canAddFieldsToAllTCATypesRespectsPalettes()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        $GLOBALS['TCA'][$table]['types']['typeD'] = ['showitem' => 'fieldY, --palette--;;standard, fieldZ'];
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:--palette--;;standard');
        // Checking typeD:
        self::assertEquals('fieldY, --palette--;;standard, newA, newB, fieldA, fieldZ', $GLOBALS['TCA'][$table]['types']['typeD']['showitem']);
    }

    /**
     * Tests whether fields can be add to all TCA types and fields in pallets are respected.
     *
     * @test
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    public function canAddFieldsToAllTCATypesRespectsPositionFieldInPalette()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:fieldX1');
        // Checking typeA:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, newA, newB, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
    }

    /**
     * Tests whether fields can be add to a TCA type before existing ones
     *
     * @test
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    public function canAddFieldsToTCATypeBeforeExistingOnes()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', 'typeA', 'before:fieldD');
        // Checking typeA:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, newA, newB, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
        // Checking typeB:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
    }

    /**
     * Tests whether fields can be add to a TCA type after existing ones
     *
     * @test
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    public function canAddFieldsToTCATypeAfterExistingOnes()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', 'typeA', 'after:fieldC');
        // Checking typeA:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, newA, newB, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
        // Checking typeB:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
    }

    /**
     * @test
     */
    public function canAddFieldWithPartOfAlreadyExistingFieldname()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'field', 'typeA', 'after:fieldD1');

        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1, field', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
    }

    /**
     * Test whether replacing other TCA fields works as promised
     *
     * @test
     * @see ExtensionManagementUtility::addFieldsToAllPalettesOfField()
     */
    public function canAddFieldsToTCATypeAndReplaceExistingOnes()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        $typesBefore = $GLOBALS['TCA'][$table]['types'];
        ExtensionManagementUtility::addToAllTCAtypes($table, 'fieldZ', '', 'replace:fieldX');
        self::assertEquals($typesBefore, $GLOBALS['TCA'][$table]['types'], 'It\'s wrong that the "types" array changes here - the replaced field is only on palettes');
        // unchanged because the palette is not used
        self::assertEquals('fieldX, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
        self::assertEquals('fieldX, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
        // changed
        self::assertEquals('fieldZ, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
        self::assertEquals('fieldZ, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
    }

    /**
     * @test
     */
    public function addToAllTCAtypesReplacesExistingOnes()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        $typesBefore = $GLOBALS['TCA'][$table]['types'];
        ExtensionManagementUtility::addToAllTCAtypes($table, 'fieldX, --palette--;;foo', '', 'replace:fieldX');
        self::assertEquals($typesBefore, $GLOBALS['TCA'][$table]['types'], 'It\'s wrong that the "types" array changes here - the replaced field is only on palettes');
        // unchanged because the palette is not used
        self::assertEquals('fieldX, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
        self::assertEquals('fieldX, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteB']['showitem']);
        // changed
        self::assertEquals('fieldX, --palette--;;foo, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteC']['showitem']);
        self::assertEquals('fieldX, --palette--;;foo, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteD']['showitem']);
    }

    /**
     * Tests whether fields can be added to a palette before existing elements.
     *
     * @test
     * @see ExtensionManagementUtility::addFieldsToPalette()
     */
    public function canAddFieldsToPaletteBeforeExistingOnes()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'before:fieldY');
        self::assertEquals('fieldX, fieldX1, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
    }

    /**
     * Tests whether fields can be added to a palette after existing elements.
     *
     * @test
     * @see ExtensionManagementUtility::addFieldsToPalette()
     */
    public function canAddFieldsToPaletteAfterExistingOnes()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:fieldX');
        self::assertEquals('fieldX, newA, newB, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
    }

    /**
     * Tests whether fields can be added to a palette after a not existing elements.
     *
     * @test
     * @see ExtensionManagementUtility::addFieldsToPalette()
     */
    public function canAddFieldsToPaletteAfterNotExistingOnes()
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:' . StringUtility::getUniqueId('notExisting'));
        self::assertEquals('fieldX, fieldX1, fieldY, newA, newB', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
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
        self::assertSame($expected, $result);
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
        self::assertEquals($expected, $GLOBALS['TCA']);
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
        self::assertEquals($expected, $GLOBALS['TCA']);
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
        self::assertEquals($expected, $GLOBALS['TCA']);
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
        self::assertEquals($expected, $GLOBALS['TCA']);
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
        self::assertEquals($expected, $GLOBALS['TCA']);
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
        self::assertEquals($expected, $GLOBALS['TCA']);
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
        self::assertEquals($expected, $GLOBALS['TCA']);
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
        self::assertEquals($expected, $GLOBALS['TCA']);
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
        self::assertEquals($expected, $GLOBALS['TCA']);
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
        self::assertEquals($expected, $GLOBALS['TCA']);
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
        self::assertEquals($expected, $GLOBALS['TCA']);
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
        self::assertEquals($expectedResult, $string);
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
        self::assertEquals($expectedResultArray, $GLOBALS['TCA']['testTable']['columns']['testField']['config']['items']);
    }

    /////////////////////////////////////////
    // Tests concerning loadExtLocalconf
    /////////////////////////////////////////
    /**
     * @test
     */
    public function loadExtLocalconfDoesNotReadFromCacheIfCachingIsDenied()
    {
        /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects(self::never())->method('getCache');
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $packageManager = $this->createMockPackageManagerWithMockPackage(StringUtility::getUniqueId());
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

        /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects(self::any())->method('getCache')->willReturn($mockCache);
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects(self::any())->method('has')->willReturn(true);
        $mockCache->expects(self::once())->method('require');
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

        $extensionName = StringUtility::getUniqueId('foo');
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

        self::assertTrue(isset($GLOBALS['TBE_MODULES'][$mainModule]));
        self::assertEquals($expected, $GLOBALS['TBE_MODULES'][$mainModule]);
    }

    /**
     * @test
     * @dataProvider addModulePositionTestsDataProvider
     * @param $position
     * @param $existing
     * @param $expected
     */
    public function addModuleCanAddMainModule($position, $existing, $expected)
    {
        $mainModule = 'newModule';
        if ($existing) {
            foreach (explode(',', $existing) as $existingMainModule) {
                $GLOBALS['TBE_MODULES'][$existingMainModule] = '';
            }
        }

        ExtensionManagementUtility::addModule($mainModule, '', $position);

        self::assertTrue(isset($GLOBALS['TBE_MODULES'][$mainModule]));
        unset($GLOBALS['TBE_MODULES']['_configuration']);
        unset($GLOBALS['TBE_MODULES']['_navigationComponents']);
        self::assertEquals($expected, implode(',', array_keys($GLOBALS['TBE_MODULES'])));
    }

    /////////////////////////////////////////
    // Tests concerning createExtLocalconfCacheEntry
    /////////////////////////////////////////
    /**
     * @test
     */
    public function createExtLocalconfCacheEntryWritesCacheEntryWithContentOfLoadedExtensionExtLocalconf()
    {
        $extensionName = StringUtility::getUniqueId('foo');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        $extLocalconfLocation = $packageManager->getPackage($extensionName)->getPackagePath() . 'ext_localconf.php';
        $uniqueStringInLocalconf = StringUtility::getUniqueId('foo');
        file_put_contents($extLocalconfLocation, "<?php\n\n" . $uniqueStringInLocalconf . "\n\n?>");
        ExtensionManagementUtility::setPackageManager($packageManager);
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockCache->expects(self::once())->method('set')->with(self::anything(), self::stringContains($uniqueStringInLocalconf), self::anything());
        ExtensionManagementUtilityAccessibleProxy::createExtLocalconfCacheEntry($mockCache);
    }

    /**
     * @test
     */
    public function createExtLocalconfCacheEntryWritesCacheEntryWithExtensionContentOnlyIfExtLocalconfExists()
    {
        $extensionName = StringUtility::getUniqueId('foo');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        ExtensionManagementUtility::setPackageManager($packageManager);
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockCache->expects(self::once())
            ->method('set')
            ->with(self::anything(), self::logicalNot(self::stringContains($extensionName)), self::anything());
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
        $mockCache->expects(self::once())->method('set')->with(self::anything(), self::anything(), self::equalTo([]));
        $packageManager = $this->createMockPackageManagerWithMockPackage(StringUtility::getUniqueId());
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
        self::assertStringStartsWith($prefix, $identifier);
        $sha1 = str_replace($prefix, '', $identifier);
        self::assertEquals(40, strlen($sha1));
    }

    /////////////////////////////////////////
    // Tests concerning loadBaseTca
    /////////////////////////////////////////

    /**
     * @test
     */
    public function loadBaseTcaDoesNotReadFromCacheIfCachingIsDenied()
    {
        /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects(self::never())->method('getCache');
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

        $mockCache->expects(self::once())->method('require')->willReturn(['tca' => [], 'categoryRegistry' => \serialize(CategoryRegistry::getInstance())]);
        ExtensionManagementUtilityAccessibleProxy::loadBaseTca(true, $mockCache);
    }

    /**
     * @test
     */
    public function loadBaseTcaCreatesCacheFileWithContentOfAnExtensionsConfigurationTcaPhpFile()
    {
        $extensionName = StringUtility::getUniqueId('test_baseTca_');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        $packagePath = $packageManager->getPackage($extensionName)->getPackagePath();
        GeneralUtility::mkdir($packagePath);
        GeneralUtility::mkdir($packagePath . 'Configuration/');
        GeneralUtility::mkdir($packagePath . 'Configuration/TCA/');
        ExtensionManagementUtility::setPackageManager($packageManager);
        $uniqueTableName = StringUtility::getUniqueId('table_name_');
        $uniqueStringInTableConfiguration = StringUtility::getUniqueId('table_configuration_');
        $tableConfiguration = '<?php return array(\'foo\' => \'' . $uniqueStringInTableConfiguration . '\'); ?>';
        file_put_contents($packagePath . 'Configuration/TCA/' . $uniqueTableName . '.php', $tableConfiguration);
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects(self::any())->method('getCache')->willReturn($mockCache);
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects(self::once())->method('require')->willReturn(false);
        $mockCache->expects(self::once())->method('set')->with(self::anything(), self::stringContains($uniqueStringInTableConfiguration), self::anything());

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->shouldBeCalled()->willReturnArgument(0);
        ExtensionManagementUtility::setEventDispatcher($eventDispatcher->reveal());

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

        /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects(self::any())->method('getCache')->willReturn($mockCache);
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects(self::once())->method('require')->willReturn(false);
        $mockCache->expects(self::once())->method('set')->with(self::anything(), self::anything(), self::equalTo([]));
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
        self::assertStringStartsWith($prefix, $identifier);
        $sha1 = str_replace($prefix, '', $identifier);
        self::assertEquals(40, strlen($sha1));
    }

    /////////////////////////////////////////
    // Tests concerning loadExtTables
    /////////////////////////////////////////
    /**
     * @test
     */
    public function loadExtTablesDoesNotReadFromCacheIfCachingIsDenied()
    {
        /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects(self::never())->method('getCache');
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $packageManager = $this->createMockPackageManagerWithMockPackage(StringUtility::getUniqueId());
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

        /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects(self::any())->method('getCache')->willReturn($mockCache);
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects(self::any())->method('has')->willReturn(true);
        $mockCache->expects(self::once())->method('require');
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
        $extensionName = StringUtility::getUniqueId('foo');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        $extensionPath = $packageManager->getPackage($extensionName)->getPackagePath();
        $extTablesLocation = $extensionPath . 'ext_tables.php';
        $uniqueStringInTables = StringUtility::getUniqueId('foo');
        file_put_contents($extTablesLocation, "<?php\n\n$uniqueStringInTables\n\n?>");
        ExtensionManagementUtility::setPackageManager($packageManager);
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects(self::any())->method('getCache')->willReturn($mockCache);
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects(self::once())->method('set')->with(self::anything(), self::stringContains($uniqueStringInTables), self::anything());
        ExtensionManagementUtilityAccessibleProxy::createExtTablesCacheEntry();
    }

    /**
     * @test
     */
    public function createExtTablesCacheEntryWritesCacheEntryWithExtensionContentOnlyIfExtTablesExists()
    {
        $extensionName = StringUtility::getUniqueId('foo');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionName);
        ExtensionManagementUtility::setPackageManager($packageManager);
        $mockCache = $this->getMockBuilder(PhpFrontend::class)
            ->setMethods(['getIdentifier', 'set', 'get', 'has', 'remove', 'flush', 'flushByTag', 'require'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects(self::any())->method('getCache')->willReturn($mockCache);
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects(self::once())
            ->method('set')
            ->with(self::anything(), self::logicalNot(self::stringContains($extensionName)), self::anything());
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

        /** @var CacheManager|\PHPUnit\Framework\MockObject\MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['getCache'])
            ->getMock();
        $mockCacheManager->expects(self::any())->method('getCache')->willReturn($mockCache);
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        $mockCache->expects(self::once())->method('set')->with(self::anything(), self::anything(), self::equalTo([]));
        $packageManager = $this->createMockPackageManagerWithMockPackage(StringUtility::getUniqueId());
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
        self::assertStringStartsWith($prefix, $identifier);
        $sha1 = str_replace($prefix, '', $identifier);
        self::assertEquals(40, strlen($sha1));
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
        $uniqueSuffix = StringUtility::getUniqueId('test');
        $extensionKey = 'unloadedextension' . $uniqueSuffix;
        self::assertEquals('', ExtensionManagementUtility::getExtensionVersion($extensionKey));
    }

    /**
     * @test
     */
    public function getExtensionVersionForLoadedExtensionReturnsExtensionVersion()
    {
        $uniqueSuffix = StringUtility::getUniqueId('test');
        $extensionKey = 'unloadedextension' . $uniqueSuffix;
        $packageMetaData = $this->getMockBuilder(MetaData::class)
            ->setMethods(['getVersion'])
            ->setConstructorArgs([$extensionKey])
            ->getMock();
        $packageMetaData->expects(self::any())->method('getVersion')->willReturn('1.2.3');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionKey, ['getPackagePath', 'getPackageKey', 'getPackageMetaData']);
        /** @var \PHPUnit\Framework\MockObject\MockObject $package */
        $package = $packageManager->getPackage($extensionKey);
        $package->expects(self::any())
                ->method('getPackageMetaData')
                ->willReturn($packageMetaData);
        ExtensionManagementUtility::setPackageManager($packageManager);
        self::assertEquals('1.2.3', ExtensionManagementUtility::getExtensionVersion($extensionKey));
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

        $extensionKey = StringUtility::getUniqueId('test');
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

        $packageName = StringUtility::getUniqueId('foo');
        /** @var PackageManager|\PHPUnit\Framework\MockObject\MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive'])
            ->disableOriginalConstructor()
            ->getMock();
        $packageManager->expects(self::once())
            ->method('isPackageActive')
            ->with(self::equalTo($packageName))
            ->willReturn(false);
        ExtensionManagementUtility::setPackageManager($packageManager);
        ExtensionManagementUtility::unloadExtension($packageName);
    }

    /**
     * @test
     */
    public function unloadExtensionCallsPackageManagerToDeactivatePackage()
    {
        $packageName = StringUtility::getUniqueId('foo');
        /** @var PackageManager|\PHPUnit\Framework\MockObject\MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive', 'deactivatePackage'])
            ->disableOriginalConstructor()
            ->getMock();
        $packageManager->expects(self::any())
            ->method('isPackageActive')
            ->willReturn(true);
        $packageManager->expects(self::once())
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
        $extensionKey = StringUtility::getUniqueId('extension');
        $tableName = StringUtility::getUniqueId('table');

        /** @var CategoryRegistry|\PHPUnit\Framework\MockObject\MockObject $registryMock */
        $registryMock = $this->getMockBuilder(CategoryRegistry::class)->getMock();
        $registryMock->expects(self::once())->method('add')->with($extensionKey, $tableName, 'categories', []);
        GeneralUtility::setSingletonInstance(CategoryRegistry::class, $registryMock);
        ExtensionManagementUtility::makeCategorizable($extensionKey, $tableName);
    }

    /**
     * @test
     */
    public function doesMakeCategorizableCallsTheCategoryRegistryWithFieldName()
    {
        $extensionKey = StringUtility::getUniqueId('extension');
        $tableName = StringUtility::getUniqueId('table');
        $fieldName = StringUtility::getUniqueId('field');

        /** @var CategoryRegistry|\PHPUnit\Framework\MockObject\MockObject $registryMock */
        $registryMock = $this->getMockBuilder(CategoryRegistry::class)->getMock();
        $registryMock->expects(self::once())->method('add')->with($extensionKey, $tableName, $fieldName, []);
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
                'EXT:' . $extKey . '/Resources/Public/Icons/Extension.png',
                'default'
            ]
        ];
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        ExtensionManagementUtility::addPlugin(['label', $extKey], 'list_type', $extKey);
        self::assertEquals($expectedTCA, $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items']);
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

    public function addTcaSelectItemGroupAddsGroupDataProvider()
    {
        return [
            'add the first group' => [
                'my_group',
                'my_group_label',
                null,
                null,
                [
                    'my_group' => 'my_group_label'
                ]
            ],
            'add a new group at the bottom' => [
                'my_group',
                'my_group_label',
                'bottom',
                [
                    'default' => 'default_label'
                ],
                [
                    'default' => 'default_label',
                    'my_group' => 'my_group_label'
                ]
            ],
            'add a new group at the top' => [
                'my_group',
                'my_group_label',
                'top',
                [
                    'default' => 'default_label'
                ],
                [
                    'my_group' => 'my_group_label',
                    'default' => 'default_label'
                ]
            ],
            'add a new group after an existing group' => [
                'my_group',
                'my_group_label',
                'after:default',
                [
                    'default' => 'default_label',
                    'special' => 'special_label'
                ],
                [
                    'default' => 'default_label',
                    'my_group' => 'my_group_label',
                    'special' => 'special_label'
                ]
            ],
            'add a new group before an existing group' => [
                'my_group',
                'my_group_label',
                'before:default',
                [
                    'default' => 'default_label',
                    'special' => 'special_label'
                ],
                [
                    'my_group' => 'my_group_label',
                    'default' => 'default_label',
                    'special' => 'special_label'
                ]
            ],
            'add a new group after a non-existing group moved to bottom' => [
                'my_group',
                'my_group_label',
                'after:default2',
                [
                    'default' => 'default_label',
                    'special' => 'special_label'
                ],
                [
                    'default' => 'default_label',
                    'special' => 'special_label',
                    'my_group' => 'my_group_label',
                ]
            ],
            'add a new group which already exists does nothing' => [
                'my_group',
                'my_group_label',
                'does-not-matter',
                [
                    'default' => 'default_label',
                    'my_group' => 'existing_label',
                    'special' => 'special_label'
                ],
                [
                    'default' => 'default_label',
                    'my_group' => 'existing_label',
                    'special' => 'special_label'
                ]
            ],
        ];
    }

    /**
     * @test
     * @param string $groupId
     * @param string $groupLabel
     * @param string $position
     * @param array|null $existingGroups
     * @param array $expectedGroups
     * @dataProvider addTcaSelectItemGroupAddsGroupDataProvider
     */
    public function addTcaSelectItemGroupAddsGroup(string $groupId, string $groupLabel, ?string $position, ?array $existingGroups, array $expectedGroups)
    {
        $GLOBALS['TCA']['tt_content']['columns']['CType']['config'] = [];
        if (is_array($existingGroups)) {
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups'] = $existingGroups;
        }
        ExtensionManagementUtility::addTcaSelectItemGroup('tt_content', 'CType', $groupId, $groupLabel, $position);
        self::assertEquals($expectedGroups, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups']);
    }
}
