<?php

declare(strict_types=1);

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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\MetaData;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtensionManagementUtilityTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;
    protected ?PackageManager $backUpPackageManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backUpPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
    }

    protected function tearDown(): void
    {
        ExtensionManagementUtilityAccessibleProxy::setPackageManager($this->backUpPackageManager);
        parent::tearDown();
    }

    protected function createMockPackageManagerWithMockPackage(string $packageKey, array $packageMethods = ['getPackagePath', 'getPackageKey']): MockObject&PackageManager
    {
        $packagePath = Environment::getVarPath() . '/tests/' . $packageKey . '/';
        GeneralUtility::mkdir_deep($packagePath);
        $this->testFilesToDelete[] = $packagePath;
        $package = $this->getMockBuilder(Package::class)
                ->disableOriginalConstructor()
                ->onlyMethods($packageMethods)
                ->getMock();
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods(['isPackageActive', 'getPackage', 'getActivePackages'])
            ->disableOriginalConstructor()
            ->getMock();
        $package
                ->method('getPackagePath')
                ->willReturn($packagePath);
        $package
                ->method('getPackageKey')
                ->willReturn($packageKey);
        $packageManager
                ->method('isPackageActive')
                ->willReturnMap([
                    [null, false],
                    [$packageKey, true],
                ]);
        $packageManager
                ->method('getPackage')
                ->with(self::equalTo($packageKey))
                ->willReturn($package);
        $packageManager
                ->method('getActivePackages')
                ->willReturn([$packageKey => $package]);
        return $packageManager;
    }

    ///////////////////////////////
    // Tests concerning isLoaded
    ///////////////////////////////
    #[Test]
    public function isLoadedReturnsFalseIfExtensionIsNotLoaded(): void
    {
        self::assertFalse(ExtensionManagementUtility::isLoaded(StringUtility::getUniqueId('foobar')));
    }

    ///////////////////////////////
    // Tests concerning extPath
    ///////////////////////////////
    #[Test]
    public function extPathThrowsExceptionIfExtensionIsNotLoaded(): void
    {
        $this->expectException(\BadFunctionCallException::class);
        $this->expectExceptionCode(1365429656);

        $packageName = StringUtility::getUniqueId('foo');
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods(['isPackageActive'])
            ->disableOriginalConstructor()
            ->getMock();
        $packageManager->expects(self::once())
                ->method('isPackageActive')
                ->with(self::equalTo($packageName))
                ->willReturn(false);
        ExtensionManagementUtility::setPackageManager($packageManager);
        ExtensionManagementUtility::extPath($packageName);
    }

    #[Test]
    public function extPathAppendsScriptNameToPath(): void
    {
        $package = $this->getMockBuilder(Package::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getPackagePath'])
                ->getMock();
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods(['isPackageActive', 'getPackage'])
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
    private function generateTCAForTable(string $table): array
    {
        $tca = [];
        $tca[$table] = [];
        $tca[$table]['columns'] = [
            'fieldA' => [],
            'fieldC' => [],
        ];
        $tca[$table]['types'] = [
            'typeA' => ['showitem' => 'fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1'],
            'typeB' => ['showitem' => 'fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1'],
            'typeC' => ['showitem' => 'fieldC;;paletteD'],
        ];
        $tca[$table]['palettes'] = [
            'paletteA' => ['showitem' => 'fieldX, fieldX1, fieldY'],
            'paletteB' => ['showitem' => 'fieldX, fieldX1, fieldY'],
            'paletteC' => ['showitem' => 'fieldX, fieldX1, fieldY'],
            'paletteD' => ['showitem' => 'fieldX, fieldX1, fieldY'],
        ];
        return $tca;
    }

    /**
     * Data provider for getClassNamePrefixForExtensionKey.
     */
    public static function extensionKeyDataProvider(): array
    {
        return [
            'Without underscores' => [
                'testkey',
                'tx_testkey',
            ],
            'With underscores' => [
                'this_is_a_test_extension',
                'tx_thisisatestextension',
            ],
            'With user prefix and without underscores' => [
                'user_testkey',
                'user_testkey',
            ],
            'With user prefix and with underscores' => [
                'user_test_key',
                'user_testkey',
            ],
        ];
    }

    #[DataProvider('extensionKeyDataProvider')]
    #[Test]
    public function getClassNamePrefixForExtensionKey(string $extensionName, string $expectedPrefix): void
    {
        self::assertSame($expectedPrefix, ExtensionManagementUtility::getCN($extensionName));
    }

    //////////////////////////////////////
    // Tests concerning addToAllTCAtypes
    //////////////////////////////////////
    /**
     * Tests whether fields can be added to all TCA types and duplicate fields are considered.
     *
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    #[Test]
    public function canAddFieldsToAllTCATypesBeforeExistingOnes(): void
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
     * Tests whether fields can be added to all TCA types and duplicate fields are considered.
     *
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    #[Test]
    public function canAddFieldsToAllTCATypesAfterExistingOnes(): void
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
     * Tests whether fields can be added to all TCA types and duplicate fields are considered.
     *
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    #[Test]
    public function canAddFieldsToAllTCATypesRespectsPalettes(): void
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        $GLOBALS['TCA'][$table]['types']['typeD'] = ['showitem' => 'fieldY, --palette--;;standard, fieldZ'];
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:--palette--;;standard');
        // Checking typeD:
        self::assertEquals('fieldY, --palette--;;standard, newA, newB, fieldA, fieldZ', $GLOBALS['TCA'][$table]['types']['typeD']['showitem']);
    }

    /**
     * Tests whether fields can be added to all TCA types and fields in pallets are respected.
     *
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    #[Test]
    public function canAddFieldsToAllTCATypesRespectsPositionFieldInPalette(): void
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', '', 'after:fieldX1');
        // Checking typeA:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, newA, newB, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
    }

    /**
     * Tests whether fields can be added to a TCA type before existing ones
     *
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    #[Test]
    public function canAddFieldsToTCATypeBeforeExistingOnes(): void
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
     * Tests whether fields can be added to a TCA type after existing ones
     *
     * @see ExtensionManagementUtility::addToAllTCAtypes()
     */
    #[Test]
    public function canAddFieldsToTCATypeAfterExistingOnes(): void
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'newA, newA, newB, fieldA', 'typeA', 'after:fieldC');
        // Checking typeA:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, newA, newB, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
        // Checking typeB:
        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
    }

    #[Test]
    public function canAddFieldWithPartOfAlreadyExistingFieldname(): void
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'field', 'typeA', 'after:fieldD1');

        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1, field', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
    }

    /**
     * Test whether replacing other TCA fields works as promised
     *
     * @see ExtensionManagementUtility::addFieldsToAllPalettesOfField()
     */
    #[Test]
    public function canAddFieldsToTCATypeAndReplaceExistingOnes(): void
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

    #[Test]
    public function addToAllTCAtypesReplacesExistingOnes(): void
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

    #[Test]
    public function addToAllTCAtypesAddsToPaletteIdentifier(): void
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addToAllTCAtypes($table, 'fieldX, --palette--;;newpalette', '', 'after:palette:paletteC');
        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldX, --palette--;;newpalette, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldX, --palette--;;newpalette, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeB']['showitem']);
    }

    #[Test]
    public function addToAllTCAtypesAddsBeforeDiv(): void
    {
        $showitemDiv = '--div--;LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:foobar';
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        $GLOBALS['TCA'][$table]['types']['typeD']['showitem'] = $showitemDiv . ', ' . $GLOBALS['TCA'][$table]['types']['typeA']['showitem'];

        ExtensionManagementUtility::addToAllTCAtypes($table, 'fieldX', '', 'before:' . $showitemDiv);
        self::assertEquals('fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1, fieldX', $GLOBALS['TCA'][$table]['types']['typeA']['showitem']);
        self::assertEquals('fieldX, ' . $showitemDiv . ', fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1', $GLOBALS['TCA'][$table]['types']['typeD']['showitem']);
    }

    /**
     * Tests whether fields can be added to a palette before existing elements.
     *
     * @see ExtensionManagementUtility::addFieldsToPalette()
     */
    #[Test]
    public function canAddFieldsToPaletteBeforeExistingOnes(): void
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'before:fieldY');
        self::assertEquals('fieldX, fieldX1, newA, newB, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
    }

    /**
     * Tests whether fields can be added to a palette after existing elements.
     *
     * @see ExtensionManagementUtility::addFieldsToPalette()
     */
    #[Test]
    public function canAddFieldsToPaletteAfterExistingOnes(): void
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:fieldX');
        self::assertEquals('fieldX, newA, newB, fieldX1, fieldY', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
    }

    /**
     * Tests whether fields can be added to a palette after a not existing elements.
     *
     * @see ExtensionManagementUtility::addFieldsToPalette()
     */
    #[Test]
    public function canAddFieldsToPaletteAfterNotExistingOnes(): void
    {
        $table = StringUtility::getUniqueId('tx_coretest_table');
        $GLOBALS['TCA'] = $this->generateTCAForTable($table);
        ExtensionManagementUtility::addFieldsToPalette($table, 'paletteA', 'newA, newA, newB, fieldX', 'after:' . StringUtility::getUniqueId('notExisting'));
        self::assertEquals('fieldX, fieldX1, fieldY, newA, newB', $GLOBALS['TCA'][$table]['palettes']['paletteA']['showitem']);
    }

    public static function removeDuplicatesForInsertionRemovesDuplicatesDataProvider(): array
    {
        return [
            'Simple' => [
                'field_b, field_d, field_c',
                'field_a, field_b, field_c',
                'field_d',
            ],
            'with linebreaks' => [
                'field_b, --linebreak--, field_d, --linebreak--, field_c',
                'field_a, field_b, field_c',
                '--linebreak--, field_d, --linebreak--',
            ],
            'with linebreaks in list and insertion list' => [
                'field_b, --linebreak--, field_d, --linebreak--, field_c',
                'field_a, field_b, --linebreak--, field_c',
                '--linebreak--, field_d, --linebreak--',
            ],
        ];
    }

    #[DataProvider('removeDuplicatesForInsertionRemovesDuplicatesDataProvider')]
    #[Test]
    public function removeDuplicatesForInsertionRemovesDuplicates(string $insertionList, string $list, string $expected): void
    {
        $result = ExtensionManagementUtilityAccessibleProxy::removeDuplicatesForInsertion($insertionList, $list);
        self::assertSame($expected, $result);
    }

    ///////////////////////////////////////////////////
    // Tests concerning addFieldsToAllPalettesOfField
    ///////////////////////////////////////////////////
    #[Test]
    public function addFieldsToAllPalettesOfFieldDoesNotAddAnythingIfFieldIsNotRegisteredInColumns(): void
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

    #[Test]
    public function addFieldsToAllPalettesOfFieldAddsFieldsToPaletteAndSuppressesDuplicates(): void
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

    #[Test]
    public function addFieldsToAllPalettesOfFieldDoesNotAddAFieldThatIsPartOfPaletteAlready(): void
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

    #[Test]
    public function addFieldsToAllPalettesOfFieldAddsFieldsToMultiplePalettes(): void
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

    #[Test]
    public function addFieldsToAllPalettesOfFieldAddsMultipleFields(): void
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

    #[Test]
    public function addFieldsToAllPalettesOfFieldAddsBeforeExistingIfRequested(): void
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

    #[Test]
    public function addFieldsToAllPalettesOfFieldAddsFieldsAtEndIfBeforeRequestedDoesNotExist(): void
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

    #[Test]
    public function addFieldsToAllPalettesOfFieldAddsAfterExistingIfRequested(): void
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

    #[Test]
    public function addFieldsToAllPalettesOfFieldAddsFieldsAtEndIfAfterRequestedDoesNotExist(): void
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

    #[Test]
    public function addFieldsToAllPalettesOfFieldAddsNewPaletteIfFieldHasNoPaletteYet(): void
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

    #[Test]
    public function addFieldsToAllPalettesOfFieldAddsNewPaletteIfFieldHasNoPaletteYetAndKeepsExistingLabel(): void
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
     */
    public static function executePositionedStringInsertionTrimsCorrectCharactersDataProvider(): array
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

    #[DataProvider('executePositionedStringInsertionTrimsCorrectCharactersDataProvider')]
    #[Test]
    public function executePositionedStringInsertionTrimsCorrectCharacters(string $string, string $expectedResult): void
    {
        $extensionManagementUtility = $this->getAccessibleMock(ExtensionManagementUtility::class, null);
        $string = $extensionManagementUtility->_call('executePositionedStringInsertion', $string, '');
        self::assertEquals($expectedResult, $string);
    }

    /////////////////////////////////////////
    // Tests concerning addTcaSelectItem
    /////////////////////////////////////////
    #[Test]
    public function addTcaSelectItemThrowsExceptionIfRelativePositionIsNotOneOfValidKeywords(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1303236967);

        ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', [], 'foo', 'not allowed keyword');
    }

    #[Test]
    public function addTcaSelectItemThrowsExceptionIfFieldIsNotFoundInTca(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1303237468);

        $GLOBALS['TCA'] = [];
        ExtensionManagementUtility::addTcaSelectItem('foo', 'bar', []);
    }

    /**
     * Data provider for addTcaSelectItemInsertsItemAtSpecifiedPosition
     */
    public static function addTcaSelectItemDataProvider(): array
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
                    0 => ['label' => 'firstElement'],
                    1 => ['label' => 'matchMe'],
                    2 => ['label' => 'thirdElement'],
                    3 => ['label' => 'insertedElement'],
                ],
            ],
            'replace element' => [
                'matchMe',
                'replace',
                [
                    0 => ['label' => 'firstElement'],
                    1 => ['label' => 'insertedElement'],
                    2 => ['label' => 'thirdElement'],
                ],
            ],
            'add element after' => [
                'matchMe',
                'after',
                [
                    0 => ['label' => 'firstElement'],
                    1 => ['label' => 'matchMe'],
                    2 => ['label' => 'insertedElement'],
                    3 => ['label' => 'thirdElement'],
                ],
            ],
            'add element before' => [
                'matchMe',
                'before',
                [
                    0 => ['label' => 'firstElement'],
                    1 => ['label' => 'insertedElement'],
                    2 => ['label' => 'matchMe'],
                    3 => ['label' => 'thirdElement'],
                ],
            ],
            'add at end if relative position was not found' => [
                'notExistingItem',
                'after',
                [
                    0 => ['label' => 'firstElement'],
                    1 => ['label' => 'matchMe'],
                    2 => ['label' => 'thirdElement'],
                    3 => ['label' => 'insertedElement'],
                ],
            ],
        ];
    }

    #[DataProvider('addTcaSelectItemDataProvider')]
    #[Test]
    public function addTcaSelectItemInsertsItemAtSpecifiedPosition(string $relativeToField, string $relativePosition, array $expectedResultArray): void
    {
        $GLOBALS['TCA'] = [
            'testTable' => [
                'columns' => [
                    'testField' => [
                        'config' => [
                            'items' => [
                                0 => ['label' => 'firstElement'],
                                1 => ['label' => 'matchMe'],
                                2 => ['label' => 'thirdElement'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        ExtensionManagementUtility::addTcaSelectItem('testTable', 'testField', ['label' => 'insertedElement'], $relativeToField, $relativePosition);
        self::assertEquals($expectedResultArray, $GLOBALS['TCA']['testTable']['columns']['testField']['config']['items']);
    }

    /////////////////////////////////////////
    // Tests concerning getExtensionVersion
    /////////////////////////////////////////
    #[Test]
    public function getExtensionVersionForEmptyExtensionKeyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1294586096);

        ExtensionManagementUtility::getExtensionVersion('');
    }

    #[Test]
    public function getExtensionVersionForNotLoadedExtensionReturnsEmptyString(): void
    {
        $uniqueSuffix = StringUtility::getUniqueId('test');
        $extensionKey = 'unloadedextension' . $uniqueSuffix;
        self::assertEquals('', ExtensionManagementUtility::getExtensionVersion($extensionKey));
    }

    #[Test]
    public function getExtensionVersionForLoadedExtensionReturnsExtensionVersion(): void
    {
        $uniqueSuffix = StringUtility::getUniqueId('test');
        $extensionKey = 'unloadedextension' . $uniqueSuffix;
        $packageMetaData = $this->getMockBuilder(MetaData::class)
            ->onlyMethods(['getVersion'])
            ->setConstructorArgs([$extensionKey])
            ->getMock();
        $packageMetaData->method('getVersion')->willReturn('1.2.3');
        $packageManager = $this->createMockPackageManagerWithMockPackage($extensionKey, ['getPackagePath', 'getPackageKey', 'getPackageMetaData']);
        /** @var Package&MockObject $package */
        $package = $packageManager->getPackage($extensionKey);
        $package
                ->method('getPackageMetaData')
                ->willReturn($packageMetaData);
        ExtensionManagementUtility::setPackageManager($packageManager);
        self::assertEquals('1.2.3', ExtensionManagementUtility::getExtensionVersion($extensionKey));
    }

    /////////////////////////////////////////
    // Tests concerning loadExtension
    /////////////////////////////////////////
    #[Test]
    public function loadExtensionThrowsExceptionIfExtensionIsLoaded(): void
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
    #[Test]
    public function unloadExtensionThrowsExceptionIfExtensionIsNotLoaded(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1342345487);

        $packageName = StringUtility::getUniqueId('foo');
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods(['isPackageActive'])
            ->disableOriginalConstructor()
            ->getMock();
        $packageManager->expects(self::once())
            ->method('isPackageActive')
            ->with(self::equalTo($packageName))
            ->willReturn(false);
        ExtensionManagementUtility::setPackageManager($packageManager);
        ExtensionManagementUtility::unloadExtension($packageName);
    }

    #[Test]
    public function unloadExtensionCallsPackageManagerToDeactivatePackage(): void
    {
        $packageName = StringUtility::getUniqueId('foo');
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods(['isPackageActive', 'deactivatePackage'])
            ->disableOriginalConstructor()
            ->getMock();
        $packageManager
            ->method('isPackageActive')
            ->willReturn(true);
        $packageManager->expects(self::once())
            ->method('deactivatePackage')
            ->with($packageName);
        ExtensionManagementUtility::setPackageManager($packageManager);
        ExtensionManagementUtility::unloadExtension($packageName);
    }

    //////////////////////////////////
    // Tests concerning addRecordType
    //////////////////////////////////
    #[Test]
    public function addRecordTypeFailsWithExceptionWithInvalidTcaRegistration(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1725997543);

        $table = 'tx_testtable';
        $GLOBALS['TCA'][$table] = [
            'ctrl' => [],
        ];
        $item = ['label' => 'mylabel', 'value' => 'examplekey'];
        ExtensionManagementUtility::addRecordType($item, 'aField', [], '', $table);
    }

    #[Test]
    public function addRecordTypeSetsIcon(): void
    {
        $table = 'tx_testtable';
        $GLOBALS['TCA'][$table]['ctrl']['type'] = 'aTypeField';
        $GLOBALS['TCA'][$table]['columns'] = [
            'aTypeField' => [
                'label' => 'my type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [],
                ],
            ],
        ];
        $item = ['label' => 'mylabel', 'value' => 'examplekey', 'icon' => 'apps-pagetree-folder-contains'];
        ExtensionManagementUtility::addRecordType($item, 'aField', [], '', $table);
        self::assertEquals('apps-pagetree-folder-contains', $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['examplekey']);
    }

    #[Test]
    public function addRecordTypeOverwritesRecordTypeIcon(): void
    {
        $table = 'tx_testtable';
        $GLOBALS['TCA'][$table]['ctrl']['type'] = 'aTypeField';
        $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['examplekey'] = 'random-icon';
        $GLOBALS['TCA'][$table]['columns'] = [
            'aTypeField' => [
                'label' => 'my type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [],
                ],
            ],
        ];
        $item = ['label' => 'mylabel', 'value' => 'examplekey', 'icon' => 'apps-pagetree-folder-contains'];
        ExtensionManagementUtility::addRecordType($item, 'aField', [], '', $table);
        self::assertEquals('apps-pagetree-folder-contains', $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['examplekey']);
    }

    #[Test]
    public function addRecordTypeSetsDefautGroup(): void
    {
        $table = 'tx_testtable';
        $GLOBALS['TCA'][$table]['ctrl']['type'] = 'aTypeField';
        $GLOBALS['TCA'][$table]['columns'] = [
            'aTypeField' => [
                'label' => 'my type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [],
                ],
            ],
        ];
        $item = ['label' => 'mylabel', 'value' => 'examplekey', 'icon' => 'apps-pagetree-folder-contains'];
        ExtensionManagementUtility::addRecordType($item, 'aField', [], '', $table);
        /** @phpstan-ignore-next-line PHPStan does not understand that the items array gets updated.. */
        self::assertEquals('default', $GLOBALS['TCA'][$table]['columns']['aTypeField']['config']['items'][0]['group']);
    }

    public static function addRecordTypeOptimizesShowItemListDataProvider(): iterable
    {
        yield 'empty showItemList is not modified' => [
            '',
            '',
        ];
        yield 'spaces and commas are removed' => [
            ', aField, ',
            'aField,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
        ];
        yield 'extended div is added' => [
            'fieldX, --palette--;;foo, fieldX1, fieldY',
            'fieldX, --palette--;;foo, fieldX1, fieldY,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
        ];
        yield 'extended is not added if already somewhere in place' => [
            'fieldX, --palette--;;foo, fieldX1, fieldY,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended, anotherField,',
            'fieldX, --palette--;;foo, fieldX1, fieldY,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended, anotherField,',
        ];
    }

    #[Test]
    #[DataProvider('addRecordTypeOptimizesShowItemListDataProvider')]
    public function addRecordTypeOptimizesShowItemList(string $givenItemList, string $expectedItemList): void
    {
        $table = 'tx_testtable';
        $GLOBALS['TCA'][$table]['ctrl']['type'] = 'aTypeField';
        $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['anotherType'] = 'random-icon';
        $GLOBALS['TCA'][$table]['columns'] = [
            'aTypeField' => [
                'label' => 'my type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [],
                ],
            ],
        ];
        $item = ['label' => 'mylabel', 'value' => 'examplekey', 'icon' => 'apps-pagetree-folder-contains'];
        ExtensionManagementUtility::addRecordType($item, $givenItemList, [], '', $table);
        self::assertEquals($expectedItemList, $GLOBALS['TCA'][$table]['types']['examplekey']['showitem']);
    }

    ///////////////////////////////
    // Tests concerning addPlugin
    ///////////////////////////////
    #[Test]
    public function addPluginSetsTcaCorrectlyForGivenExtKeyAsParameter(): void
    {
        $extKey = 'indexed_search';
        $expectedTCA = [
            [
                'label' => 'label',
                'value' => $extKey,
                'icon' => null,
                'group' => 'plugins',
                'description' => null,
            ],
        ];
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        ExtensionManagementUtility::addPlugin(['label', $extKey], 'list_type', $extKey);
        self::assertEquals($expectedTCA, $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items']);
    }

    #[Test]
    public function addPluginSetsCorrectItemGroupsEntry(): void
    {
        $extKey = 'indexed_search';
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['itemGroups']['my-second-group'] = 'My second group label from list_type';
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['itemGroups']['my-third-group'] = 'My third group label from list_type';
        $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups']['null-group'] = null;
        $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups']['my-group'] = 'My group label from CType';
        $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups']['my-third-group'] = 'My third group label from CType';

        // Won't be added since not defined in list_type or CType
        ExtensionManagementUtility::addPlugin(['label', $extKey . '_1', '', 'non-existing-group'], 'list_type', $extKey);
        // Won't be added since invalid value in CType definition
        ExtensionManagementUtility::addPlugin(['label', $extKey . '_2', '', 'null-group'], 'list_type', $extKey);
        ExtensionManagementUtility::addPlugin(['label', $extKey . '_3', '', 'my-group'], 'list_type', $extKey);
        ExtensionManagementUtility::addPlugin(['label', $extKey . '_4', '', 'my-second-group'], 'list_type', $extKey);
        ExtensionManagementUtility::addPlugin(['label', $extKey . '_5', '', 'my-third-group'], 'list_type', $extKey);

        self::assertSame(
            [
                // Group exists in list_type>itemGroups
                'my-second-group' => 'My second group label from list_type',
                // Group exists in both - no overwriting
                'my-third-group' => 'My third group label from list_type',
                // Group exists in CType>itemGroups
                'my-group' => 'My group label from CType',
            ],
            $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['itemGroups']
        );
    }

    #[Test]
    public function addPluginAsContentTypeAddsIconAndDefaultItem(): void
    {
        $extKey = 'felogin';
        $expectedTCA = [
            [
                'label' => 'label',
                'value' => 'felogin',
                'icon' => 'content-form-login',
                'group' => 'plugins',
                'description' => null,
            ],
        ];
        $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'] = [];
        $GLOBALS['TCA']['tt_content']['types']['header'] = ['showitem' => 'header,header_link'];
        $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] = [];
        ExtensionManagementUtility::addPlugin(['label', $extKey, 'content-form-login'], 'CType', $extKey);
        self::assertEquals($expectedTCA, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        self::assertEquals([$extKey => 'content-form-login'], $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']);
        self::assertEquals($GLOBALS['TCA']['tt_content']['types']['header'], $GLOBALS['TCA']['tt_content']['types']['felogin']);
    }

    #[Test]
    public function addPluginAsContentTypeAddsIconAndDefaultItemWithSelectItem(): void
    {
        $extKey = 'felogin';
        $expectedTCA = [
            [
                'label' => 'label',
                'value' => 'felogin',
                'icon' => 'content-form-login',
                'group' => 'plugins',
                'description' => null,
            ],
        ];
        $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'] = [];
        $GLOBALS['TCA']['tt_content']['types']['header'] = ['showitem' => 'header,header_link'];
        $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] = [];
        ExtensionManagementUtility::addPlugin(new SelectItem('select', 'label', $extKey, 'content-form-login'), 'CType', $extKey);
        self::assertEquals($expectedTCA, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
        self::assertEquals([$extKey => 'content-form-login'], $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']);
        self::assertEquals($GLOBALS['TCA']['tt_content']['types']['header'], $GLOBALS['TCA']['tt_content']['types']['felogin']);
    }

    public static function addTcaSelectItemGroupAddsGroupDataProvider(): array
    {
        return [
            'add the first group' => [
                'my_group',
                'my_group_label',
                null,
                null,
                [
                    'my_group' => 'my_group_label',
                ],
            ],
            'add a new group at the bottom' => [
                'my_group',
                'my_group_label',
                'bottom',
                [
                    'default' => 'default_label',
                ],
                [
                    'default' => 'default_label',
                    'my_group' => 'my_group_label',
                ],
            ],
            'add a new group at the top' => [
                'my_group',
                'my_group_label',
                'top',
                [
                    'default' => 'default_label',
                ],
                [
                    'my_group' => 'my_group_label',
                    'default' => 'default_label',
                ],
            ],
            'add a new group after an existing group' => [
                'my_group',
                'my_group_label',
                'after:default',
                [
                    'default' => 'default_label',
                    'special' => 'special_label',
                ],
                [
                    'default' => 'default_label',
                    'my_group' => 'my_group_label',
                    'special' => 'special_label',
                ],
            ],
            'add a new group before an existing group' => [
                'my_group',
                'my_group_label',
                'before:default',
                [
                    'default' => 'default_label',
                    'special' => 'special_label',
                ],
                [
                    'my_group' => 'my_group_label',
                    'default' => 'default_label',
                    'special' => 'special_label',
                ],
            ],
            'add a new group after a non-existing group moved to bottom' => [
                'my_group',
                'my_group_label',
                'after:default2',
                [
                    'default' => 'default_label',
                    'special' => 'special_label',
                ],
                [
                    'default' => 'default_label',
                    'special' => 'special_label',
                    'my_group' => 'my_group_label',
                ],
            ],
            'add a new group which already exists does nothing' => [
                'my_group',
                'my_group_label',
                'does-not-matter',
                [
                    'default' => 'default_label',
                    'my_group' => 'existing_label',
                    'special' => 'special_label',
                ],
                [
                    'default' => 'default_label',
                    'my_group' => 'existing_label',
                    'special' => 'special_label',
                ],
            ],
        ];
    }

    #[DataProvider('addTcaSelectItemGroupAddsGroupDataProvider')]
    #[Test]
    public function addTcaSelectItemGroupAddsGroup(string $groupId, string $groupLabel, ?string $position, ?array $existingGroups, array $expectedGroups): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['CType']['config'] = [];
        if (is_array($existingGroups)) {
            $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups'] = $existingGroups;
        }
        ExtensionManagementUtility::addTcaSelectItemGroup('tt_content', 'CType', $groupId, $groupLabel, $position);
        self::assertEquals($expectedGroups, $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups']);
    }

    #[Test]
    public function addServiceDoesNotFailIfValueIsNotSet(): void
    {
        ExtensionManagementUtility::addService(
            'myprovider',
            'auth',
            'myclass',
            [
                'title' => 'My authentication provider',
                'description' => 'Authentication with my provider',
                'subtype' => 'processLoginDataBE,getUserBE,authUserBE',
                'available' => true,
                'priority' => 80,
                'quality' => 100,
            ]
        );
    }
}
