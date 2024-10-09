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
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * This test only covers the specialties of the renderType `selectTree`. Common type `select` functionality is tested in
 * TcaSelectItemsTest.
 */
final class TcaSelectTreeItemsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/backend/Tests/Functional/Fixtures/Extensions/test_tca_select_tree_items'];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TcaSelectTreeItems/base.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
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
        $result = $selectItems->addData($input);

        self::assertEquals($expected, $result);
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
        $result = $selectItems->addData($input);

        $resultItems = $result['processedTca']['columns']['select_tree']['config']['items'];
        $expectedItems = [
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
        $result = $selectItems->addData($input);

        $resultStartingPoints = $result['processedTca']['columns']['select_tree']['config']['treeConfig']['startingPoints'];
        self::assertSame($expectedStartingPoints, $resultStartingPoints);
    }
}
