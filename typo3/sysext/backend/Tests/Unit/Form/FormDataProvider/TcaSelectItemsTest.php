<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case
 */
class TcaSelectItemsTest extends UnitTestCase
{
    /**
     * @var TcaSelectItems
     */
    protected $subject;

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->subject = new TcaSelectItems();
    }

    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addDataKeepExistingItems()
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    'foo',
                                    'bar',
                                ],
                            ],
                        ],
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'group',
                            'items' => [
                                0 => [
                                    'foo',
                                    'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfAnItemIsNotAnArray()
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => 'foo',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1439288036);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataTranslatesItemLabels()
    {
        $input = [
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                    1 => 'aValue',
                                ],
                            ],
                            'maxitems' => 1
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');

        $languageService->sL('aLabel')->shouldBeCalled()->willReturn('translated');

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'translated';
        $expected['processedTca']['columns']['aField']['config']['items'][0][2] = null;
        $expected['processedTca']['columns']['aField']['config']['items'][0][3] = null;

        $expected['databaseRow']['aField'] = ['aValue'];

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsIconFromItem()
    {
        $input = [
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                    1 => 'aValue',
                                    2 => 'an-icon-reference',
                                    3 => null,
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['databaseRow']['aField'] = ['aValue'];

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionWithUnknownSpecialValue()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'anUnknownValue',
                        ],
                    ],
                ],
            ],
        ];

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1439298496);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataAddsTablesWithSpecialTables()
    {
        $input = [
            'databaseRow' => [
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'tables',
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
        ];
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

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');
        $languageService->sL(Argument::containingString('INVALID VALUE'))->willReturnArgument(0);

        $languageService->sL('aTitle')->shouldBeCalled()->willReturnArgument(0);
        $languageService->loadSingleTableDescription('aTable')->shouldBeCalled();

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            0 => [
                0 => 'aTitle',
                1 => 'aTable',
                2 => 'default-not-found',
                3 => [
                    'description' => 'aDescription',
                ],
            ]
        ];

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataAddsTablesWithSpecialPageTypes()
    {
        $input = [
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'pagetypes',
                            'items' => [],
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA'] = [
            'pages' => [
                'columns' => [
                    'doktype' => [
                        'config' => [
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                    1 => 'aValue',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');

        $languageService->sL('aLabel')->shouldBeCalled()->willReturnArgument(0);

        $expected = $input;
        $expected['databaseRow']['aField'] = ['aValue'];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            0 => [
                0 => 'aLabel',
                1 => 'aValue',
                2 => 'default-not-found',
                3 => null,
            ]
        ];

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * Data provider
     */
    public function addDataAddsExcludeFieldsWithSpecialExcludeDataProvider()
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
                                'exclude' => 1
                            ],
                            'baz' => [
                                'label' => 'bazColumnTitle',
                            ],
                        ],
                    ],
                ],
                [
                    // expected items
                    0 => [
                        0 => 'fooTableTitle',
                        1 => '--div--',
                        2 => 'default-not-found',
                        3 => null,
                    ],
                    1 => [
                        0 => 'barColumnTitle (bar)',
                        1 => 'fooTable:bar',
                        2 => 'empty-empty',
                        3 => null,
                    ],
                ],
            ],
            'Root level table with ignored root level restriction returns exclude item' => [
                [
                    // input tca
                    'fooTable' => [
                        'ctrl' => [
                            'title' => 'fooTableTitle',
                            'rootLevel' => true,
                            'security' => [
                                'ignoreRootLevelRestriction' => true,
                            ],
                        ],
                        'columns' => [
                            'bar' => [
                                'label' => 'barColumnTitle',
                                'exclude' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    // expected items
                    0 => [
                        0 => 'fooTableTitle',
                        1 => '--div--',
                        2 => 'default-not-found',
                        3 => null,
                    ],
                    1 => [
                        0 => 'barColumnTitle (bar)',
                        1 => 'fooTable:bar',
                        2 => 'empty-empty',
                        3 => null,
                    ],
                ],
            ],
            'Root level table without ignored root level restriction returns no item' => [
                [
                    // input tca
                    'fooTable' => [
                        'ctrl' => [
                            'title' => 'fooTableTitle',
                            'rootLevel' => true,
                        ],
                        'columns' => [
                            'bar' => [
                                'label' => 'barColumnTitle',
                                'exclude' => 1,
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
                                'exclude' => 1,
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
     * @dataProvider addDataAddsExcludeFieldsWithSpecialExcludeDataProvider
     */
    public function addDataAddsExcludeFieldsWithSpecialExclude($tca, $expectedItems)
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'exclude',
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA'] = $tca;

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->loadSingleTableDescription(Argument::cetera())->willReturn(null);
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $result = $this->subject->addData($input);

        $this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataAddsExcludeFieldsFromFlexWithSpecialExclude()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'exclude',
                        ],
                    ],
                ],
            ],
        ];

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

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->loadSingleTableDescription(Argument::cetera())->willReturn(null);
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        // Needed to suppress a cache in xml2array
        /** @var DatabaseConnection|ObjectProphecy $database */
        $database = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $database->reveal();

        $expectedItems = [
            0 => [
                0 => 'fooTableTitle aFlexFieldTitle dummy',
                1 => '--div--',
                2 => 'default-not-found',
                3 => null,
            ],
            1 => [
                0 => 'flexInputLabel (input1)',
                1 => 'fooTable:aFlexField;dummy;sDEF;input1',
                2 => 'empty-empty',
                3 => null,
            ],
        ];

        $result = $this->subject->addData($input);

        $this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataAddsExplicitAllowFieldsWithSpecialExplicitValues()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'explicitValues',
                        ],
                    ],
                ],
            ],
        ];

        $GLOBALS['TCA'] = [
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
                            ]
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.allow')->shouldBeCalled()->willReturn('allowMe');
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expectedItems = [
            0 => [
                0 => 'fooTableTitle: aFieldTitle',
                1 => '--div--',
                2 => null,
                3 => null,
            ],
            1 => [
                0 => '[allowMe] anItemTitle',
                1 => 'fooTable:aField:anItemValue:ALLOW',
                2 => 'status-status-permission-granted',
                3 => null,
            ],
        ];

        $result = $this->subject->addData($input);

        $this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataAddsExplicitDenyFieldsWithSpecialExplicitValues()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'explicitValues',
                        ],
                    ],
                ],
            ],
        ];

        $GLOBALS['TCA'] = [
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
                            ]
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.deny')->shouldBeCalled()->willReturn('denyMe');
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expectedItems = [
            0 => [
                0 => 'fooTableTitle: aFieldTitle',
                1 => '--div--',
                2 => null,
                3 => null,
            ],
            1 => [
                0 => '[denyMe] anItemTitle',
                1 => 'fooTable:aField:anItemValue:DENY',
                2 => 'status-status-permission-denied',
                3 => null,
            ],
        ];

        $result = $this->subject->addData($input);

        $this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataAddsExplicitIndividualAllowFieldsWithSpecialExplicitValues()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'explicitValues',
                        ],
                    ],
                ],
            ],
        ];

        $GLOBALS['TCA'] = [
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
                                    'EXPL_ALLOW',
                                ],
                            ]
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.allow')->shouldBeCalled()->willReturn('allowMe');
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expectedItems = [
            0 => [
                0 => 'fooTableTitle: aFieldTitle',
                1 => '--div--',
                2 => null,
                3 => null,
            ],
            1 => [
                0 => '[allowMe] aItemTitle',
                1 => 'fooTable:aField:aItemValue:ALLOW',
                2 => 'status-status-permission-granted',
                3 => null,
            ],
            2 => [
                0 => '[allowMe] cItemTitle',
                1 => 'fooTable:aField:cItemValue:ALLOW',
                2 => 'status-status-permission-granted',
                3 => null,
            ],
        ];

        $result = $this->subject->addData($input);

        $this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataAddsExplicitIndividualDenyFieldsWithSpecialExplicitValues()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'explicitValues',
                        ],
                    ],
                ],
            ],
        ];

        $GLOBALS['TCA'] = [
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
                                    'EXPL_DENY',
                                ],
                            ]
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.deny')->shouldBeCalled()->willReturn('denyMe');
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expectedItems = [
            0 => [
                0 => 'fooTableTitle: aFieldTitle',
                1 => '--div--',
                2 => null,
                3 => null,
            ],
            1 => [
                0 => '[denyMe] aItemTitle',
                1 => 'fooTable:aField:aItemValue:DENY',
                2 => 'status-status-permission-denied',
                3 => null,
            ],
            2 => [
                0 => '[denyMe] cItemTitle',
                1 => 'fooTable:aField:cItemValue:DENY',
                2 => 'status-status-permission-denied',
                3 => null,
            ],
        ];

        $result = $this->subject->addData($input);

        $this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataAddsLanguagesWithSpecialLanguages()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'languages',
                        ],
                    ],
                ],
            ],
            'systemLanguageRows' => [
                0 => [
                    'title' => 'aLangTitle',
                    'uid' => 42,
                    'flagIconIdentifier' => 'aFlag.gif',
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expectedItems = [
            0 => [
                0 => 'aLangTitle [42]',
                1 => 42,
                2 => 'aFlag.gif',
                3 => null,
            ],
        ];

        $result = $this->subject->addData($input);

        $this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataAddsCustomOptionsWithSpecialCustom()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'custom',
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

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
            ]
        ];

        $expectedItems = [
            0 => [
                0 => 'aHeader',
                1 => '--div--',
                null,
                null,
            ],
            1 => [
                0 => 'anItemTitle',
                1 => 'aKey:anItemKey',
                2 => 'empty-empty',
                3 => null,
            ],
            2 => [
                0 => 'anotherTitle',
                1 => 'aKey:anotherKey',
                2 => 'status-status-permission-denied',
                3 => [ 'description' => 'aDescription' ],
            ],
        ];

        $result = $this->subject->addData($input);

        $this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataAddsGroupItemsWithSpecialModListGroup()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'special' => 'modListGroup',
                        ],
                    ],
                ],
            ],
        ];

        $GLOBALS['TBE_MODULES'] = [];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);
        $languageService->moduleLabels = [
            'tabs_images' => [
                'aModule_tab' => PATH_site . 'aModuleTabIcon.gif',
            ],
            'labels' => [
                'aModule_tablabel' => 'aModuleTabLabel',
                'aModule_tabdescr' => 'aModuleTabDescription',
            ],
            'tabs' => [
                'aModule_tab' => 'aModuleLabel',
            ]
        ];

        /** @var ModuleLoader|ObjectProphecy $moduleLoaderProphecy */
        $moduleLoaderProphecy = $this->prophesize(ModuleLoader::class);
        GeneralUtility::addInstance(ModuleLoader::class, $moduleLoaderProphecy->reveal());
        $moduleLoaderProphecy->load([])->shouldBeCalled();
        $moduleLoaderProphecy->modListGroup = [
            'aModule',
        ];

        $expectedItems = [
            0 => [
                0 => 'aModuleLabel',
                1 => 'aModule',
                2 => '../aModuleTabIcon.gif',
                3 => [
                    'title' => 'aModuleTabLabel',
                    'description' => 'aModuleTabDescription',
                ],
            ],
        ];

        $result = $this->subject->addData($input);

        $this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataAddsFileItemsWithConfiguredFileFolder()
    {
        $directory = $this->getUniqueId('typo3temp/test-') . '/';
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'fileFolder' => $directory,
                            'fileFolder_extList' => 'gif',
                            'fileFolder_recursions' => 1,
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        mkdir(PATH_site . $directory);
        $this->testFilesToDelete[] = PATH_site . $directory;
        touch(PATH_site . $directory . 'anImage.gif');
        touch(PATH_site . $directory . 'aFile.txt');
        mkdir(PATH_site . $directory . '/subdir');
        touch(PATH_site . $directory . '/subdir/anotherImage.gif');

        $expectedItems = [
            0 => [
                0 => 'anImage.gif',
                1 => 'anImage.gif',
                2 => '../' . $directory . 'anImage.gif',
                3 => null,
            ],
            1 => [
                0 => 'subdir/anotherImage.gif',
                1 => 'subdir/anotherImage.gif',
                2 => '../' . $directory . 'subdir/anotherImage.gif',
                3 => null,
            ],
        ];

        $result = $this->subject->addData($input);

        $this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForInvalidFileFolder()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'fileFolder' => 'EXT:non_existing/Resources/Public/',
                        ],
                    ],
                ],
            ],
        ];

        $this->setExpectedException(\RuntimeException::class, $this->anything(), 1479399227);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataAddsItemsByAddItemsFromPageTsConfig()
    {
        $input = [
            'databaseRow' => [
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'keepMe',
                                    1 => 'keep',
                                    null,
                                    null,
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'addItems.' => [
                                '1' => 'addMe'
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'][1] = [
            0 => 'addMe',
            1 => '1',
            null,
            null,
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataAddsItemsByAddItemsWithDuplicateValuesFromPageTsConfig()
    {
        $input = [
            'databaseRow' => [
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'keepMe',
                                    1 => 'keep',
                                    null,
                                    null,
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'addItems.' => [
                                'keep' => 'addMe'
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'][1] = [
            0 => 'addMe',
            1 => 'keep',
            null,
            null,
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * Data provider
     */
    public function addDataReplacesMarkersInForeignTableClauseDataProvider()
    {
        return [
            'replace REC_FIELD' => [
                'AND fTable.title=\'###REC_FIELD_rowField###\'',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.title=\'rowFieldValue\'',
                [],
            ],
            'replace REC_FIELD within FlexForm' => [
                'AND fTable.title=###REC_FIELD_rowFieldFlexForm###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.title=\'rowFieldFlexFormValue\'',
                [
                    'databaseRow' => [
                        'rowFieldThree' => [
                            0 => 'rowFieldThreeValue'
                        ]
                    ],
                    'flexParentDatabaseRow' => [
                        'rowFieldFlexForm' => [
                            0 => 'rowFieldFlexFormValue'
                        ]
                    ],
                ],
            ],
            'replace REC_FIELD fullQuote' => [
                'AND fTable.title=###REC_FIELD_rowField###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.title=\'rowFieldValue\'',
                [],
            ],
            'replace REC_FIELD fullQuoteWithArray' => [
                'AND fTable.title=###REC_FIELD_rowFieldThree###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.title=\'rowFieldThreeValue\'',
                [
                    'databaseRow' => [
                        'rowFieldThree' => [
                            0 => 'rowFieldThreeValue'
                        ]
                    ],
                ],
            ],
            'replace REC_FIELD multiple markers' => [
                'AND fTable.title=\'###REC_FIELD_rowField###\' AND fTable.pid=###REC_FIELD_rowFieldTwo###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.title=\'rowFieldValue\' AND fTable.pid=\'rowFieldTwoValue\'',
                [],
            ],
            'replace CURRENT_PID' => [
                'AND fTable.uid=###CURRENT_PID###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=43',
                [],
            ],
            'replace CURRENT_PID within FlexForm' => [
                'AND fTable.uid=###CURRENT_PID###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=77',
                [
                    'flexParentDatabaseRow' => [
                        'pid' => '77',
                    ],
                ],
            ],
            'replace CURRENT_PID integer cast' => [
                'AND fTable.uid=###CURRENT_PID###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=431',
                [
                    'effectivePid' => '431string',
                ],
            ],
            'replace THIS_UID' => [
                'AND fTable.uid=###THIS_UID###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=42',
                [],
            ],
            'replace THIS_UID integer cast' => [
                'AND fTable.uid=###THIS_UID###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=421',
                [
                    'databaseRow' => [
                        'uid' => '421string',
                    ],
                ],
            ],
            'replace SITEROOT' => [
                'AND fTable.uid=###SITEROOT###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=44',
                [],
            ],
            'replace SITEROOT integer cast' => [
                'AND fTable.uid=###SITEROOT###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=441',
                [
                    'rootline' => [
                        1 => [
                            'uid' => '441string',
                        ],
                    ],
                ],
            ],
            'replace PAGE_TSCONFIG_ID' => [
                'AND fTable.uid=###PAGE_TSCONFIG_ID###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=45',
                [
                    'pageTsConfig' => [
                        'TCEFORM.' => [
                            'aTable.' => [
                                'aField.' => [
                                    'PAGE_TSCONFIG_ID' => '45',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'replace PAGE_TSCONFIG_ID integer cast' => [
                'AND fTable.uid=###PAGE_TSCONFIG_ID###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=451',
                [
                    'pageTsConfig' => [
                        'TCEFORM.' => [
                            'aTable.' => [
                                'aField.' => [
                                    'PAGE_TSCONFIG_ID' => '451string'
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'replace PAGE_TSCONFIG_STR' => [
                'AND fTable.uid=\'###PAGE_TSCONFIG_STR###\'',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=\'46\'',
                [
                    'pageTsConfig' => [
                        'TCEFORM.' => [
                            'aTable.' => [
                                'aField.' => [
                                    'PAGE_TSCONFIG_STR' => '46',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'replace PAGE_TSCONFIG_IDLIST' => [
                'AND fTable.uid IN (###PAGE_TSCONFIG_IDLIST###)',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid IN (47,48)',
                [
                    'pageTsConfig' => [
                        'TCEFORM.' => [
                            'aTable.' => [
                                'aField.' => [
                                    'PAGE_TSCONFIG_IDLIST' => '47,48',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'replace PAGE_TSCONFIG_IDLIST cleans list' => [
                'AND fTable.uid IN (###PAGE_TSCONFIG_IDLIST###)',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid IN (471,481)',
                [
                    'pageTsConfig' => [
                        'TCEFORM.' => [
                            'aTable.' => [
                                'aField.' => [
                                    'PAGE_TSCONFIG_IDLIST' => 'a, 471, b, 481, c',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'deprecated flexHack PAGE_TSCONFIG_ID is substituted' => [
                'AND fTable.uid=###PAGE_TSCONFIG_ID###',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=123',
                [
                    'pageTsConfig' => [
                        'flexHack.' => [
                            'PAGE_TSCONFIG_ID' => '123',
                        ],
                    ],
                ],
            ],
            'deprecated flexHack PAGE_TSCONFIG_IDLIST is substituted' => [
                'AND fTable.uid IN (###PAGE_TSCONFIG_IDLIST###)',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid IN (123,124)',
                [
                    'pageTsConfig' => [
                        'flexHack.' => [
                            'PAGE_TSCONFIG_IDLIST' => '123,124',
                        ],
                    ],
                ],
            ],
            'deprecated flexHack PAGE_TSCONFIG_STR is substituted' => [
                'AND fTable.uid=\'###PAGE_TSCONFIG_STR###\'',
                'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND fTable.uid=\'aString\'',
                [
                    'pageTsConfig' => [
                        'flexHack.' => [
                            'PAGE_TSCONFIG_STR' => 'aString',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addDataReplacesMarkersInForeignTableClauseDataProvider
     */
    public function addDataReplacesMarkersInForeignTableClause($foreignTableWhere, $expectedWhere, array $inputOverride)
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 43,
            'databaseRow' => [
                'uid' => 42,
                'rowField' => 'rowFieldValue',
                'rowFieldTwo' => 'rowFieldTwoValue',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'fTable',
                            'foreign_table_where' => $foreignTableWhere,
                        ],
                    ],
                ]
            ],
            'rootline' => [
                2 => [
                    'uid' => 999,
                    'is_siteroot' => 0,
                ],
                1 => [
                    'uid' => 44,
                    'is_siteroot' => 1,
                ],
                0 => [
                    'uid' => 0,
                    'is_siteroot' => null,
                ],
            ],
            'pageTsConfig' => [],
        ];
        ArrayUtility::mergeRecursiveWithOverrule($input, $inputOverride);

        $GLOBALS['TCA']['fTable'] = [];

        $expectedQueryArray = [
            'SELECT' => 'fTable.uid',
            'FROM' => 'fTable, pages',
            'WHERE' => $expectedWhere,
            'GROUPBY' => '',
            'ORDERBY' => '',
            'LIMIT' => '',
        ];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        /** @var DatabaseConnection|ObjectProphecy $databaseProphecy */
        $databaseProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $databaseProphecy->reveal();
        $databaseProphecy->sql_error()->shouldBeCalled()->willReturn(false);
        $databaseProphecy->quoteStr(Argument::cetera())->willReturnArgument(0);
        $databaseProphecy->fullQuoteStr(Argument::cetera())->will(function ($args) {
            return '\'' . $args[0] . '\'';
        });
        $databaseProphecy->sql_fetch_assoc(Argument::cetera())->shouldBeCalled()->willReturn(false);
        $databaseProphecy->sql_free_result(Argument::cetera())->shouldBeCalled()->willReturn(null);

        $databaseProphecy->exec_SELECT_queryArray($expectedQueryArray)->shouldBeCalled()->willReturn(false);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignTableIsNotDefinedInTca()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'fTable',
                        ],
                    ],
                ]
            ],
        ];

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1439569743);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataForeignTableSplitsGroupOrderAndLimit()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'fTable',
                            'foreign_table_where' => 'AND ftable.uid=1 GROUP BY groupField ORDER BY orderField LIMIT 1,2',
                        ],
                    ],
                ]
            ],
            'rootline' => [],
        ];

        $GLOBALS['TCA']['fTable'] = [];

        $expectedQueryArray = [
            'SELECT' => 'fTable.uid',
            'FROM' => 'fTable, pages',
            'WHERE' => 'pages.uid=fTable.pid AND pages.deleted=0 AND 1=1 AND ftable.uid=1',
            'GROUPBY' => 'groupField',
            'ORDERBY' => 'orderField',
            'LIMIT' => '1,2',
        ];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        /** @var DatabaseConnection|ObjectProphecy $databaseProphecy */
        $databaseProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $databaseProphecy->reveal();
        $databaseProphecy->sql_error()->shouldBeCalled()->willReturn(false);
        $databaseProphecy->quoteStr(Argument::cetera())->willReturnArgument(0);
        $databaseProphecy->fullQuoteStr(Argument::cetera())->will(function ($args) {
            return '\'' . $args[0] . '\'';
        });
        $databaseProphecy->sql_fetch_assoc(Argument::cetera())->shouldBeCalled()->willReturn(false);
        $databaseProphecy->sql_free_result(Argument::cetera())->shouldBeCalled()->willReturn(null);

        $databaseProphecy->exec_SELECT_queryArray($expectedQueryArray)->shouldBeCalled()->willReturn(false);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataForeignTableQueuesFlashMessageOnDatabaseError()
    {
        $input = [
            'databaseRow' => [
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'fTable',
                            'items' => [
                                0 => [
                                    0 => 'itemLabel',
                                    1 => 'itemValue',
                                    2 => null,
                                    3 => null,
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
            'rootline' => [],
        ];

        $GLOBALS['TCA']['fTable'] = [];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        /** @var LanguageService|ObjectProphecy $languageServiceProphecy */
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $languageServiceProphecy->sL(Argument::cetera())->willReturnArgument(0);

        /** @var DatabaseConnection|ObjectProphecy $databaseProphecy */
        $databaseProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $databaseProphecy->reveal();
        $databaseProphecy->exec_SELECT_queryArray(Argument::cetera())->willReturn(false);

        $databaseProphecy->sql_error()->shouldBeCalled()->willReturn('anError');
        $databaseProphecy->sql_free_result(Argument::cetera())->shouldBeCalled()->willReturn(null);

        /** @var FlashMessage|ObjectProphecy $flashMessage */
        $flashMessage = $this->prophesize(FlashMessage::class);
        GeneralUtility::addInstance(FlashMessage::class, $flashMessage->reveal());
        /** @var FlashMessageService|ObjectProphecy $flashMessageService */
        $flashMessageService = $this->prophesize(FlashMessageService::class);
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService->reveal());
        /** @var FlashMessageQueue|ObjectProphecy $flashMessageQueue */
        $flashMessageQueue = $this->prophesize(FlashMessageQueue::class);
        $flashMessageService->getMessageQueueByIdentifier(Argument::cetera())->willReturn($flashMessageQueue->reveal());

        $flashMessageQueue->enqueue($flashMessage)->shouldBeCalled();

        $expected = $input;
        $expected['databaseRow']['aField'] = [];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataForeignTableHandlesForeignTableRows()
    {
        $input = [
            'databaseRow' => [
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'fTable',
                            'foreign_table_prefix' => 'aPrefix',
                            'items' => [],
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
            'rootline' => [],
        ];

        $GLOBALS['TCA']['fTable'] = [];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        /** @var LanguageService|ObjectProphecy $languageServiceProphecy */
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $languageServiceProphecy->sL(Argument::cetera())->willReturnArgument(0);

        /** @var DatabaseConnection|ObjectProphecy $databaseProphecy */
        $databaseProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $databaseProphecy->reveal();
        $databaseProphecy->sql_error()->shouldBeCalled()->willReturn(false);
        $databaseProphecy->sql_free_result(Argument::cetera())->willReturn(null);
        $databaseProphecy->exec_SELECT_queryArray(Argument::cetera())->willReturn(true);

        $counter = 0;
        $databaseProphecy->sql_fetch_assoc(Argument::cetera())->shouldBeCalled()->will(function ($args) use (&$counter) {
            $counter++;
            if ($counter >= 3) {
                return false;
            }
            return [
                'uid' => $counter,
                'aValue' => 'bar,',
            ];
        });

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            0 => [
                0 => 'aPrefix[LLL:EXT:lang/locallang_core.xlf:labels.no_title]',
                1 => 1,
                2 => 'default-not-found',
                3 => null,
            ],
            1 => [
                0 => 'aPrefix[LLL:EXT:lang/locallang_core.xlf:labels.no_title]',
                1 => 2,
                2 => 'default-not-found',
                3 => null,
            ],
        ];

        $expected['databaseRow']['aField'] = [];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataForeignTableResolvesIconFromSelicon()
    {
        $input = [
            'databaseRow' => [
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'fTable',
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
            'rootline' => [],
        ];

        // Fake the foreign_table
        $GLOBALS['TCA']['fTable'] = [
            'ctrl' => [
                'label' => 'icon',
                'selicon_field' => 'icon',
                'selicon_field_path' => 'uploads/media',
            ],
            'columns' =>[
                'icon' => [
                    'config' => [
                        'type' => 'group',
                    ],
                ],
            ],
        ];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        /** @var LanguageService|ObjectProphecy $languageServiceProphecy */
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $languageServiceProphecy->sL(Argument::cetera())->willReturnArgument(0);

        /** @var DatabaseConnection|ObjectProphecy $databaseProphecy */
        $databaseProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $databaseProphecy->reveal();
        $databaseProphecy->sql_error()->shouldBeCalled()->willReturn(false);
        $databaseProphecy->sql_free_result(Argument::cetera())->willReturn(null);
        // Query on foreign table is successful
        $databaseProphecy->exec_SELECT_queryArray(Argument::cetera())->willReturn(true);
        // Query returns one row, then false on second call
        $foreignTableRowResultOne = [
            'uid' => 1,
            'icon' => 'foo.jpg',
        ];
        $databaseProphecy->sql_fetch_assoc(Argument::cetera())->shouldBeCalled()->willReturn($foreignTableRowResultOne, false);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            0 => [
                0 => 'foo.jpg',
                1 => 1,
                2 => '../uploads/media/foo.jpg', // combination of selicon_field_path and the row value of field 'icon'
                3 => null,
            ],
        ];
        $expected['databaseRow']['aField'] = [];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesItemsByKeepItemsPageTsConfig()
    {
        $input = [
            'databaseRow' => [
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'keepMe',
                                    1 => 'keep',
                                    null,
                                    null,
                                ],
                                1 => [
                                    0 => 'removeMe',
                                    1 => 'remove',
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'keepItems' => 'keep',
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        unset($expected['processedTca']['columns']['aField']['config']['items'][1]);

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesAllItemsByEmptyKeepItemsPageTsConfig()
    {
        $input = [
            'databaseRow' => [
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'keepMe',
                                    1 => 'keep',
                                    null,
                                    null,
                                ],
                                1 => [
                                    0 => 'removeMe',
                                    1 => 'remove',
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'keepItems' => '',
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'] = [];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataEvaluatesKeepItemsBeforeAddItemsFromPageTsConfig()
    {
        $input = [
            'databaseRow' => [
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'keepMe',
                                    1 => '1',
                                    null,
                                    null,
                                ],
                                1 => [
                                    0 => 'removeMe',
                                    1 => 'remove',
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'keepItems' => '1',
                            'addItems.' => [
                                '1' => 'addItem #1',
                                '12' => 'addItem #12',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            0 => [
                0 => 'keepMe',
                1 => '1',
                null,
                null,
            ],
            1 => [
                0 => 'addItem #1',
                1 => '1',
                null,
                null,
            ],
            2 => [
                0 => 'addItem #12',
                1 => '12',
                null,
                null,
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesItemsByRemoveItemsPageTsConfig()
    {
        $input = [
            'databaseRow' => [
                'aField' => ''
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'keepMe',
                                    1 => 'keep',
                                    null,
                                    null,
                                ],
                                1 => [
                                    0 => 'removeMe',
                                    1 => 'remove',
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'removeItems' => 'remove',
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        unset($expected['processedTca']['columns']['aField']['config']['items'][1]);

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesItemsAddedByAddItemsFromPageTsConfigByRemoveItemsPageTsConfig()
    {
        $input = [
            'databaseRow' => [
                'aField' => ''
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'keepMe',
                                    1 => 'keep',
                                    null,
                                    null,
                                ],
                                1 => [
                                    0 => 'removeMe',
                                    1 => 'remove',
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'removeItems' => 'remove,add',
                            'addItems.' => [
                                'add' => 'addMe'
                            ]
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        unset($expected['processedTca']['columns']['aField']['config']['items'][1]);

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesItemsByLanguageFieldUserRestriction()
    {
        $input = [
            'databaseRow' => [
                'aField' => 'aValue,remove'
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'aField',
                ],
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'keepMe',
                                    1 => 'keep',
                                    null,
                                    null,
                                ],
                                1 => [
                                    0 => 'removeMe',
                                    1 => 'remove',
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->checkLanguageAccess('keep')->shouldBeCalled()->willReturn(true);
        $backendUserProphecy->checkLanguageAccess('remove')->shouldBeCalled()->willReturn(false);

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            [ '[ INVALID VALUE "aValue" ]', 'aValue', null, null ],
            [ 'keepMe', 'keep', null, null ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesItemsByUserAuthModeRestriction()
    {
        $input = [
            'databaseRow' => [
                'aField' => 'keep,remove'
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'authMode' => 'explicitAllow',
                            'items' => [
                                0 => [
                                    0 => 'keepMe',
                                    1 => 'keep',
                                    null,
                                    null,
                                ],
                                1 => [
                                    0 => 'removeMe',
                                    1 => 'remove',
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ]
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->checkAuthMode('aTable', 'aField', 'keep', 'explicitAllow')->shouldBeCalled()->willReturn(true);
        $backendUserProphecy->checkAuthMode('aTable', 'aField', 'remove', 'explicitAllow')->shouldBeCalled()->willReturn(false);

        $expected = $input;
        $expected['databaseRow']['aField'] = ['keep'];
        unset($expected['processedTca']['columns']['aField']['config']['items'][1]);

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsAllPagesDoktypesForAdminUser()
    {
        $input = [
            'databaseRow' => [
                'doktype' => 'keep'
            ],
            'tableName' => 'pages',
            'processedTca' => [
                'columns' => [
                    'doktype' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'keepMe',
                                    1 => 'keep',
                                    null,
                                    null,
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(true);

        $expected = $input;
        $expected['databaseRow']['doktype'] = ['keep'];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsAllowedPageTypesForNonAdminUser()
    {
        $input = [
            'databaseRow' => [
                'doktype' => 'keep',
            ],
            'tableName' => 'pages',
            'processedTca' => [
                'columns' => [
                    'doktype' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'keepMe',
                                    1 => 'keep',
                                    null,
                                    null,
                                ],
                                1 => [
                                    0 => 'removeMe',
                                    1 => 'remove',
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(false);
        $backendUserProphecy->groupData = [
            'pagetypes_select' => 'foo,keep,anotherAllowedDoktype',
        ];

        $expected = $input;
        $expected['databaseRow']['doktype'] = ['keep'];
        unset($expected['processedTca']['columns']['doktype']['config']['items'][1]);

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataCallsItemsProcFunc()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'aValue'
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [],
                            'itemsProcFunc' => function (array $parameters, $pObj) {
                                $parameters['items'] = [
                                    0 => [
                                        0 => 'aLabel',
                                        1 => 'aValue',
                                        2 => null,
                                        3 => null,
                                    ],
                                ];
                            },
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['databaseRow']['aField'] = ['aValue'];
        $expected['processedTca']['columns']['aField']['config'] = [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                0 => [
                    0 => 'aLabel',
                    1 => 'aValue',
                    2 => null,
                    3 => null,
                ],
            ],
            'maxitems' => 1,
        ];

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataItemsProcFuncReceivesParameters()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'itemsProcFunc.' => [
                                'itemParamKey' => 'itemParamValue',
                            ],
                        ]
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'aKey' => 'aValue',
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                    1 => 'aValue',
                                ],
                            ],
                            'itemsProcFunc' => function (array $parameters, $pObj) {
                                if ($parameters['items'] !== [ 0 => [ 'aLabel', 'aValue'] ]
                                    || $parameters['config']['aKey'] !== 'aValue'
                                    || $parameters['TSconfig'] !== [ 'itemParamKey' => 'itemParamValue' ]
                                    || $parameters['table'] !== 'aTable'
                                    || $parameters['row'] !== [ 'aField' => 'aValue' ]
                                    || $parameters['field'] !== 'aField'
                                ) {
                                    throw new \UnexpectedValueException('broken', 1438604329);
                                }
                            },
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);
        /** @var FlashMessage|ObjectProphecy $flashMessage */
        $flashMessage = $this->prophesize(FlashMessage::class);
        GeneralUtility::addInstance(FlashMessage::class, $flashMessage->reveal());
        /** @var FlashMessageService|ObjectProphecy $flashMessageService */
        $flashMessageService = $this->prophesize(FlashMessageService::class);
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService->reveal());
        /** @var FlashMessageQueue|ObjectProphecy $flashMessageQueue */
        $flashMessageQueue = $this->prophesize(FlashMessageQueue::class);
        $flashMessageService->getMessageQueueByIdentifier(Argument::cetera())->willReturn($flashMessageQueue->reveal());

        // itemsProcFunc must NOT have raised an exception
        $flashMessageQueue->enqueue($flashMessage)->shouldNotBeCalled();

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataItemsProcFuncEnqueuesFlashMessageOnException()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'itemsProcFunc.' => [
                                'itemParamKey' => 'itemParamValue',
                            ],
                        ]
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'aKey' => 'aValue',
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                    1 => 'aValue',
                                ],
                            ],
                            'itemsProcFunc' => function (array $parameters, $pObj) {
                                throw new \UnexpectedValueException('anException', 1438604329);
                            },
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        /** @var FlashMessage|ObjectProphecy $flashMessage */
        $flashMessage = $this->prophesize(FlashMessage::class);
        GeneralUtility::addInstance(FlashMessage::class, $flashMessage->reveal());
        /** @var FlashMessageService|ObjectProphecy $flashMessageService */
        $flashMessageService = $this->prophesize(FlashMessageService::class);
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService->reveal());
        /** @var FlashMessageQueue|ObjectProphecy $flashMessageQueue */
        $flashMessageQueue = $this->prophesize(FlashMessageQueue::class);
        $flashMessageService->getMessageQueueByIdentifier(Argument::cetera())->willReturn($flashMessageQueue->reveal());

        $flashMessageQueue->enqueue($flashMessage)->shouldBeCalled();

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataTranslatesItemLabelsFromPageTsConfig()
    {
        $input = [
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                    1 => 'aValue',
                                    null,
                                    null,
                                ],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'altLabels.' => [
                                'aValue' => 'labelOverride',
                            ],
                        ]
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('aLabel')->willReturnArgument(0);
        $languageService->sL('labelOverride')->shouldBeCalled()->willReturnArgument(0);
        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');

        $expected = $input;
        $expected['databaseRow']['aField'] = ['aValue'];
        $expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'labelOverride';

        $this->assertSame($expected, $this->subject->addData($input));
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function processSelectFieldValueSetsMmForeignRelationValues()
    {
        $GLOBALS['TCA']['foreignTable'] = [];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

        /** @var DatabaseConnection|ObjectProphecy $database */
        $database = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $database->reveal();

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => 42,
                // Two connected rows
                'aField' => 2,
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'maxitems' => 999,
                            'foreign_table' => 'foreignTable',
                            'MM' => 'aTable_foreignTable_mm',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];
        $fieldConfig = $input['processedTca']['columns']['aField']['config'];
        /** @var RelationHandler|ObjectProphecy $relationHandlerProphecy */
        $relationHandlerProphecy = $this->prophesize(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());

        $relationHandlerUids = [
            23,
            24
        ];

        $relationHandlerProphecy->start(2, 'foreignTable', 'aTable_foreignTable_mm', 42, 'aTable', $fieldConfig)->shouldBeCalled();
        $relationHandlerProphecy->getValueArray()->shouldBeCalled()->willReturn($relationHandlerUids);

        $expected = $input;
        $expected['databaseRow']['aField'] = $relationHandlerUids;

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueSetsForeignRelationValues()
    {
        $GLOBALS['TCA']['foreignTable'] = [];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

        /** @var DatabaseConnection|ObjectProphecy $database */
        $database = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $database->reveal();

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => 42,
                // Two connected rows
                'aField' => '22,23,24,25',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'maxitems' => 999,
                            'foreign_table' => 'foreignTable',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];
        $fieldConfig = $input['processedTca']['columns']['aField']['config'];
        /** @var RelationHandler|ObjectProphecy $relationHandlerProphecy */
        $relationHandlerProphecy = $this->prophesize(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());

        $relationHandlerUids = [
            23,
            24
        ];

        $relationHandlerProphecy->start('22,23,24,25', 'foreignTable', '', 42, 'aTable', $fieldConfig)->shouldBeCalled();
        $relationHandlerProphecy->getValueArray()->shouldBeCalled()->willReturn($relationHandlerUids);

        $expected = $input;
        $expected['databaseRow']['aField'] = $relationHandlerUids;

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueRemovesInvalidDynamicValues()
    {
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $GLOBALS['TCA']['foreignTable'] = [];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

        /** @var DatabaseConnection|ObjectProphecy $database */
        $database = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $database->reveal();

        $relationHandlerProphecy = $this->prophesize(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());
        $relationHandlerProphecy->start(Argument::cetera())->shouldBeCalled();
        $relationHandlerProphecy->getValueArray(Argument::cetera())->shouldBeCalled()->willReturn([1]);

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '1,2,bar,foo',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingleBox',
                            'foreign_table' => 'foreignTable',
                            'maxitems' => 999,
                            'items' => [
                                ['foo', 'foo', null, null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = ['foo', 1];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueKeepsValuesFromStaticItems()
    {
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'foo,bar',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'maxitems' => 999,
                            'items' => [
                                ['foo', 'foo', null, null],
                                ['bar', 'bar', null, null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [
            'foo',
            'bar'
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueReturnsEmptyValueForSingleSelect()
    {
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'maxitems' => 1,
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueTrimsEmptyValueForMultiValueSelect()
    {
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'b,,c',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'maxitems' => 999,
                            'items' => [
                                ['a', '', null, null],
                                ['b', 'b', null, null],
                                ['c', 'c', null, null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [
            'b',
            'c',
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueDoesNotCallRelationManagerForStaticOnlyItems()
    {
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $relationHandlerProphecy = $this->prophesize(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());
        $relationHandlerProphecy->start(Argument::cetera())->shouldNotBeCalled();
        $relationHandlerProphecy->getValueArray(Argument::cetera())->shouldNotBeCalled();

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'foo',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'maxitems' => 999,
                            'items' => [
                                ['foo', 'foo', null, null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = ['foo'];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueAddsInvalidValuesToItemsForSingleSelects()
    {
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $relationHandlerProphecy = $this->prophesize(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());
        $relationHandlerProphecy->start(Argument::cetera())->shouldNotBeCalled();
        $relationHandlerProphecy->getValueArray(Argument::cetera())->shouldNotBeCalled();

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '1,2,bar,foo',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'maxitems' => 1,
                            'items' => [
                                ['foo', 'foo', null, null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = ['foo'];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            ['[ INVALID VALUE "bar" ]', 'bar', null, null],
            ['[ INVALID VALUE "2" ]', '2', null, null],
            ['[ INVALID VALUE "1" ]', '1', null, null],
            ['foo', 'foo', null, null],
        ];
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueReturnsDuplicateValuesForMultipleSelect()
    {
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '1,foo,foo,2,bar',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'multiple' => true,
                            'maxitems' => 999,
                            'items' => [
                                ['1', '1', null, null],
                                ['foo', 'foo', null, null],
                                ['bar', 'bar', null, null],
                                ['2', '2', null, null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [
            '1',
            'foo',
            'foo',
            '2',
            'bar'
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueReturnsUniqueValuesForMultipleSelect()
    {
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '1,foo,foo,2,bar',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'multiple' => false,
                            'maxitems' => 999,
                            'items' => [
                                ['1', '1', null, null],
                                ['foo', 'foo', null, null],
                                ['bar', 'bar', null, null],
                                ['2', '2', null, null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [
            0 => '1',
            1 => 'foo',
            3 => '2',
            4 => 'bar',
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function processSelectFieldSetsCorrectValuesForMmRelationsDataProvider()
    {
        return [
            'Relation with MM table and new status with default values' => [
                [
                    'tableName' => 'aTable',
                    'command' => 'new',
                    'databaseRow' => [
                        'uid' => 'NEW1234',
                        'aField' => '24,35',
                    ],
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'maxitems' => 999,
                                    'MM' => 'mm_aTable_foreignTable',
                                    'foreign_table' => 'foreignTable',
                                    'items' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'MM' => ''
                ],
                [
                    24, 35
                ]
            ],
            'Relation with MM table and item array in list but no new status' => [
                [
                    'tableName' => 'aTable',
                    'databaseRow' => [
                        'uid' => 'NEW1234',
                        'aField' => '24,35',
                    ],
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'maxitems' => 999,
                                    'MM' => 'mm_aTable_foreignTable',
                                    'foreign_table' => 'foreignTable',
                                    'items' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [],
                []
            ],
            'Relation with MM table and maxitems = 1 processes field value (item count)' => [
                [
                    'tableName' => 'aTable',
                    'databaseRow' => [
                        'uid' => 42,
                        // MM relation with one item has 1 in field value
                        'aField' => 1,
                    ],
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'maxitems' => 1,
                                    'MM' => 'mm_aTable_foreignTable',
                                    'foreign_table' => 'foreignTable',
                                    'items' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [],
                [
                    24
                ]
            ],
            'Relation with MM table and maxitems = 1 results in empty array if no items are set' => [
                [
                    'tableName' => 'aTable',
                    'databaseRow' => [
                        'uid' => 58,
                        // MM relation with no items has 0 in field value
                        'aField' => 0,
                    ],
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'maxitems' => 1,
                                    'MM' => 'mm_aTable_foreignTable',
                                    'foreign_table' => 'foreignTable',
                                    'items' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [],
                []
            ]
        ];
    }

    /**
     * @test
     * @dataProvider processSelectFieldSetsCorrectValuesForMmRelationsDataProvider
     *
     * @param array $input
     * @param array $overrideRelationHandlerSettings
     * @param array $relationHandlerUids
     */
    public function processSelectFieldSetsCorrectValuesForMmRelations(array $input, array $overrideRelationHandlerSettings, array $relationHandlerUids)
    {
        $field = $input['databaseRow']['aField'];
        $foreignTable = isset($overrideRelationHandlerSettings['foreign_table']) ? $overrideRelationHandlerSettings['foreign_table'] : $input['processedTca']['columns']['aField']['config']['foreign_table'];
        $mmTable = isset($overrideRelationHandlerSettings['MM']) ? $overrideRelationHandlerSettings['MM'] : $input['processedTca']['columns']['aField']['config']['MM'];
        $uid = $input['databaseRow']['uid'];
        $tableName = $input['tableName'];
        $fieldConfig = $input['processedTca']['columns']['aField']['config'];

        $GLOBALS['TCA'][$foreignTable] = [];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

        /** @var DatabaseConnection|ObjectProphecy $database */
        $database = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $database->reveal();

        /** @var RelationHandler|ObjectProphecy $relationHandlerProphecy */
        $relationHandlerProphecy = $this->prophesize(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());

        $relationHandlerProphecy->start($field, $foreignTable, $mmTable, $uid, $tableName, $fieldConfig)->shouldBeCalled();
        $relationHandlerProphecy->getValueArray()->shouldBeCalled()->willReturn($relationHandlerUids);

        $expected = $input;
        $expected['databaseRow']['aField'] = $relationHandlerUids;

        $this->assertEquals($expected, $this->subject->addData($input));
    }
}
