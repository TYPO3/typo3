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

use Doctrine\DBAL\Driver\Statement;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;
use TYPO3\CMS\Core\Tree\TableConfiguration\TableConfigurationTree;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tests for the TcaSelectTreeItems provider.
 *
 * This test only covers the renderTree() method. All other methods are covered TcaSelecItemsTest
 *
 * @see TcaSelecItemsTest
 */
class TcaSelectTreeItemsTest extends UnitTestCase
{
    /**
     * @var TcaSelectTreeItems
     */
    protected $subject;

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * Initializes the mock object.
     */
    public function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->subject = new TcaSelectTreeItems();
    }

    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * Setup a mock database connection with expectations for
     * the testsuite.
     */
    protected function mockDatabaseConnection()
    {
        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->quote(Argument::cetera())->will(function ($arguments) {
            return "'" . $arguments[0] . "'";
        });
        $connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($arguments) {
            return '`' . $arguments[0] . '`';
        });

        /** @var Statement|ObjectProphecy $statementProphet */
        $statementProphet = $this->prophesize(Statement::class);
        $statementProphet->fetch()->shouldBeCalled();

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
    public function addDataAddsTreeConfigurationForExtJs()
    {
        $GLOBALS['TCA']['foreignTable'] = [];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(Argument::cetera())->willReturn(' 1=1');

        $this->mockDatabaseConnection();

        /** @var  DatabaseTreeDataProvider|ObjectProphecy $treeDataProviderProphecy */
        $treeDataProviderProphecy = $this->prophesize(DatabaseTreeDataProvider::class);
        GeneralUtility::addInstance(DatabaseTreeDataProvider::class, $treeDataProviderProphecy->reveal());

        /** @var  TableConfigurationTree|ObjectProphecy $treeDataProviderProphecy */
        $tableConfigurationTreeProphecy = $this->prophesize(TableConfigurationTree::class);
        GeneralUtility::addInstance(TableConfigurationTree::class, $tableConfigurationTreeProphecy->reveal());
        $tableConfigurationTreeProphecy->setDataProvider(Argument::cetera())->shouldBeCalled();
        $tableConfigurationTreeProphecy->setNodeRenderer(Argument::cetera())->shouldBeCalled();
        $tableConfigurationTreeProphecy->render()->shouldBeCalled()->willReturn(['fake', 'tree', 'data']);

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '1'
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'treeConfig' => [
                                'childrenField' => 'childrenField'
                            ],
                            'foreign_table' => 'foreignTable',
                            'items' => [],
                            'maxitems' => 1
                        ],
                    ],
                ],
            ],
            'selectTreeCompileItems' => true,
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = ['1'];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            'fake', 'tree', 'data',
        ];
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataHandsPageTsConfigSettingsOverToTableConfigurationTree()
    {
        $GLOBALS['TCA']['foreignTable'] = [];

        /** @var BackendUserAuthentication|ObjectProphecy $backendUserProphecy */
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $backendUserProphecy->getPagePermsClause(Argument::cetera())->willReturn(' 1=1');

        $this->mockDatabaseConnection();

        /** @var DatabaseTreeDataProvider|ObjectProphecy $treeDataProviderProphecy */
        $treeDataProviderProphecy = $this->prophesize(DatabaseTreeDataProvider::class);
        GeneralUtility::addInstance(DatabaseTreeDataProvider::class, $treeDataProviderProphecy->reveal());

        /** @var  TableConfigurationTree|ObjectProphecy $treeDataProviderProphecy */
        $tableConfigurationTreeProphecy = $this->prophesize(TableConfigurationTree::class);
        GeneralUtility::addInstance(TableConfigurationTree::class, $tableConfigurationTreeProphecy->reveal());
        $tableConfigurationTreeProphecy->render()->willReturn([]);
        $tableConfigurationTreeProphecy->setDataProvider(Argument::cetera())->shouldBeCalled();
        $tableConfigurationTreeProphecy->setNodeRenderer(Argument::cetera())->shouldBeCalled();

        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '1'
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'treeConfig' => [
                                'childrenField' => 'childrenField'
                            ],
                            'foreign_table' => 'foreignTable',
                            'items' => [],
                            'maxitems' => 1
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
                                    'rootUid' => '42',
                                    'appearance.' => [
                                        'expandAll' => 1,
                                        'maxLevels' => 4,
                                        'nonSelectableLevels' => '0,1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'selectTreeCompileItems' => true,
        ];

        $this->subject->addData($input);

        $treeDataProviderProphecy->setRootUid(42)->shouldHaveBeenCalled();
        $treeDataProviderProphecy->setExpandAll(true)->shouldHaveBeenCalled();
        $treeDataProviderProphecy->setLevelMaximum(4)->shouldHaveBeenCalled();
        $treeDataProviderProphecy->setNonSelectableLevelList('0,1')->shouldHaveBeenCalled();
    }
}
