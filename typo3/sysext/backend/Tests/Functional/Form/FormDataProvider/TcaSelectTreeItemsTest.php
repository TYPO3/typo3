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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveEmptyRelations;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaBuilder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration\Fixtures\TreeDataProviderWithoutDatabaseFixture;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * This test only covers the specialties of the renderType `selectTree`. Common type `select` functionality is tested in
 * TcaSelectItemsTest.
 */
final class TcaSelectTreeItemsTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected array $testExtensionsToLoad = ['typo3/sysext/backend/Tests/Functional/Fixtures/Extensions/test_tca_select_tree_items'];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TcaSelectTreeItems/base.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
    }

    public static function initialFormRecordTitleDataProvider(): iterable
    {
        yield 'label' => [['label' => 'select_tree'], '1, 2'];
        yield 'forced alternative label' => [['label' => 'uid', 'label_alt' => 'select_tree', 'label_alt_force' => true], '1, 1, 2'];
    }

    #[Test]
    #[DataProvider('initialFormRecordTitleDataProvider')]
    public function initialFormPreservesAllSelectedValuesInRecordTitle(array $labelConfiguration, string $expectedTitle): void
    {
        $input = $this->initialFormInput();
        $input['isInlineChild'] = false;
        $input['databaseRow']['select_tree'] = '1,2';
        $input['processedTca']['ctrl'] = $labelConfiguration;
        $subject = $this->createSubject();

        $result = $subject->addData($this->addTcaSchemata($input));
        $recordTitleProvider = new TcaRecordTitle();
        $result = $recordTitleProvider->addData($result);

        self::assertSame($expectedTitle, $result['recordTitle']);
    }

    #[Test]
    public function initialFormLeavesRawTcaItemsUnchanged(): void
    {
        $input = $this->initialFormInput();
        $items = [['label' => 'Static', 'value' => -1]];
        $input['processedTca']['columns']['select_tree']['config']['items'] = $items;
        $input['pageTsConfig']['TCEFORM.']['tca_select_tree_items.']['select_tree.']['addItems.'] = ['7' => 'TSconfig'];

        $result = $this->createSubject()->addData($this->addTcaSchemata($input));

        self::assertSame($items, $result['processedTca']['columns']['select_tree']['config']['items']);
    }

    public static function initialFormWithoutForeignTableProbeDataProvider(): iterable
    {
        yield 'showIfEmpty' => [['showIfEmpty' => true], []];
        yield 'positive static item' => [['items' => [['label' => 'Static', 'value' => 7]]], []];
        yield 'TSconfig addition after keepItems' => [[], ['keepItems' => '1', 'addItems.' => ['7' => 'TSconfig']]];
    }

    #[Test]
    #[DataProvider('initialFormWithoutForeignTableProbeDataProvider')]
    public function initialFormSkipsForeignTableProbeWhenFieldIsAlreadyVisible(array $configuration, array $tsConfig): void
    {
        $input = $this->initialFormInput();
        $input['processedTca']['columns']['select_tree']['config'] += $configuration;
        $input['pageTsConfig']['TCEFORM.']['tca_select_tree_items.']['select_tree.'] = $tsConfig;
        $subject = $this->getMockBuilder(TcaSelectTreeItems::class)
            ->setConstructorArgs([$this->get(IconFactory::class)])
            ->onlyMethods(['buildForeignTableQueryBuilder'])
            ->getMock();
        $subject->expects($this->never())->method('buildForeignTableQueryBuilder');

        $result = $subject->addData($this->addTcaSchemata($input));
        $emptyRelationsProvider = new TcaColumnsRemoveEmptyRelations();
        $result = $emptyRelationsProvider->addData($result);

        self::assertArrayHasKey('select_tree', $result['processedTca']['columns']);
    }

    #[Test]
    public function initialFormChecksSelectableItemsWithoutResolvingThem(): void
    {
        $input = $this->initialFormInput();
        $input['pageTsConfig']['TCEFORM.']['tca_select_tree_items.']['select_tree.']['removeItems'] = '1';
        $subject = new TcaSelectTreeItems($this->get(IconFactory::class));
        $subject->injectConnectionPool($this->get(ConnectionPool::class));
        $subject->injectIconFactory($this->get(IconFactory::class));

        $result = $subject->addData($this->addTcaSchemata($input));

        self::assertTrue($result['processedTca']['columns']['select_tree']['_hasSelectableItems']);
        self::assertArrayNotHasKey('items', $result['processedTca']['columns']['select_tree']['config']);
    }

    public static function initialFormBoundedQueryDataProvider(): iterable
    {
        yield 'unrestricted' => [[]];
        yield 'keepItems matches only the last record' => [['keepItems' => '101']];
        yield 'removeItems excludes the first hundred records' => [['removeItems' => implode(',', range(1, 100))]];
    }

    /**
     * Performance regression: checking whether to hide a selectTree field must
     * not load its entire foreign table. Multiple tree fields over a large
     * pages table previously exhausted PHP memory before the AJAX tree load.
     *
     * The 101 records expose both unbounded loading and scans through rejected
     * batches. One LIMIT 1 query must find the last UID even with restrictive
     * TSconfig, and the initial form must not retain foreign items. These guards
     * protect performance deterministically; visibility alone would miss it.
     */
    #[Test]
    #[DataProvider('initialFormBoundedQueryDataProvider')]
    public function initialFormAvoidsPerformanceRegressionByStoppingAfterOneBoundedQuery(array $tsConfig): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('foreign_table');
        for ($uid = 6; $uid <= 101; $uid++) {
            $connection->insert('foreign_table', ['uid' => $uid, 'pid' => 1, 'title' => 'Item ' . $uid]);
        }
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$connection])
            ->onlyMethods(['executeQuery'])
            ->getMock();
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('joined_table.*')->from('foreign_table', 'joined_table');
        $queryBuilder->expects($this->once())->method('executeQuery')->willReturnCallback(
            static function () use ($connection, $queryBuilder) {
                self::assertNotNull($queryBuilder->getMaxResults(), 'The initial form must not load an unbounded relation.');
                self::assertSame(1, $queryBuilder->getMaxResults(), 'UID filters must be applied before LIMIT 1.');
                return $connection->executeQuery($queryBuilder->getSQL(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());
            }
        );
        $subject = $this->getMockBuilder(TcaSelectTreeItems::class)
            ->setConstructorArgs([$this->get(IconFactory::class)])
            ->onlyMethods(['buildForeignTableQueryBuilder'])
            ->getMock();
        $subject->expects($this->once())->method('buildForeignTableQueryBuilder')->willReturn($queryBuilder);
        $subject->injectIconFactory($this->get(IconFactory::class));

        $input = $this->initialFormInput();
        $input['pageTsConfig']['TCEFORM.']['tca_select_tree_items.']['select_tree.'] = $tsConfig;
        $result = $subject->addData($this->addTcaSchemata($input));

        self::assertTrue($result['processedTca']['columns']['select_tree']['_hasSelectableItems']);
        self::assertArrayNotHasKey('items', $result['processedTca']['columns']['select_tree']['config'], 'Retaining foreign items per tree field caused the memory regression.');
    }

    public static function initialFormRelationHandlingDataProvider(): iterable
    {
        yield 'available records' => [[], [], false, true, false];
        yield 'no records' => [['foreign_table_where' => ' AND uid > 5'], [], false, false, false];
        yield 'empty in debug mode' => [['foreign_table_where' => ' AND uid > 5'], [], true, true, true];
        yield 'showIfEmpty' => [['foreign_table_where' => ' AND uid > 5', 'showIfEmpty' => true], [], false, true, false];
        yield 'keepItems excludes all' => [[], ['keepItems' => ''], false, false, false];
        yield 'keepItems allows a later record' => [[], ['keepItems' => '3'], false, true, false];
        yield 'keepItems references a missing record' => [[], ['keepItems' => '999'], false, false, false];
        yield 'removeItems excludes all' => [[], ['removeItems' => '1,2,3,4,5'], false, false, false];
        yield 'configured SQL limit is preserved' => [['foreign_table_where' => ' ORDER BY uid LIMIT 1'], ['removeItems' => '1'], false, false, false];
        yield 'positive static item' => [['foreign_table_where' => ' AND uid > 5', 'items' => [['label' => 'Static', 'value' => 7]]], [], false, true, false];
        yield 'negative static item' => [['foreign_table_where' => ' AND uid > 5', 'items' => [['label' => 'Static', 'value' => -1]]], [], false, false, false];
        yield 'TSconfig item' => [['foreign_table_where' => ' AND uid > 5'], ['addItems.' => ['7' => 'TSconfig']], false, true, false];
        yield 'keepItems is applied before TSconfig additions, as in AJAX' => [['foreign_table_where' => ' AND uid > 5'], ['addItems.' => ['7' => 'TSconfig'], 'keepItems' => '1'], false, true, false];
        yield 'keepItems does not coerce a leading-zero UID' => [[], ['keepItems' => '03'], false, false, false];
        yield 'removeItems does not coerce leading-zero UIDs' => [[], ['removeItems' => '01,02,03,04,05'], false, true, false];
        yield 'filtered positive static item does not keep the field visible' => [['foreign_table_where' => ' AND uid > 5', 'items' => [['label' => 'Static', 'value' => 7]]], ['removeItems' => '7'], false, false, false];
    }

    #[Test]
    #[DataProvider('initialFormRelationHandlingDataProvider')]
    public function initialFormPreservesEmptyRelationHandling(array $configuration, array $tsConfig, bool $debug, bool $visible, bool $readOnly): void
    {
        $input = $this->initialFormInput();
        $input['databaseRow']['select_tree'] = '3';
        $input['processedTca']['columns']['select_tree']['config'] = array_replace($input['processedTca']['columns']['select_tree']['config'], $configuration);
        $input['pageTsConfig']['TCEFORM.']['tca_select_tree_items.']['select_tree.'] = $tsConfig;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = $debug;
        $subject = new TcaSelectTreeItems($this->get(IconFactory::class));
        $subject->injectConnectionPool($this->get(ConnectionPool::class));
        $subject->injectIconFactory($this->get(IconFactory::class));

        $emptyRelationsProvider = new TcaColumnsRemoveEmptyRelations();
        $result = $emptyRelationsProvider->addData($subject->addData($this->addTcaSchemata($input)));

        self::assertSame(['3'], $result['databaseRow']['select_tree']);
        self::assertSame($visible, isset($result['processedTca']['columns']['select_tree']));
        if ($visible) {
            self::assertSame($readOnly, $result['processedTca']['columns']['select_tree']['config']['readOnly'] ?? false);
            self::assertSame($readOnly, isset($result['processedTca']['columns']['select_tree']['config']['fieldInformation']['noSelectableItemsAvailable']));
        }
    }

    #[Test]
    public function initialFormContinuesAfterACompletelyFilteredBatch(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('foreign_table');
        for ($uid = 6; $uid <= 101; $uid++) {
            $connection->insert('foreign_table', ['uid' => $uid, 'pid' => 1, 'title' => 'Item ' . $uid]);
        }
        $input = $this->initialFormInput();
        $input['processedTca']['columns']['select_tree']['config']['authMode'] = 'explicitAllow';
        $GLOBALS['BE_USER']->user['admin'] = 0;
        $GLOBALS['BE_USER']->groupData['explicit_allowdeny'] = 'tca_select_tree_items:select_tree:101';
        $this->get(ConnectionPool::class)->getConnectionForTable('pages')->update('pages', ['perms_everybody' => 1], ['uid' => 1]);
        $subject = new TcaSelectTreeItems($this->get(IconFactory::class));
        $subject->injectConnectionPool($this->get(ConnectionPool::class));

        $result = $subject->addData($this->addTcaSchemata($input));

        self::assertTrue($result['processedTca']['columns']['select_tree']['_hasSelectableItems']);
    }

    #[Test]
    public function initialFormSkipsWorkspaceDeletePlaceholders(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('pages');
        $connection->insert('pages', ['uid' => 2, 'pid' => 0, 'title' => 'Deleted in workspace', 't3ver_oid' => 1, 't3ver_wsid' => 1, 't3ver_state' => 2]);
        $GLOBALS['BE_USER']->workspace = 1;
        $input = $this->initialFormInput();
        $input['processedTca']['columns']['select_tree']['config']['foreign_table'] = 'pages';
        $subject = new TcaSelectTreeItems($this->get(IconFactory::class));
        $subject->injectConnectionPool($this->get(ConnectionPool::class));

        $result = $subject->addData($this->addTcaSchemata($input));

        self::assertFalse($result['processedTca']['columns']['select_tree']['_hasSelectableItems']);
    }

    private function initialFormInput(): array
    {
        return [
            'tableName' => 'tca_select_tree_items',
            'effectivePid' => 1,
            'databaseRow' => ['uid' => 1, 'select_tree' => ''],
            'processedTca' => [
                'columns' => [
                    'select_tree' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'foreign_table' => 'foreign_table',
                            'foreign_table_where' => ' ORDER BY uid',
                        ],
                    ],
                ],
            ],
            'rootline' => [],
            'site' => null,
            'selectTreeCompileItems' => false,
        ];
    }

    #[Test]
    public function addDataAddsTreeConfigurationForSelectTreeElement(): void
    {
        $input = [
            'tableName' => 'tca_select_tree_items',
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 1,
                'select_tree' => '1',
            ],
            'processedTca' => [
                'columns' => [
                    'select_tree' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'treeConfig' => [
                                'childrenField' => 'children_field',
                                'startingPoints' => 1,
                            ],
                            'foreign_table' => 'foreign_table',
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
        $expected['databaseRow']['select_tree'] = ['1'];
        $expected['processedTca']['columns']['select_tree']['config']['items'] = [
            [
                'identifier' => '1',
                'name' => 'Item 1',
                'icon' => 'default-not-found',
                'overlayIcon' => '',
                'depth' => 0,
                'hasChildren' => true,
                'selectable' => true,
                'checked' => true,
            ],
            [
                'identifier' => '2',
                'name' => 'Item 2',
                'icon' => 'default-not-found',
                'overlayIcon' => '',
                'depth' => 1,
                'hasChildren' => true,
                'selectable' => true,
                'checked' => false,
            ],
            [
                'identifier' => '4',
                'name' => 'Item 4',
                'icon' => 'default-not-found',
                'overlayIcon' => '',
                'depth' => 2,
                'hasChildren' => false,
                'selectable' => true,
                'checked' => false,
            ],
            [
                'identifier' => '3',
                'name' => 'Item 3',
                'icon' => 'default-not-found',
                'overlayIcon' => '',
                'depth' => 1,
                'hasChildren' => true,
                'selectable' => true,
                'checked' => false,
            ],
            [
                'identifier' => '5',
                'name' => 'Item 5',
                'icon' => 'default-not-found',
                'overlayIcon' => '',
                'depth' => 2,
                'hasChildren' => false,
                'selectable' => true,
                'checked' => false,
            ],
        ];

        $selectItems = (new TcaSelectTreeItems($this->get(IconFactory::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $result = $selectItems->addData($this->addTcaSchemata($input));

        foreach ($expected as $key => $value) {
            self::assertEquals($value, $result[$key]);
        }
    }

    #[Test]
    public function addDataKeepsDatabaseValuesForDataProviderWithoutForeignTable(): void
    {
        $input = [
            'tableName' => 'tca_select_tree_items',
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 1,
                'select_tree' => 'de,ch',
            ],
            'processedTca' => [
                'columns' => [
                    'select_tree' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'treeConfig' => [
                                'dataProvider' => TreeDataProviderWithoutDatabaseFixture::class,
                            ],
                            'items' => [],
                        ],
                    ],
                ],
            ],
            'rootline' => [],
            'site' => null,
            'selectTreeCompileItems' => false,
        ];

        $selectItems = (new TcaSelectTreeItems($this->get(IconFactory::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $result = $selectItems->addData($this->addTcaSchemata($input));

        self::assertSame(['de', 'ch'], $result['databaseRow']['select_tree']);
    }

    private function addTcaSchemata(array $result): array
    {
        if (isset($result['tcaSchemata'])) {
            return $result;
        }
        $tca = $result['fullTca'] ?? $GLOBALS['TCA'];
        if (!isset($tca[$result['tableName']]) && isset($result['processedTca'])) {
            $tca[$result['tableName']] = $result['processedTca'];
        }
        $result['tcaSchemata'] = $this->get(TcaSchemaBuilder::class)->buildFromStructure($tca);
        return $result;
    }

    #[Test]
    public function addDataHandsPageTsConfigSettingsOverToTableConfigurationTree(): void
    {
        $input = [
            'tableName' => 'tca_select_tree_items',
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 1,
                'select_tree' => '1',
            ],
            'processedTca' => [
                'columns' => [
                    'select_tree' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'treeConfig' => [
                                'childrenField' => 'children_field',
                            ],
                            'foreign_table' => 'foreign_table',
                            'items' => [
                                ['label' => 'static empty value item', 'value' => '', 'icon' => 'foo-icon'],
                                ['label' => 'static item foo', 'value' => 1, 'icon' => 'foo-icon'],
                                ['label' => 'static item bar', 'value' => 2, 'icon' => 'bar-icon'],
                            ],
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'tca_select_tree_items.' => [
                        'select_tree.' => [
                            'config.' => [
                                'treeConfig.' => [
                                    'startingPoints' => '1',
                                    'appearance.' => [
                                        'expandAll' => 1,
                                        'maxLevels' => 1,
                                        'nonSelectableLevels' => '0,1',
                                    ],
                                ],
                            ],
                            'altLabels' => 'alt static empty value item',
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

        $selectItems = (new TcaSelectTreeItems($this->get(IconFactory::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $result = $selectItems->addData($this->addTcaSchemata($input));

        $resultItems = $result['processedTca']['columns']['select_tree']['config']['items'];
        $expectedItems = [
            [
                'identifier' => '',
                'name' => 'alt static empty value item',
                'icon' => 'foo-icon',
                'iconOverlay' => '', // @todo for non-static items this is called `overlayIcon`. Bug?
                'depth' => 0,
                'hasChildren' => false,
                'selectable' => true,
                'checked' => false,
            ],
            [
                'identifier' => 1,
                'name' => 'alt static item foo',
                'icon' => 'foo-alt-icon',
                'iconOverlay' => '', // @todo for non-static items this is called `overlayIcon`. Bug?
                'depth' => 0,
                'hasChildren' => false,
                'selectable' => true,
                'checked' => true,
            ],
            [
                'identifier' => 2,
                'name' => 'alt static item bar',
                'icon' => 'bar-alt-icon',
                'iconOverlay' => '', // @todo for non-static items this is called `overlayIcon`. Bug?
                'depth' => 0,
                'hasChildren' => false,
                'selectable' => true,
                'checked' => false,
            ],
            [
                'identifier' => '1',
                'name' => 'Item 1',
                'icon' => 'default-not-found',
                'overlayIcon' => '',
                'depth' => 0,
                'hasChildren' => true,
                'selectable' => false,
                'checked' => false,
            ],
            [
                'identifier' => '2',
                'name' => 'Item 2',
                'icon' => 'default-not-found',
                'overlayIcon' => '',
                'depth' => 1,
                'hasChildren' => false,
                'selectable' => false,
                'checked' => false,
            ],
            [
                'identifier' => '3',
                'name' => 'Item 3',
                'icon' => 'default-not-found',
                'overlayIcon' => '',
                'depth' => 1,
                'hasChildren' => false,
                'selectable' => false,
                'checked' => false,
            ],
        ];

        self::assertTrue($result['processedTca']['columns']['select_tree']['config']['treeConfig']['appearance']['expandAll']);
        self::assertEquals($expectedItems, $resultItems);
    }

    public static function addDataHandsSiteConfigurationOverToTableConfigurationTreeDataProvider(): array
    {
        return [
            'one setting' => [
                'inputStartingPoints' => '42,###SITE:categories.contentCategory###,12',
                'expectedStartingPoints' => '42,4711,12',
                'site' => new Site('some-site', 1, ['rootPageId' => 1, 'categories' => ['contentCategory' => 4711]]),
            ],
            'two settings' => [
                'inputStartingPoints' => '42,###SITE:categories.contentCategory###,12,###SITE:foobar###',
                'expectedStartingPoints' => '42,4711,12,1',
                'site' => new Site('some-site', 1, ['rootPageId' => 1, 'foobar' => 1, 'categories' => ['contentCategory' => 4711]]),
            ],
        ];
    }

    #[DataProvider('addDataHandsSiteConfigurationOverToTableConfigurationTreeDataProvider')]
    #[Test]
    public function addDataHandsSiteConfigurationOverToTableConfigurationTree(string $inputStartingPoints, string $expectedStartingPoints, Site $site): void
    {
        $input = [
            'tableName' => 'tca_select_tree_items',
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 1,
                'select_tree' => '1',
            ],
            'processedTca' => [
                'columns' => [
                    'select_tree' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectTree',
                            'treeConfig' => [
                                'childrenField' => 'children_field',
                                'startingPoints' => $inputStartingPoints,
                                'appearance' => [
                                    'expandAll' => true,
                                    'maxLevels' => 4,
                                ],
                            ],
                            'foreign_table' => 'foreign_table',
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
            'site' => $site,
            'selectTreeCompileItems' => true,
        ];

        $selectItems = (new TcaSelectTreeItems($this->get(IconFactory::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $result = $selectItems->addData($this->addTcaSchemata($input));

        $resultStartingPoints = $result['processedTca']['columns']['select_tree']['config']['treeConfig']['startingPoints'];
        self::assertSame($expectedStartingPoints, $resultStartingPoints);
    }

    private function createSubject(): TcaSelectTreeItems
    {
        $subject = new TcaSelectTreeItems($this->get(IconFactory::class));
        $subject->injectIconFactory($this->get(IconFactory::class));
        $subject->injectConnectionPool($this->get(ConnectionPool::class));
        return $subject;
    }
}
