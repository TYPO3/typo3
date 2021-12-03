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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use Doctrine\DBAL\Result;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;
use TYPO3\CMS\Core\Tree\TableConfiguration\TableConfigurationTree;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for the TcaSelectTreeItems provider.
 *
 * This test only covers the renderTree() method. All other methods are covered by TcaSelectItemsTest
 *
 * @see TcaSelecItemsTest
 */
class TcaSelectTreeItemsTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var bool Reset singletons created by subject
     */
    protected bool $resetSingletonInstances = true;

    /**
     * Setup a mock database connection with expectations for
     * the testsuite.
     */
    protected function mockDatabaseConnection(): void
    {
        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->quote(Argument::cetera())->will(static function ($arguments) {
            return "'" . $arguments[0] . "'";
        });
        $connectionProphet->quoteIdentifier(Argument::cetera())->will(static function ($arguments) {
            return '`' . $arguments[0] . '`';
        });

        /** @var Result|ObjectProphecy $statementProphet */
        $statementProphet = $this->prophesize(Result::class);
        $statementProphet->fetchAssociative()->shouldBeCalled();

        $restrictionProphet = $this->prophesize(DefaultRestrictionContainer::class);
        $restrictionProphet->removeAll()->willReturn($restrictionProphet->reveal());
        $restrictionProphet->add(Argument::cetera())->willReturn($restrictionProphet->reveal());

        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->expr()->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal())
        );
        $queryBuilderProphet->getRestrictions()->willReturn($restrictionProphet->reveal());
        $queryBuilderProphet->quoteIdentifier(Argument::cetera())->will(static function ($arguments) {
            return '`' . $arguments[0] . '`';
        });

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getConnectionForTable('foreignTable')
            ->willReturn($connectionProphet->reveal());
        $connectionPoolProphet->getQueryBuilderForTable('foreignTable')
            ->shouldBeCalled()
            ->willReturn($queryBuilderProphet->reveal());

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
    public function addDataAddsTreeConfigurationForSelectTreeElement(): void
    {
        $GLOBALS['TCA']['foreignTable'] = [];

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $fileRepositoryProphecy = $this->prophesize(FileRepository::class);
        $fileRepositoryProphecy->findByRelation(Argument::cetera())->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(FileRepository::class, $fileRepositoryProphecy->reveal());

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(Argument::cetera())->willReturn(' 1=1');

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->mockDatabaseConnection();

        /** @var DatabaseTreeDataProvider|ObjectProphecy $treeDataProviderProphecy */
        $treeDataProviderProphecy = $this->prophesize(DatabaseTreeDataProvider::class);
        GeneralUtility::addInstance(DatabaseTreeDataProvider::class, $treeDataProviderProphecy->reveal());

        /** @var TableConfigurationTree|ObjectProphecy $treeDataProviderProphecy */
        $tableConfigurationTreeProphecy = $this->prophesize(TableConfigurationTree::class);
        GeneralUtility::addInstance(TableConfigurationTree::class, $tableConfigurationTreeProphecy->reveal());
        $tableConfigurationTreeProphecy->setDataProvider(Argument::cetera())->shouldBeCalled();
        $tableConfigurationTreeProphecy->setNodeRenderer(Argument::cetera())->shouldBeCalled();
        $tableConfigurationTreeProphecy->render()->shouldBeCalled()->willReturn(['fake', 'tree', 'data']);

        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 42,
            'databaseRow' => [
                'uid' => 5,
                'aField' => '1',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'treeConfig' => [
                                'childrenField' => 'childrenField',
                            ],
                            'foreign_table' => 'foreignTable',
                            'items' => [],
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
            'site' => null,
            'selectTreeCompileItems' => true,
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = ['1'];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            'fake', 'tree', 'data',
        ];
        self::assertEquals($expected, (new TcaSelectTreeItems())->addData($input));
    }

    /**
     * @test
     */
    public function addDataHandsPageTsConfigSettingsOverToTableConfigurationTree(): void
    {
        $GLOBALS['TCA']['foreignTable'] = [];

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $fileRepositoryProphecy = $this->prophesize(FileRepository::class);
        $fileRepositoryProphecy->findByRelation(Argument::cetera())->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(FileRepository::class, $fileRepositoryProphecy->reveal());

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(Argument::cetera())->willReturn(' 1=1');

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->mockDatabaseConnection();

        /** @var DatabaseTreeDataProvider|ObjectProphecy $treeDataProviderProphecy */
        $treeDataProviderProphecy = $this->prophesize(DatabaseTreeDataProvider::class);
        GeneralUtility::addInstance(DatabaseTreeDataProvider::class, $treeDataProviderProphecy->reveal());

        /** @var TableConfigurationTree|ObjectProphecy $treeDataProviderProphecy */
        $tableConfigurationTreeProphecy = $this->prophesize(TableConfigurationTree::class);
        GeneralUtility::addInstance(TableConfigurationTree::class, $tableConfigurationTreeProphecy->reveal());
        $tableConfigurationTreeProphecy->render()->willReturn([]);
        $tableConfigurationTreeProphecy->setDataProvider(Argument::cetera())->shouldBeCalled();
        $tableConfigurationTreeProphecy->setNodeRenderer(Argument::cetera())->shouldBeCalled();

        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 42,
            'databaseRow' => [
                'uid' => 5,
                'aField' => '1',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'treeConfig' => [
                                'childrenField' => 'childrenField',
                            ],
                            'foreign_table' => 'foreignTable',
                            'items' => [
                                [ 'static item foo', 1, 'foo-icon' ],
                                [ 'static item bar', 2, 'bar-icon' ],
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
                            'config.' => [
                                'treeConfig.' => [
                                    'startingPoints' => '42',
                                    'appearance.' => [
                                        'expandAll' => 1,
                                        'maxLevels' => 4,
                                        'nonSelectableLevels' => '0,1',
                                    ],
                                ],
                            ],
                            'altLabels.' => [
                                1 => 'alt static item foo',
                                2 => 'alt static item bar',
                            ],
                            'altIcons.' => [
                                1 => 'foo-alt-icon',
                                2 => 'bar-alt-icon',
                            ],
                        ],
                    ],
                ],
            ],
            'site' => null,
            'selectTreeCompileItems' => true,
        ];

        $result = (new TcaSelectTreeItems())->addData($input);

        $treeDataProviderProphecy->setStartingPoints([42])->shouldHaveBeenCalled();
        $treeDataProviderProphecy->setExpandAll(true)->shouldHaveBeenCalled();
        $treeDataProviderProphecy->setLevelMaximum(4)->shouldHaveBeenCalled();
        $treeDataProviderProphecy->setNonSelectableLevelList('0,1')->shouldHaveBeenCalled();

        $resultItems = $result['processedTca']['columns']['aField']['config']['items'];
        self::assertEquals('alt static item foo', $resultItems[0]['name']);
        self::assertEquals('foo-alt-icon', $resultItems[0]['icon']);
        self::assertEquals('alt static item bar', $resultItems[1]['name']);
        self::assertEquals('bar-alt-icon', $resultItems[1]['icon']);
    }

    public function addDataHandsSiteConfigurationOverToTableConfigurationTreeDataProvider(): array
    {
        return [
            'one setting' => [
                'inputStartingPoints' => '42,###SITE:categories.contentCategory###,12',
                'expectedStartingPoints' => [42, 4711, 12],
                'site' => new Site('some-site', 1, ['rootPageId' => 1, 'categories' => ['contentCategory' => 4711]]),
            ],
            'two settings' => [
                'inputStartingPoints' => '42,###SITE:categories.contentCategory###,12,###SITE:foobar###',
                'expectedStartingPoints' => [42, 4711, 12, 1],
                'site' => new Site('some-site', 1, ['rootPageId' => 1, 'foobar' => 1, 'categories' => ['contentCategory' => 4711]]),
            ],
        ];
    }

    /**
     * @dataProvider addDataHandsSiteConfigurationOverToTableConfigurationTreeDataProvider
     * @test
     */
    public function addDataHandsSiteConfigurationOverToTableConfigurationTree(string $inputStartingPoints, array $expectedStartingPoints, Site $site): void
    {
        $GLOBALS['TCA']['foreignTable'] = [];

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $fileRepositoryProphecy = $this->prophesize(FileRepository::class);
        $fileRepositoryProphecy->findByRelation(Argument::cetera())->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(FileRepository::class, $fileRepositoryProphecy->reveal());

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(Argument::cetera())->willReturn(' 1=1');

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->mockDatabaseConnection();

        /** @var DatabaseTreeDataProvider|ObjectProphecy $treeDataProviderProphecy */
        $treeDataProviderProphecy = $this->prophesize(DatabaseTreeDataProvider::class);
        GeneralUtility::addInstance(DatabaseTreeDataProvider::class, $treeDataProviderProphecy->reveal());

        /** @var TableConfigurationTree|ObjectProphecy $treeDataProviderProphecy */
        $tableConfigurationTreeProphecy = $this->prophesize(TableConfigurationTree::class);
        GeneralUtility::addInstance(TableConfigurationTree::class, $tableConfigurationTreeProphecy->reveal());
        $tableConfigurationTreeProphecy->render()->willReturn([]);
        $tableConfigurationTreeProphecy->setDataProvider(Argument::cetera())->shouldBeCalled();
        $tableConfigurationTreeProphecy->setNodeRenderer(Argument::cetera())->shouldBeCalled();

        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 42,
            'databaseRow' => [
                'uid' => 5,
                'aField' => '1',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'treeConfig' => [
                                'childrenField' => 'childrenField',
                                'startingPoints' => $inputStartingPoints,
                                'appearance' => [
                                    'expandAll' => true,
                                    'maxLevels' => 4,
                                ],
                            ],
                            'foreign_table' => 'foreignTable',
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
            'site' => $site,
            'selectTreeCompileItems' => true,
        ];

        $result = (new TcaSelectTreeItems())->addData($input);

        $treeDataProviderProphecy->setStartingPoints($expectedStartingPoints)->shouldHaveBeenCalled();
        $treeDataProviderProphecy->setExpandAll(true)->shouldHaveBeenCalled();
        $treeDataProviderProphecy->setLevelMaximum(4)->shouldHaveBeenCalled();
        $treeDataProviderProphecy->setNonSelectableLevelList('0')->shouldHaveBeenCalled();

        $resultFieldConfig = $result['processedTca']['columns']['aField']['config'];
        self::assertSame(implode(',', $expectedStartingPoints), $resultFieldConfig['treeConfig']['startingPoints']);
    }
}
