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

namespace TYPO3\CMS\Core\Tests\Unit\Hooks;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Tests\Unit\Fixtures\EventDispatcher\MockEventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaItemsProcessorFunctionsTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Default LANG mock just returns incoming value as label if calling ->sL()
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->with(self::anything())->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceMock;

        $iconRegistryMock = $this->createMock(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconRegistryMock);
        $iconFactoryMock = $this->createMock(IconFactory::class);
        // Two instances are needed due to the push/pop behavior of addInstance()
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryMock);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryMock);
    }

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function populateAvailableTablesTest(): void
    {
        $fieldDefinition = ['items' => [0 => ['---', 0]]];

        $GLOBALS['TCA'] = [
            'notInResult' => [
                'ctrl' => [
                    'adminOnly' => true,
                ],
            ],
            'aTable' => [
                'ctrl' => [
                    'title' => 'aTitle',
                ],
            ],
        ];

        $expected = [
            'items' => [
                0 => [
                    '---',
                    0,
                ],
                1 => [
                    0 => 'aTitle',
                    1 => 'aTable',
                    2 => null,
                ],
            ],
        ];

        (new TcaItemsProcessorFunctions())->populateAvailableTables($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    /**
     * @test
     */
    public function populateAvailablePageTypesTest(): void
    {
        $fieldDefinition = ['items' => []];

        (new TcaItemsProcessorFunctions())->populateAvailablePageTypes($fieldDefinition);
        self::assertSame($fieldDefinition, $fieldDefinition);

        $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] = [
            0 => [
                0 => 'Divider',
                1 => '--div--',
            ],
            1 => [
                0 => 'invalid',
            ],
            2 => [
                0 => 'aLabel',
                1 => 'aValue',
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
                    0 => 'aLabel',
                    1 => 'aValue',
                    2 => null,
                ],
            ],
        ];

        (new TcaItemsProcessorFunctions())->populateAvailablePageTypes($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    /**
     * @test
     */
    public function populateAvailableUserModulesTest(): void
    {
        $moduleProviderMock = $this->createMock(ModuleProvider::class);
        GeneralUtility::addInstance(ModuleProvider::class, $moduleProviderMock);

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

        $fieldDefinition = $expected = ['items' => []];
        $expected['items'] = [
            0 => [
                0 => 'LLL:EXT:a-module/locallang:mlang_tabs_tab',
                1 => 'aModule',
                2 => 'a-module',
                3 => null,
                4 => [
                    'title' => 'LLL:EXT:a-module/locallang:mlang_labels_tablabel',
                    'description' => 'LLL:EXT:a-module/locallang:mlang_labels_tabdescr',
                ],
            ],
            1 => [
                0 => 'LLL:EXT:b-module/locallang:mlang_tabs_tab',
                1 => 'bModule',
                2 => 'b-module',
                3 => null,
                4 => [
                    'title' => 'LLL:EXT:b-module/locallang:mlang_labels_tablabel',
                    'description' => 'LLL:EXT:b-module/locallang:mlang_labels_tabdescr',
                ],
            ],
        ];

        (new TcaItemsProcessorFunctions())->populateAvailableUserModules($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    /**
     * @test
     * @dataProvider populateExcludeFieldsTestDataProvider
     */
    public function populateExcludeFieldsTest(array $tca, array $expectedItems): void
    {
        $GLOBALS['TCA'] = $tca;
        $fieldDefinition = ['items' => []];
        $expected = [
            'items' => $expectedItems,
        ];

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);
        GeneralUtility::addInstance(FlexFormTools::class, new FlexFormTools($eventDispatcher));

        (new TcaItemsProcessorFunctions())->populateExcludeFields($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    public function populateExcludeFieldsTestDataProvider(): array
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
                            ],
                            'baz' => [
                                'label' => 'bazColumnTitle',
                            ],
                        ],
                    ],
                ],
                [
                    // expected items
                    'fooTable' => [
                        0 => 'fooTableTitle',
                        1 => '--div--',
                        2 => null,
                    ],
                    0 => [
                        0 => 'barColumnTitle (bar)',
                        1 => 'fooTable:bar',
                        2 => 'empty-empty',
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
                            ],
                        ],
                    ],
                ],
                [
                    // expected items
                    'fooTable' => [
                        0 => 'fooTableTitle',
                        1 => '--div--',
                        2 => null,
                    ],
                    0 => [
                        0 => 'barColumnTitle (bar)',
                        1 => 'fooTable:bar',
                        2 => 'empty-empty',
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

    /**
     * @test
     */
    public function populateExcludeFieldsWithFlexFormTest(): void
    {
        $GLOBALS['TCA'] = [
            'fooTable' => [
                'ctrl' => [
                    'title' => 'fooTableTitle',
                ],
                'columns' => [
                    'aFlexField' => [
                        'label' => 'aFlexFieldTitle',
                        'config' => [
                            'type' => 'flex',
                            'title' => 'title',
                            'ds' => [
                                'dummy' => '
									<T3DataStructure>
										<ROOT>
											<type>array</type>
											<el>
												<input1>
													<label>flexInputLabel</label>
													<exclude>1</exclude>
													<config>
														<type>input</type>
														<size>23</size>
													</config>
												</input1>
											</el>
										</ROOT>
									</T3DataStructure>
								',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $fieldDefinition = ['items' => []];
        $expected = [
            'items' => [
                'fooTableTitle aFlexFieldTitle dummy' => [
                    0 => 'fooTableTitle aFlexFieldTitle dummy',
                    1 => '--div--',
                    2 => null,
                ],
                0 => [
                    0 => 'flexInputLabel (input1)',
                    1 => 'fooTable:aFlexField;dummy;sDEF;input1',
                    2 => 'empty-empty',
                ],
            ],
        ];

        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheManagerMock->method('getCache')->with('runtime')->willReturn($cacheMock);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);
        GeneralUtility::addInstance(FlexFormTools::class, new FlexFormTools($eventDispatcher));

        (new TcaItemsProcessorFunctions())->populateExcludeFields($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    /**
     * @test
     * @dataProvider populateExplicitAuthValuesTestDataProvider
     */
    public function populateExplicitAuthValuesTest(array $tca, array $expectedItems): void
    {
        $GLOBALS['TCA'] = $tca;
        $fieldDefinition = ['items' => []];
        $expected = [
            'items' => $expectedItems,
        ];
        (new TcaItemsProcessorFunctions())->populateExplicitAuthValues($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    public function populateExplicitAuthValuesTestDataProvider(): \Generator
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
                                        'anItemTitle',
                                        'anItemValue',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                0 => [
                    0 => 'fooTableTitle: aFieldTitle',
                    1 => '--div--',
                ],
                1 => [
                    0 => 'anItemTitle',
                    1 => 'fooTable:aField:anItemValue',
                    2 => 'status-status-permission-granted',
                ],
            ],
        ];
    }

    /**
     * @test
     */
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
                    0 => 'aHeader',
                    1 => '--div--',
                ],
                1 => [
                    0 => 'anItemTitle',
                    1 => 'aKey:anItemKey',
                    2 => 'empty-empty',
                    3 => null,
                    4 => '',
                ],
                2 => [
                    0 => 'anotherTitle',
                    1 => 'aKey:anotherKey',
                    2 => 'empty-empty',
                    3 => null,
                    4 => 'aDescription',
                ],
            ],
        ];
        (new TcaItemsProcessorFunctions())->populateCustomPermissionOptions($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    /**
     * @test
     */
    public function populateAvailableCategoryFieldsThrowsExceptionOnMissingTable(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1627565458);

        $fieldDefinition = ['items' => [], 'config' => []];
        (new TcaItemsProcessorFunctions())->populateAvailableCategoryFields($fieldDefinition);
    }

    /**
     * @test
     */
    public function populateAvailableCategoryFieldsThrowsExceptionOnInvalidTable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1627565459);

        $fieldDefinition = ['items' => [], 'config' => ['itemsProcConfig' => ['table' => 'aTable']]];
        (new TcaItemsProcessorFunctions())->populateAvailableCategoryFields($fieldDefinition);
    }

    /**
     * @test
     * @dataProvider populateAvailableCategoryFieldsDataProvider
     */
    public function populateAvailableCategoryFields(array $itemsProcConfig, array $expectedItems): void
    {
        $GLOBALS['TCA']['aTable']['columns'] = [
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
        ];
        $fieldDefinition = ['items' => [], 'config' => ['itemsProcConfig' => $itemsProcConfig]];
        $expected = $fieldDefinition;
        $expected['items'] = $expectedItems;
        (new TcaItemsProcessorFunctions())->populateAvailableCategoryFields($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    public function populateAvailableCategoryFieldsDataProvider(): \Generator
    {
        yield 'falls back to default relationship (manyToMany)' => [
            [
                'table' => 'aTable',
            ],
            [
                0 => [
                    0 => 'aField label',
                    1 => 'aField',
                ],
                1 => [
                    0 => 'dField label',
                    1 => 'dField',
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
                    0 => 'cField label',
                    1 => 'cField',
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
                    0 => 'aField label',
                    1 => 'aField',
                ],
                1 => [
                    0 => 'cField label',
                    1 => 'cField',
                ],
                2 => [
                    0 => 'dField label',
                    1 => 'dField',
                ],
            ],
        ];
    }
}
