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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaItemsProcessorFunctionsTest extends UnitTestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // Default LANG prophecy just returns incoming value as label if calling ->sL()
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->loadSingleTableDescription(Argument::cetera())->willReturn(null);
        $languageServiceProphecy->sL(Argument::cetera())->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $iconRegistryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconRegistryProphecy->reveal());
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        // Two instances are needed due to the push/pop behavior of addInstance()
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
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
        $GLOBALS['TCA_DESCR']['aTable']['columns']['']['description'] = 'aDescription';

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
                    3 => null,
                    4 => 'aDescription',
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
    public function populateAvailableGroupModulesTest(): void
    {
        $GLOBALS['TBE_MODULES'] = [];

        $moduleLoaderProphecy = $this->prophesize(ModuleLoader::class);
        $moduleLoader = $moduleLoaderProphecy->reveal();
        GeneralUtility::addInstance(ModuleLoader::class, $moduleLoader);
        $moduleLoaderProphecy->load([])->shouldBeCalled();
        $moduleLoader->modListGroup = [
            'aModule',
        ];
        $moduleLoaderProphecy->getModules()->willReturn([
            'aModule' => [
                'iconIdentifier' => 'empty-empty',
            ],
        ]);
        $moduleLoaderProphecy->getLabelsForModule('aModule')->shouldBeCalled()->willReturn([
            'shortdescription' => 'aModuleTabLabel',
            'description' => 'aModuleTabDescription',
            'title' => 'aModuleLabel',
        ]);

        $fieldDefinition = $expected = ['items' => []];
        $expected['items'] = [
            0 => [
                0 => 'aModuleLabel',
                1 => 'aModule',
                2 => 'empty-empty',
                3 => null,
                4 => [
                    'title' => 'aModuleTabLabel',
                    'description' => 'aModuleTabDescription',
                ],
            ],
        ];

        (new TcaItemsProcessorFunctions())->populateAvailableGroupModules($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    /**
     * @test
     */
    public function populateAvailableUserModulesTest(): void
    {
        $GLOBALS['TBE_MODULES'] = [];

        $moduleLoaderProphecy = $this->prophesize(ModuleLoader::class);
        $moduleLoader = $moduleLoaderProphecy->reveal();
        GeneralUtility::addInstance(ModuleLoader::class, $moduleLoader);
        $moduleLoaderProphecy->load([])->shouldBeCalled();
        $moduleLoader->modListUser = [
            'bModule',
        ];
        $moduleLoaderProphecy->getModules()->willReturn([
            'bModule' => [
                'iconIdentifier' => 'empty-empty',
            ],
        ]);
        $moduleLoaderProphecy->getLabelsForModule('bModule')->shouldBeCalled()->willReturn([
            'shortdescription' => 'bModuleTabLabel',
            'description' => 'bModuleTabDescription',
            'title' => 'bModuleLabel',
        ]);

        $fieldDefinition = $expected = ['items' => []];
        $expected['items'] = [
            0 => [
                0 => 'bModuleLabel',
                1 => 'bModule',
                2 => 'empty-empty',
                3 => null,
                4 => [
                    'title' => 'bModuleTabLabel',
                    'description' => 'bModuleTabDescription',
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
        $GLOBALS['TCA_DESCR']['fooTable']['columns']['bar']['description'] = 'aDescription';
        $fieldDefinition = ['items' => []];
        $expected = [
            'items' => $expectedItems,
        ];

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
                                'exclude' => 1,
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
                        3 => null,
                        4 => 'aDescription',
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
                        3 => null,
                        4 => 'aDescription',
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
													<TCEforms>
														<label>flexInputLabel</label>
														<exclude>1</exclude>
														<config>
															<type>input</type>
															<size>23</size>
														</config>
													</TCEforms>
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
        $GLOBALS['TCA_DESCR']['fooTable']['columns']['aFlexField;dummy;sDEF;input1']['description'] = 'aDescription';

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
                    3 => null,
                    4 => 'aDescription',
                ],
            ],
        ];

        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('runtime')->willReturn($cacheProphecy->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());

        (new TcaItemsProcessorFunctions())->populateExcludeFields($fieldDefinition);
        self::assertSame($expected, $fieldDefinition);
    }

    /**
     * @test
     * @dataProvider populateExplicitAuthValuesTestDataProvider
     *
     * @param array $tca
     * @param array $expectedItems
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
                    0 => '[LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.allow] anItemTitle',
                    1 => 'fooTable:aField:anItemValue:ALLOW',
                    2 => 'status-status-permission-granted',
                ],
            ],
        ];
        yield 'ExplicitDeny fields with special explicit values' => [
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
                                'authMode' => 'explicitDeny',
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
                    0 => '[LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deny] anItemTitle',
                    1 => 'fooTable:aField:anItemValue:DENY',
                    2 => 'status-status-permission-denied',
                ],
            ],
        ];
        yield 'Explicit individual allow fields with special explicit values' => [
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
                                'authMode' => 'individual',
                                'items' => [
                                    0 => [
                                        'aItemTitle',
                                        'aItemValue',
                                        null,
                                        null,
                                        '',
                                        'EXPL_ALLOW',
                                    ],
                                    // 1 is not selectable as allow and is always allowed
                                    1 => [
                                        'bItemTitle',
                                        'bItemValue',
                                    ],
                                    2 => [
                                        'cItemTitle',
                                        'cItemValue',
                                        null,
                                        null,
                                        '',
                                        'EXPL_ALLOW',
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
                    0 => '[LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.allow] aItemTitle',
                    1 => 'fooTable:aField:aItemValue:ALLOW',
                    2 => 'status-status-permission-granted',
                ],
                2 => [
                    0 => '[LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.allow] cItemTitle',
                    1 => 'fooTable:aField:cItemValue:ALLOW',
                    2 => 'status-status-permission-granted',
                ],
            ],
        ];
        yield 'Explicit individual deny fields with special explicit values' => [
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
                                'authMode' => 'individual',
                                'items' => [
                                    0 => [
                                        'aItemTitle',
                                        'aItemValue',
                                        null,
                                        null,
                                        '',
                                        'EXPL_DENY',
                                    ],
                                    // 1 is not selectable as allow and is always allowed
                                    1 => [
                                        'bItemTitle',
                                        'bItemValue',
                                    ],
                                    2 => [
                                        'cItemTitle',
                                        'cItemValue',
                                        null,
                                        null,
                                        '',
                                        'EXPL_DENY',
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
                    0 => '[LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deny] aItemTitle',
                    1 => 'fooTable:aField:aItemValue:DENY',
                    2 => 'status-status-permission-denied',
                ],
                2 => [
                    0 => '[LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deny] cItemTitle',
                    1 => 'fooTable:aField:cItemValue:DENY',
                    2 => 'status-status-permission-denied',
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
