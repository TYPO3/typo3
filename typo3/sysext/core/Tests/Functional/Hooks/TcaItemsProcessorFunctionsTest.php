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

namespace TYPO3\CMS\Core\Tests\Functional\Hooks;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaItemsProcessorFunctionsTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        // Default LANG mock just returns incoming value as label if calling ->sL()
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->with(self::anything())->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceMock;
        $iconRegistryMock = $this->createMock(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconRegistryMock);
    }

    #[Test]
    public function populateAvailableTablesTest(): void
    {
        $fieldDefinition = [
            'items' => [
                0 => [
                    'label' => '---',
                    'value' => 0,
                ],
            ],
        ];
        $GLOBALS['TCA'] = [
            'notInResult' => [
                'ctrl' => [
                    'adminOnly' => true,
                ],
                'columns' => [],
            ],
            'aTable' => [
                'ctrl' => [
                    'title' => 'aTitle',
                ],
                'columns' => [],
            ],
        ];
        $expected = [
            'items' => [
                0 => [
                    'label' => '---',
                    'value' => 0,
                ],
                1 => [
                    'label' => 'aTitle',
                    'value' => 'aTable',
                    'icon' => '',
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->load($GLOBALS['TCA'], true);
        $this->get(TcaItemsProcessorFunctions::class)->populateAvailableTables($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    #[Test]
    public function populateAvailablePageTypesTest(): void
    {
        $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] = [
            0 => [
                'label' => 'Divider',
                'value' => '--div--',
            ],
            1 => [
                'label' => 'invalid',
            ],
            2 => [
                'label' => 'aLabel',
                'value' => 'aValue',
            ],
        ];
        $fieldDefinition = ['items' => [0 => ['---', 0]]];
        $expected = [
            'items' => [
                0 => [
                    0 => '---',
                    1 => 0,
                ],
                1 => [
                    'label' => 'aLabel',
                    'value' => 'aValue',
                    'icon' => '',
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->load($GLOBALS['TCA'], true);
        $this->get(TcaItemsProcessorFunctions::class)->populateAvailablePageTypes($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    #[Test]
    public function populateAvailableUserModulesTest(): void
    {
        $moduleProviderMock = $this->createMock(ModuleProvider::class);
        $moduleFactory = new ModuleFactory($this->createMock(IconRegistry::class), new NoopEventDispatcher());
        $moduleProviderMock->method('getUserModules')->willReturn([
            'aModule' => $moduleFactory->createModule('aModule', [
                'iconIdentifier' => 'a-module',
                'labels' => 'LLL:EXT:a-module/locallang',
                'packageName' => 'typo3/cms-testing',
            ]),
            'bModule' => $moduleFactory->createModule('bModule', [
                'iconIdentifier' => 'b-module',
                'labels' => 'LLL:EXT:b-module/locallang',
                'packageName' => 'typo3/cms-testing',
            ]),
        ]);
        $fieldDefinition = [
            'items' => [],
        ];
        $expected['items'] = [
            0 => [
                'label' => 'LLL:EXT:a-module/locallang:mlang_tabs_tab',
                'value' => 'aModule',
                'icon' => 'a-module',
                'description' => [
                    'title' => 'LLL:EXT:a-module/locallang:mlang_labels_tablabel',
                    'description' => 'LLL:EXT:a-module/locallang:mlang_labels_tabdescr',
                ],
            ],
            1 => [
                'label' => 'LLL:EXT:b-module/locallang:mlang_tabs_tab',
                'value' => 'bModule',
                'icon' => 'b-module',
                'description' => [
                    'title' => 'LLL:EXT:b-module/locallang:mlang_labels_tablabel',
                    'description' => 'LLL:EXT:b-module/locallang:mlang_labels_tabdescr',
                ],
            ],
        ];
        $subject = new TcaItemsProcessorFunctions(
            $this->get(IconFactory::class),
            $this->get(IconRegistry::class),
            $moduleProviderMock,
            $this->get(FlexFormTools::class),
            $this->get(TcaSchemaFactory::class),
            $this->get(PageDoktypeRegistry::class),
        );
        $subject->populateAvailableUserModules($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    public static function populateExcludeFieldsTestDataProvider(): array
    {
        return [
            'Table with exclude and non exclude field returns exclude item' => [
                [
                    // input tca
                    'fooTable' => [
                        'ctrl' => [
                            'title' => 'fooTableTitle',
                        ],
                        'columns' => [
                            'bar' => [
                                'label' => 'barColumnTitle',
                                'exclude' => true,
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                            'baz' => [
                                'label' => 'bazColumnTitle',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    // expected items
                    'fooTable' => [
                        'label' => 'fooTableTitle',
                        'value' => '--div--',
                        'icon' => '',
                    ],
                    0 => [
                        'label' => 'barColumnTitle (bar)',
                        'value' => 'fooTable:bar',
                        'icon' => 'empty-empty',
                    ],
                ],
            ],
            'Root level table with ignored root level restriction returns exclude item' => [
                [
                    // input tca
                    'fooTable' => [
                        'ctrl' => [
                            'title' => 'fooTableTitle',
                            'rootLevel' => 1,
                            'security' => [
                                'ignoreRootLevelRestriction' => true,
                            ],
                        ],
                        'columns' => [
                            'bar' => [
                                'label' => 'barColumnTitle',
                                'exclude' => true,
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    // expected items
                    'fooTable' => [
                        'label' => 'fooTableTitle',
                        'value' => '--div--',
                        'icon' => '',
                    ],
                    0 => [
                        'label' => 'barColumnTitle (bar)',
                        'value' => 'fooTable:bar',
                        'icon' => 'empty-empty',
                    ],
                ],
            ],
            'Root level table without ignored root level restriction returns no item' => [
                [
                    // input tca
                    'fooTable' => [
                        'ctrl' => [
                            'title' => 'fooTableTitle',
                            'rootLevel' => 1,
                        ],
                        'columns' => [
                            'bar' => [
                                'label' => 'barColumnTitle',
                                'exclude' => true,
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    // no items
                ],
            ],
            'Admin table returns no item' => [
                [
                    // input tca
                    'fooTable' => [
                        'ctrl' => [
                            'title' => 'fooTableTitle',
                            'adminOnly' => true,
                        ],
                        'columns' => [
                            'bar' => [
                                'label' => 'barColumnTitle',
                                'exclude' => true,
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    // no items
                ],
            ],
        ];
    }

    #[DataProvider('populateExcludeFieldsTestDataProvider')]
    #[Test]
    public function populateExcludeFieldsTest(array $tca, array $expectedItems): void
    {
        $fieldDefinition = [
            'items' => [],
        ];
        $expected = [
            'items' => $expectedItems,
        ];
        $this->get(TcaSchemaFactory::class)->load($tca, true);
        $this->get(TcaItemsProcessorFunctions::class)->populateExcludeFields($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    #[Test]
    public function populateExcludeFieldsWithFlexFormTest(): void
    {
        $tca = [
            'fooTable' => [
                'ctrl' => [
                    'title' => 'fooTableTitle',
                    'type' => 'pointerField',
                ],
                'columns' => [
                    'pointerField' => [
                        'label' => 'pointerFieldTitle',
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                [
                                    'value' => 'dummy',
                                    'label' => 'dummy',
                                ],
                                [
                                    'value' => 'dummy2',
                                    'label' => 'dummy2',
                                ],
                            ],
                        ],
                    ],
                    'aFlexField' => [
                        'label' => 'defaultFlexFieldTitle',
                        'config' => [
                            'type' => 'flex',
                            'title' => 'title',
                            'ds' => '
                                <T3DataStructure>
                                    <ROOT>
                                        <type>array</type>
                                        <el>
                                            <input1>
                                                <label>defaultFieldLabel</label>
                                                <exclude>1</exclude>
                                                <config>
                                                    <type>input</type>
                                                </config>
                                            </input1>
                                        </el>
                                    </ROOT>
                                </T3DataStructure>
                            ',
                        ],
                    ],
                ],
                'types' => [
                    'dummy' => [
                        'showitem' => 'pointerField,aFlexField',
                        // Specific record type override
                        'columnsOverrides' => [
                            'aFlexField' => [
                                'label' => 'overrideFieldTitle',
                                'config' => [
                                    'ds' => '
                                        <T3DataStructure>
                                            <ROOT>
                                                <type>array</type>
                                                <el>
                                                    <text1>
                                                        <label>overrideFieldLabel</label>
                                                        <exclude>1</exclude>
                                                        <config>
                                                            <type>text</type>
                                                        </config>
                                                    </text1>
                                                </el>
                                            </ROOT>
                                        </T3DataStructure>
                                    ',
                                ],
                            ],
                        ],
                    ],
                    'dummy2' => [
                        // Fallback to default ds
                        'showitem' => 'pointerField,aFlexField',
                    ],
                    'dummy3' => [
                        'showitem' => 'pointerField,aFlexField',
                        // Invalid override
                        'columnsOverrides' => [
                            'aFlexField' => [
                                'config' => [
                                    'ds' => '',
                                ],
                            ],
                        ],
                    ],
                    'dummy4' => [
                        // No flex field
                        'showitem' => 'pointerField',
                    ],
                    '5' => [
                        // Evaluated as integer by PHP
                        'showitem' => 'pointerField,aFlexField',
                    ],
                ],
            ],
            'barTable' => [
                'columns' => [
                    'barflexField' => [
                        'label' => 'barflexFieldTitle',
                        'config' => [
                            'type' => 'flex',
                            'ds' => 'FILE:EXT:core/Tests/Functional/Configuration/FlexForm/Fixtures/DataStructureWithSheetAndExclude.xml',
                        ],
                    ],
                ],
            ],
        ];
        $fieldDefinition = [
            'items' => [],
        ];
        $expected = [
            'items' => [
                'barTable barflexFieldTitle default' => [
                    'label' => 'barTable barflexFieldTitle default',
                    'value' => '--div--',
                    'icon' => '',
                ],
                0 => [
                    'label' => 'anExcludeFlexField (input_exclude)',
                    'value' => 'barTable:barflexField;default;sDEF;input_exclude',
                    'icon' => 'empty-empty',
                ],
                'fooTableTitle defaultFlexFieldTitle 5' => [
                    'label' => 'fooTableTitle defaultFlexFieldTitle 5',
                    'value' => '--div--',
                    'icon' => '',
                ],
                1 => [
                    'label' => 'defaultFieldLabel (input1)',
                    'value' => 'fooTable:aFlexField;5;sDEF;input1',
                    'icon' => 'empty-empty',
                ],
                'fooTableTitle defaultFlexFieldTitle default' => [
                    'label' => 'fooTableTitle defaultFlexFieldTitle default',
                    'value' => '--div--',
                    'icon' => '',
                ],
                2 => [
                    'label' => 'defaultFieldLabel (input1)',
                    'value' => 'fooTable:aFlexField;default;sDEF;input1',
                    'icon' => 'empty-empty',
                ],
                'fooTableTitle defaultFlexFieldTitle dummy2' => [
                    'label' => 'fooTableTitle defaultFlexFieldTitle dummy2',
                    'value' => '--div--',
                    'icon' => '',
                ],
                3 => [
                    'label' => 'defaultFieldLabel (input1)',
                    'value' => 'fooTable:aFlexField;dummy2;sDEF;input1',
                    'icon' => 'empty-empty',
                ],
                'fooTableTitle overrideFieldTitle dummy' => [
                    'label' => 'fooTableTitle overrideFieldTitle dummy',
                    'value' => '--div--',
                    'icon' => '',
                ],
                4 => [
                    'label' => 'overrideFieldLabel (text1)',
                    'value' => 'fooTable:aFlexField;dummy;sDEF;text1',
                    'icon' => 'empty-empty',
                ],
            ],
        ];
        // needs to be kept until FlexFormTools is using TcaSchemaFactory
        $GLOBALS['TCA'] = $tca;
        $this->get(TcaSchemaFactory::class)->load($tca, true);
        $this->get(TcaItemsProcessorFunctions::class)->populateExcludeFields($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    public static function populateExplicitAuthValuesTestDataProvider(): \Generator
    {
        yield 'ExplicitAllow fields with special explicit values' => [
            [
                'fooTable' => [
                    'ctrl' => [
                        'title' => 'fooTableTitle',
                    ],
                    'columns' => [
                        'aField' => [
                            'label' => 'aFieldTitle',
                            'config' => [
                                'type' => 'select',
                                'renderType' => 'selectSingle',
                                'authMode' => 'explicitAllow',
                                'items' => [
                                    0 => [
                                        'label' => 'anItemTitle',
                                        'value' => 'anItemValue',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                0 => [
                    'label' => 'fooTableTitle: aFieldTitle',
                    'value' => '--div--',
                ],
                1 => [
                    'label' => 'anItemTitle',
                    'value' => 'fooTable:aField:anItemValue',
                    'icon' => 'status-status-permission-granted',
                ],
            ],
        ];
    }

    #[DataProvider('populateExplicitAuthValuesTestDataProvider')]
    #[Test]
    public function populateExplicitAuthValuesTest(array $tca, array $expectedItems): void
    {
        $fieldDefinition = ['items' => []];
        $expected = [
            'items' => $expectedItems,
        ];
        $this->get(TcaSchemaFactory::class)->load($tca, true);
        $this->get(TcaItemsProcessorFunctions::class)->populateExplicitAuthValues($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    #[Test]
    public function populateCustomPermissionOptionsTest(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'] = [
            'aKey' => [
                'header' => 'aHeader',
                'items' => [
                    'anItemKey' => [
                        0 => 'anItemTitle',
                    ],
                    'anotherKey' => [
                        0 => 'anotherTitle',
                        1 => 'status-status-permission-denied',
                        2 => 'aDescription',
                    ],
                ],
            ],
        ];
        $fieldDefinition = ['items' => []];
        $expected = [
            'items' => [
                0 => [
                    'label' => 'aHeader',
                    'value' => '--div--',
                ],
                1 => [
                    'label' => 'anItemTitle',
                    'value' => 'aKey:anItemKey',
                    'icon' => 'empty-empty',
                    'description' => '',
                ],
                2 => [
                    'label' => 'anotherTitle',
                    'value' => 'aKey:anotherKey',
                    'icon' => 'empty-empty',
                    'description' => 'aDescription',
                ],
            ],
        ];
        $this->get(TcaItemsProcessorFunctions::class)->populateCustomPermissionOptions($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    #[Test]
    public function populateAvailableCategoryFieldsThrowsExceptionOnMissingTable(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1627565458);
        $fieldDefinition = ['items' => [], 'config' => []];
        $this->get(TcaItemsProcessorFunctions::class)->populateAvailableCategoryFields($fieldDefinition);
    }

    #[Test]
    public function populateAvailableCategoryFieldsThrowsExceptionOnInvalidTable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1627565459);
        $fieldDefinition = ['items' => [], 'config' => ['itemsProcConfig' => ['table' => 'aTable']]];
        $this->get(TcaItemsProcessorFunctions::class)->populateAvailableCategoryFields($fieldDefinition);
    }

    public static function populateAvailableCategoryFieldsDataProvider(): \Generator
    {
        yield 'falls back to default relationship (manyToMany)' => [
            [
                'table' => 'aTable',
            ],
            [
                0 => [
                    'label' => 'aField label',
                    'value' => 'aField',
                ],
                1 => [
                    'label' => 'dField label',
                    'value' => 'dField',
                ],
            ],
        ];
        yield 'relationship oneToMany given' => [
            [
                'table' => 'aTable',
                'allowedRelationships' => ['oneToMany'],
            ],
            [
                0 => [
                    'label' => 'cField label',
                    'value' => 'cField',
                ],
            ],
        ];
        yield 'relationship oneToOne given' => [
            [
                'table' => 'aTable',
                'allowedRelationships' => ['oneToOne'],
            ],
            [],
        ];
        yield 'multiple relationships given' => [
            [
                'table' => 'aTable',
                'allowedRelationships' => ['oneToOne', 'oneToMany', 'manyToMany'],
            ],
            [
                0 => [
                    'label' => 'aField label',
                    'value' => 'aField',
                ],
                1 => [
                    'label' => 'cField label',
                    'value' => 'cField',
                ],
                2 => [
                    'label' => 'dField label',
                    'value' => 'dField',
                ],
            ],
        ];
    }

    #[DataProvider('populateAvailableCategoryFieldsDataProvider')]
    #[Test]
    public function populateAvailableCategoryFields(array $itemsProcConfig, array $expectedItems): void
    {
        $GLOBALS['TCA']['aTable'] = [
            'ctrl' => [
                'title' => 'aTable title',
            ],
            'columns' => [
                'aField' => [
                    'label' => 'aField label',
                    'config' => [
                        'type' => 'category',
                        'relationship' => 'manyToMany',
                    ],
                ],
                'bField' => [
                    'label' => 'bField label',
                    'config' => [
                        'type' => 'category',
                    ],
                ],
                'cField' => [
                    'label' => 'cField label',
                    'config' => [
                        'type' => 'category',
                        'relationship' => 'oneToMany',
                    ],
                ],
                'dField' => [
                    'label' => 'dField label',
                    'config' => [
                        'type' => 'category',
                        'relationship' => 'manyToMany',
                    ],
                ],
            ],
        ];
        $fieldDefinition = [
            'items' => [],
            'config' => [
                'itemsProcConfig' => $itemsProcConfig,
            ],
        ];
        $expected = $fieldDefinition;
        $expected['items'] = $expectedItems;
        $this->get(TcaSchemaFactory::class)->load($GLOBALS['TCA'], true);
        $this->get(TcaItemsProcessorFunctions::class)->populateAvailableCategoryFields($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }
}
