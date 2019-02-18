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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaSelectItemsTest extends UnitTestCase
{
    /**
     * Set up
     */
    protected function setUp()
    {
        // Default LANG prophecy just returns incoming value as label if calling ->sL()
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->loadSingleTableDescription(Argument::cetera())->willReturn(null);
        $languageServiceProphecy->sL(Argument::cetera())->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('cache_runtime')->willReturn($cacheProphecy->reveal());
        $cacheProphecy->get(Argument::cetera())->willReturn(false);
        $cacheProphecy->set(Argument::cetera())->willReturn(false);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * Prepare a mock database setup for a Doctrine connection
     * and return an array of all prophets to set expectations upon.
     *
     * @param string $tableName
     * @return array
     */
    protected function mockDatabaseConnection($tableName = 'fTable')
    {
        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->quote(Argument::cetera())->will(function ($arguments) {
            return "'" . $arguments[0] . "'";
        });
        $connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($arguments) {
            return '`' . $arguments[0] . '`';
        });

        $restrictionProphet = $this->prophesize(DefaultRestrictionContainer::class);
        $restrictionProphet->removeAll()->willReturn($restrictionProphet->reveal());
        $restrictionProphet->add(Argument::cetera())->willReturn($restrictionProphet->reveal());

        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->expr()->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal())
        );
        $queryBuilderProphet->getRestrictions()->willReturn($restrictionProphet->reveal());
        $queryBuilderProphet->quoteIdentifier(Argument::cetera())->will(function ($arguments) {
            return '`' . $arguments[0] . '`';
        });

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getConnectionForTable($tableName)
            ->willReturn($connectionProphet->reveal());
        $connectionPoolProphet->getQueryBuilderForTable($tableName)
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphet->reveal());

        return [$queryBuilderProphet, $connectionPoolProphet, $connectionProphet, $restrictionProphet];
    }

    /**
     * Mock a doctrine database connection with all expectations
     * required for the processSelectField* tests.
     */
    protected function mockDatabaseConnectionForProcessSelectField()
    {
        list($queryBuilderProphet, $connectionPoolProphet) = $this->mockDatabaseConnection('foreignTable');

        /** @var Statement|ObjectProphecy $statementProphet */
        $statementProphet = $this->prophesize(Statement::class);
        $statementProphet->fetch()->shouldBeCalled();

        $queryBuilderProphet->select('foreignTable.uid')
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('foreignTable')
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('pages')
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->where('')
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->andWhere(' 1=1')
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->andWhere('`pages.uid` = `foreignTable.pid`')
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->execute()
            ->shouldBeCalled()
            ->willReturn($statementProphet->reveal());

        // Two instances are needed due to the push/pop behavior of addInstance()
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
    }

    /**
     * @test
     */
    public function addDataKeepExistingItems()
    {
        $input = [
            'tableName' => 'aTable',
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

        $expected = $input;
        $this->assertSame($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfAnItemIsNotAnArray()
    {
        $input = [
            'tableName' => 'aTable',
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

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1439288036);

        (new TcaSelectItems)->addData($input);
    }

    /**
     * @test
     */
    public function addDataTranslatesItemLabels()
    {
        $input = [
            'tableName' => 'aTable',
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
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');

        $languageService->sL('aLabel')->shouldBeCalled()->willReturn('translated');

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'translated';
        $expected['processedTca']['columns']['aField']['config']['items'][0][2] = null;
        $expected['processedTca']['columns']['aField']['config']['items'][0][3] = null;

        $expected['databaseRow']['aField'] = ['aValue'];

        $this->assertSame($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsIconFromItem()
    {
        $input = [
            'tableName' => 'aTable',
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
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = ['aValue'];

        $this->assertSame($expected, (new TcaSelectItems)->addData($input));
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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1439298496);

        (new TcaSelectItems)->addData($input);
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
                            'maxitems' => 99999,
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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');
        $languageService->sL(Argument::containingString('INVALID VALUE'))->willReturnArgument(0);

        $languageService->sL('aTitle')->shouldBeCalled()->willReturnArgument(0);
        $languageService->loadSingleTableDescription('aTable')->shouldBeCalled();

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            0 => [
                0 => 'aTitle',
                1 => 'aTable',
                2 => null,
                3 => [
                    'description' => 'aDescription',
                ],
            ]
        ];

        $this->assertSame($expected, (new TcaSelectItems)->addData($input));
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
                            'maxitems' => 99999,
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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');

        $languageService->sL('aLabel')->shouldBeCalled()->willReturnArgument(0);

        $expected = $input;
        $expected['databaseRow']['aField'] = ['aValue'];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            0 => [
                0 => 'aLabel',
                1 => 'aValue',
                2 => null,
                3 => null,
            ]
        ];

        $this->assertSame($expected, (new TcaSelectItems)->addData($input));
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
                        2 => null,
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
                    0 => [
                        0 => 'fooTableTitle',
                        1 => '--div--',
                        2 => null,
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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

        $result = (new TcaSelectItems)->addData($input);

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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

        $expectedItems = [
            0 => [
                0 => 'fooTableTitle aFlexFieldTitle dummy',
                1 => '--div--',
                2 => null,
                3 => null,
            ],
            1 => [
                0 => 'flexInputLabel (input1)',
                1 => 'fooTable:aFlexField;dummy;sDEF;input1',
                2 => 'empty-empty',
                3 => null,
            ],
        ];

        $result = (new TcaSelectItems)->addData($input);

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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.allow')->shouldBeCalled()->willReturn('allowMe');
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

        $result = (new TcaSelectItems)->addData($input);

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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deny')->shouldBeCalled()->willReturn('denyMe');
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

        $result = (new TcaSelectItems)->addData($input);

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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.allow')->shouldBeCalled()->willReturn('allowMe');
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

        $result = (new TcaSelectItems)->addData($input);

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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deny')->shouldBeCalled()->willReturn('denyMe');
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

        $result = (new TcaSelectItems)->addData($input);

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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

        $expectedItems = [
            0 => [
                0 => 'aLangTitle',
                1 => 42,
                2 => 'aFlag.gif',
                3 => null,
            ],
        ];

        $result = (new TcaSelectItems)->addData($input);

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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

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
                2 => 'empty-empty',
                3 => [ 'description' => 'aDescription' ],
            ],
        ];

        $result = (new TcaSelectItems)->addData($input);

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

        $iconFactoryProphecy = $this->prophesize(IconRegistry::class);
        GeneralUtility::setSingletonInstance(IconRegistry::class, $iconFactoryProphecy->reveal());

        /** @var ModuleLoader|ObjectProphecy $moduleLoaderProphecy */
        $moduleLoaderProphecy = $this->prophesize(ModuleLoader::class);
        GeneralUtility::addInstance(ModuleLoader::class, $moduleLoaderProphecy->reveal());
        $moduleLoaderProphecy->load([])->shouldBeCalled();
        $moduleLoaderProphecy->modListGroup = [
            'aModule',
        ];
        $moduleLoaderProphecy->modules = [
            'aModule' => [
                'iconIdentifier' => 'empty-empty'
            ]
        ];
        $moduleLoaderProphecy->getLabelsForModule('aModule')->shouldBeCalled()->willReturn([
            'shortdescription' => 'aModuleTabLabel',
            'description' => 'aModuleTabDescription',
            'title' => 'aModuleLabel'
        ]);

        $expectedItems = [
            0 => [
                0 => 'aModuleLabel',
                1 => 'aModule',
                2 => 'empty-empty',
                3 => [
                    'title' => 'aModuleTabLabel',
                    'description' => 'aModuleTabDescription',
                ],
            ],
        ];

        $result = (new TcaSelectItems)->addData($input);

        $result['processedTca']['columns']['aField']['config']['items'][0][2] = str_replace([CR, LF, "\t"], '', $result['processedTca']['columns']['aField']['config']['items'][0][2]);
        $this->assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataAddsFileItemsWithConfiguredFileFolder()
    {
        $directory = $this->getUniqueId('typo3temp/var/tests/test-') . '/';
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

        mkdir(Environment::getPublicPath() . '/' . $directory);
        $this->testFilesToDelete[] = Environment::getPublicPath() . '/' . $directory;
        touch(Environment::getPublicPath() . '/' . $directory . 'anImage.gif');
        touch(Environment::getPublicPath() . '/' . $directory . 'aFile.txt');
        mkdir(Environment::getPublicPath() . '/' . $directory . '/subdir');
        touch(Environment::getPublicPath() . '/' . $directory . '/subdir/anotherImage.gif');

        $expectedItems = [
            0 => [
                0 => 'anImage.gif',
                1 => 'anImage.gif',
                2 => Environment::getPublicPath() . '/' . $directory . 'anImage.gif',
                3 => null,
            ],
            1 => [
                0 => 'subdir/anotherImage.gif',
                1 => 'subdir/anotherImage.gif',
                2 => Environment::getPublicPath() . '/' . $directory . 'subdir/anotherImage.gif',
                3 => null,
            ],
        ];

        $result = (new TcaSelectItems)->addData($input);

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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1479399227);
        (new TcaSelectItems)->addData($input);
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
                            'maxitems' => 99999,
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

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'][1] = [
            0 => 'addMe',
            1 => '1',
            null,
            null,
        ];

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
                            'maxitems' => 99999,
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

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'][1] = [
            0 => 'addMe',
            1 => 'keep',
            null,
            null,
        ];

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * Data provider
     */
    public function addDataReplacesMarkersInForeignTableClauseDataProvider()
    {
        return [
            'replace REC_FIELD' => [
                'AND fTable.title=\'###REC_FIELD_rowField###\'',
                [
                    ['fTable.title=\'rowFieldValue\''],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
                [],
            ],
            'replace REC_FIELD within FlexForm' => [
                'AND fTable.title=###REC_FIELD_rowFieldFlexForm###',
                [
                    ['fTable.title=\'rowFieldFlexFormValue\''],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
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
                [
                    ['fTable.title=\'rowFieldValue\''],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
                [],
            ],
            'replace REC_FIELD fullQuoteWithArray' => [
                'AND fTable.title=###REC_FIELD_rowFieldThree###',
                [
                    ['fTable.title=\'rowFieldThreeValue\''],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
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
                [
                    ['fTable.title=\'rowFieldValue\' AND fTable.pid=\'rowFieldTwoValue\''],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
                [],
            ],
            'replace CURRENT_PID' => [
                'AND fTable.uid=###CURRENT_PID###',
                [
                    ['fTable.uid=43'],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
                [],
            ],
            'replace CURRENT_PID within FlexForm' => [
                'AND fTable.uid=###CURRENT_PID###',
                [
                    ['fTable.uid=77'],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
                [
                    'flexParentDatabaseRow' => [
                        'pid' => '77',
                    ],
                ],
            ],
            'replace CURRENT_PID integer cast' => [
                'AND fTable.uid=###CURRENT_PID###',
                [
                    ['fTable.uid=431'],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
                [
                    'effectivePid' => '431string',
                ],
            ],
            'replace THIS_UID' => [
                'AND fTable.uid=###THIS_UID###',
                [
                    ['fTable.uid=42'],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
                [],
            ],
            'replace THIS_UID integer cast' => [
                'AND fTable.uid=###THIS_UID###',
                [
                    ['fTable.uid=421'],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
                [
                    'databaseRow' => [
                        'uid' => '421string',
                    ],
                ],
            ],
            'replace SITEROOT' => [
                'AND fTable.uid=###SITEROOT###',
                [
                    ['fTable.uid=44'],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
                [],
            ],
            'replace SITEROOT integer cast' => [
                'AND fTable.uid=###SITEROOT###',
                [
                    ['fTable.uid=441'],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
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
                [
                    ['fTable.uid=45'],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
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
                [
                    ['fTable.uid=451'],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
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
                [
                    ['fTable.uid=\'46\''],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
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
                [
                    ['fTable.uid IN (47,48)'],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
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
                [
                    ['fTable.uid IN (471,481)'],
                    [' 1=1'],
                    ['`pages.uid` = `fTable.pid`']
                ],
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

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $fileRepositoryProphecy = $this->prophesize(FileRepository::class);
        $fileRepositoryProphecy->findByRelation(Argument::cetera())->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(FileRepository::class, $fileRepositoryProphecy->reveal());

        list($queryBuilderProphet, $connectionPoolProphet) = $this->mockDatabaseConnection();

        /** @var Statement|ObjectProphecy $statementProphet */
        $statementProphet = $this->prophesize(Statement::class);

        $queryBuilderProphet->select('fTable.uid')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('fTable')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('pages')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->where(...array_shift($expectedWhere))->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->execute()->shouldBeCalled()->willReturn($statementProphet->reveal());

        while ($constraint = array_shift($expectedWhere)) {
            $queryBuilderProphet->andWhere(...$constraint)
                ->shouldBeCalled()
                ->willReturn($queryBuilderProphet->reveal());
        }

        // Two instances are needed due to the push/pop behavior of addInstance()
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        (new TcaSelectItems)->addData($input);
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

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1439569743);

        (new TcaSelectItems)->addData($input);
    }

    /**
     * @test
     */
    public function addDataForeignTableSplitsGroupOrderAndLimit()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 42,
            'databaseRow' => [
                'uid' => 23,
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'fTable',
                            'foreign_table_where' => '
                                AND ftable.uid=1
                                GROUP BY groupField1, groupField2
                                ORDER BY orderField
                                LIMIT 1,2',
                        ],
                    ],
                ]
            ],
            'rootline' => [],
        ];

        $GLOBALS['TCA']['fTable'] = [];

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $fileRepositoryProphecy = $this->prophesize(FileRepository::class);
        $fileRepositoryProphecy->findByRelation(Argument::cetera())->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(FileRepository::class, $fileRepositoryProphecy->reveal());

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        list($queryBuilderProphet, $connectionPoolProphet) = $this->mockDatabaseConnection();

        /** @var Statement|ObjectProphecy $statementProphet */
        $statementProphet = $this->prophesize(Statement::class);

        $queryBuilderProphet->select('fTable.uid')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('fTable')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('pages')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->groupBy('groupField1', 'groupField2')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->addOrderBy('orderField', null)->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->setFirstResult(1)->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->setMaxResults(2)->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->where('ftable.uid=1')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->andWhere(' 1=1')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->andWhere('`pages.uid` = `fTable.pid`')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->execute()->shouldBeCalled()->willReturn($statementProphet->reveal());

        // Two instances are needed due to the push/pop behavior of addInstance()
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        (new TcaSelectItems)->addData($input);
    }

    /**
     * @test
     */
    public function addDataForeignTableQueuesFlashMessageOnDatabaseError()
    {
        $input = [
            'databaseRow' => [
                'uid' => 23,
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'effectivePid' => 42,
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
                            'maxitems' => 99999,
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

        list($queryBuilderProphet, $connectionPoolProphet) = $this->mockDatabaseConnection();

        /** @var Statement|ObjectProphecy $statementProphet */
        $statementProphet = $this->prophesize(Statement::class);

        $queryBuilderProphet->select('fTable.uid')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('fTable')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('pages')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->where('')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->andWhere(' 1=1')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->andWhere('`pages.uid` = `fTable.pid`')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());

        $prevException = new DBALException('Invalid table name', 1476045274);
        $exception = new DBALException('Driver error', 1476045971, $prevException);

        $queryBuilderProphet->execute()->shouldBeCalled()->willThrow($exception);

        // Two instances are needed due to the push/pop behavior of addInstance()
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function addDataForeignTableHandlesForeignTableRows()
    {
        $input = [
            'databaseRow' => [
                'uid' => 5,
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'effectivePid' => 42,
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'fTable',
                            'foreign_table_prefix' => 'aPrefix',
                            'items' => [],
                            'maxitems' => 99999,
                        ],
                    ],
                ]
            ],
            'rootline' => [],
        ];

        $GLOBALS['TCA']['fTable'] = [
            'ctrl' => [
                'label' => 'labelField',
            ],
            'columns' => [],
        ];

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $fileRepositoryProphecy = $this->prophesize(FileRepository::class);
        $fileRepositoryProphecy->findByRelation(Argument::cetera())->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(FileRepository::class, $fileRepositoryProphecy->reveal());

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        list($queryBuilderProphet, $connectionPoolProphet) = $this->mockDatabaseConnection();

        /** @var Statement|ObjectProphecy $statementProphet */
        $statementProphet = $this->prophesize(Statement::class);

        $queryBuilderProphet->select('fTable.uid', 'fTable.labelField')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('fTable')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('pages')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->where('')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->andWhere(' 1=1')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->andWhere('`pages.uid` = `fTable.pid`')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->execute()->shouldBeCalled()->willReturn($statementProphet->reveal());

        // Two instances are needed due to the push/pop behavior of addInstance()
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        $counter = 0;
        $statementProphet->fetch()->shouldBeCalled()->will(function ($args) use (&$counter) {
            $counter++;
            if ($counter >= 3) {
                return false;
            }
            return [
                'uid' => $counter,
                'pid' => 23,
                'labelField' => 'aLabel',
                'aValue' => 'bar,',
            ];
        });

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            0 => [
                0 => 'aPrefix[LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title]',
                1 => 1,
                2 => null,
                3 => null,
            ],
            1 => [
                0 => 'aPrefix[LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title]',
                1 => 2,
                2 => null,
                3 => null,
            ],
        ];

        $expected['databaseRow']['aField'] = [];

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function addDataForeignTableResolvesIconFromSelicon()
    {
        $input = [
            'databaseRow' => [
                'uid' => 5,
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'effectivePid' => 42,
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'fTable',
                            'maxitems' => 99999,
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
                'icon' => [],
            ],
        ];

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $fileRepositoryProphecy = $this->prophesize(FileRepository::class);
        $fileRepositoryProphecy->findByRelation(Argument::cetera())->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(FileRepository::class, $fileRepositoryProphecy->reveal());

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        list($queryBuilderProphet, $connectionPoolProphet) = $this->mockDatabaseConnection();

        /** @var Statement|ObjectProphecy $statementProphet */
        $statementProphet = $this->prophesize(Statement::class);

        $queryBuilderProphet->select('fTable.uid', 'fTable.icon')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('fTable')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from('pages')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->where('')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->andWhere(' 1=1')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->andWhere('`pages.uid` = `fTable.pid`')->shouldBeCalled()->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->execute()->shouldBeCalled()->willReturn($statementProphet->reveal());

        // Two instances are needed due to the push/pop behavior of addInstance()
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        // Query returns one row, then false on second call
        $foreignTableRowResultOne = [
            'uid' => 1,
            'pid' => 23,
            'icon' => 'foo.jpg',
        ];
        $statementProphet->fetch()->shouldBeCalled()->willReturn($foreignTableRowResultOne, false);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            0 => [
                0 => 'foo.jpg',
                1 => 1,
                2 => 'uploads/media/foo.jpg', // combination of selicon_field_path and the row value of field 'icon'
                3 => null,
            ],
        ];
        $expected['databaseRow']['aField'] = [];

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
                                2 => [
                                    0 => 'removeMe',
                                    1 => 0,
                                ],
                            ],
                            'maxitems' => 99999,
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

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        unset(
            $expected['processedTca']['columns']['aField']['config']['items'][1],
            $expected['processedTca']['columns']['aField']['config']['items'][2]
        );

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
                            'maxitems' => 99999,
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

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'] = [];

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
                            'maxitems' => 99999,
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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
                                2 => [
                                    0 => 'keep me',
                                    1 => 0,
                                    null,
                                    null,
                                ],
                            ],
                            'maxitems' => 99999,
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

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        unset($expected['processedTca']['columns']['aField']['config']['items'][1]);
        $expected['processedTca']['columns']['aField']['config']['items'] = array_values($expected['processedTca']['columns']['aField']['config']['items']);
        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesItemsByZeroValueRemoveItemsPageTsConfig()
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
                                    0 => 'keepMe',
                                    1 => 'keepMe2',
                                    null,
                                    null,
                                ],
                                2 => [
                                    0 => 'remove me',
                                    1 => 0,
                                ],
                            ],
                            'maxitems' => 99999,
                        ],
                    ],
                ]
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'removeItems' => '0',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        unset($expected['processedTca']['columns']['aField']['config']['items'][2]);
        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
                            'maxitems' => 99999,
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

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        unset($expected['processedTca']['columns']['aField']['config']['items'][1]);

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
                            'maxitems' => 99999,
                        ],
                    ],
                ]
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');
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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
                            'maxitems' => 99999,
                        ],
                    ],
                ]
            ],
        ];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->checkAuthMode('aTable', 'aField', 'keep', 'explicitAllow')->shouldBeCalled()->willReturn(true);
        $backendUserProphecy->checkAuthMode('aTable', 'aField', 'remove', 'explicitAllow')->shouldBeCalled()->willReturn(false);

        $expected = $input;
        $expected['databaseRow']['aField'] = ['keep'];
        unset($expected['processedTca']['columns']['aField']['config']['items'][1]);

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
        ];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(true);

        $expected = $input;
        $expected['databaseRow']['doktype'] = ['keep'];

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
        ];

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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
            'maxitems' => 99999,
        ];

        $this->assertSame($expected, (new TcaSelectItems)->addData($input));
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
                                    throw new \UnexpectedValueException('broken', 1476109436);
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

        (new TcaSelectItems)->addData($input);
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
                                throw new \UnexpectedValueException('anException', 1476109437);
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

        (new TcaSelectItems)->addData($input);
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
                            'maxitems' => 99999,
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
        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');

        $expected = $input;
        $expected['databaseRow']['aField'] = ['aValue'];
        $expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'labelOverride';

        $this->assertSame($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueSetsMmForeignRelationValues()
    {
        $GLOBALS['TCA']['foreignTable'] = [];

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $fileRepositoryProphecy = $this->prophesize(FileRepository::class);
        $fileRepositoryProphecy->findByRelation(Argument::cetera())->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(FileRepository::class, $fileRepositoryProphecy->reveal());

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        $this->mockDatabaseConnectionForProcessSelectField();

        $input = [
            'command' => 'edit',
            'tableName' => 'aTable',
            'effectivePid' => 23,
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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueSetsForeignRelationValues()
    {
        $GLOBALS['TCA']['foreignTable'] = [];

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $fileRepositoryProphecy = $this->prophesize(FileRepository::class);
        $fileRepositoryProphecy->findByRelation(Argument::cetera())->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(FileRepository::class, $fileRepositoryProphecy->reveal());

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        $this->mockDatabaseConnectionForProcessSelectField();

        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 23,
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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueRemovesInvalidDynamicValues()
    {
        $GLOBALS['TCA']['foreignTable'] = [];

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $fileRepositoryProphecy = $this->prophesize(FileRepository::class);
        $fileRepositoryProphecy->findByRelation(Argument::cetera())->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(FileRepository::class, $fileRepositoryProphecy->reveal());

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(1)->shouldBeCalled()->willReturn(' 1=1');

        $this->mockDatabaseConnectionForProcessSelectField();

        $relationHandlerProphecy = $this->prophesize(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());
        $relationHandlerProphecy->start(Argument::cetera())->shouldBeCalled();
        $relationHandlerProphecy->getValueArray(Argument::cetera())->shouldBeCalled()->willReturn([1]);

        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 23,
            'databaseRow' => [
                'uid' => 5,
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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueKeepsValuesFromStaticItems()
    {
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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueReturnsEmptyValueForSingleSelect()
    {
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
                            'maxitems' => 99999,
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [];

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueTrimsEmptyValueForMultiValueSelect()
    {
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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueDoesNotCallRelationManagerForStaticOnlyItems()
    {
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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueAddsInvalidValuesToItemsForSingleSelects()
    {
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue')->willReturn('INVALID VALUE "%s"');
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
                            'maxitems' => 99999,
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
        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueReturnsDuplicateValuesForMultipleSelect()
    {
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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }

    /**
     * @test
     */
    public function processSelectFieldValueReturnsUniqueValuesForMultipleSelect()
    {
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

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
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
                    'effectivePid' => 42,
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
                    'command' => 'edit',
                    'effectivePid' => 42,
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
                    'command' => 'edit',
                    'effectivePid' => 42,
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
                    'command' => 'edit',
                    'effectivePid' => 42,
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
        $foreignTable = $overrideRelationHandlerSettings['foreign_table'] ?? $input['processedTca']['columns']['aField']['config']['foreign_table'];
        $mmTable = $overrideRelationHandlerSettings['MM'] ?? $input['processedTca']['columns']['aField']['config']['MM'];
        $uid = $input['databaseRow']['uid'];
        $tableName = $input['tableName'];
        $fieldConfig = $input['processedTca']['columns']['aField']['config'];

        $GLOBALS['TCA'][$foreignTable] = [];

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $fileRepositoryProphecy = $this->prophesize(FileRepository::class);
        $fileRepositoryProphecy->findByRelation(Argument::cetera())->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(FileRepository::class, $fileRepositoryProphecy->reveal());

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(Argument::cetera())->willReturn(' 1=1');

        $this->mockDatabaseConnectionForProcessSelectField();

        /** @var RelationHandler|ObjectProphecy $relationHandlerProphecy */
        $relationHandlerProphecy = $this->prophesize(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());

        $relationHandlerProphecy->start($field, $foreignTable, $mmTable, $uid, $tableName, $fieldConfig)->shouldBeCalled();
        $relationHandlerProphecy->getValueArray()->shouldBeCalled()->willReturn($relationHandlerUids);

        $expected = $input;
        $expected['databaseRow']['aField'] = $relationHandlerUids;

        $this->assertEquals($expected, (new TcaSelectItems)->addData($input));
    }
}
