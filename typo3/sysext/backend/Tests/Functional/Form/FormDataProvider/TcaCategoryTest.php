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
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaCategory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaBuilder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaCategoryTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/CategoryRelations.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function addDataOnlyWorksForTypeCategory(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '2',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $input = $this->addTcaSchemata($input);
        // We expect no change to the input data
        $expected = $input;

        self::assertEquals($expected, $this->createSubject()->addData($input));
    }

    #[Test]
    public function addDataChecksForTargetRenderType(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '2',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => $this->getFieldConfiguration([
                            'type' => 'category',
                            'renderType' => 'someRenderType',
                        ]),
                    ],
                ],
            ],
        ];

        $input = $this->addTcaSchemata($input);
        // We expect no change to the input data
        $expected = $input;

        self::assertEquals($expected, $this->createSubject()->addData($input));
    }

    #[Test]
    public function addDataInitializesDefaultFieldConfiguration(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '2',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => $this->getFieldConfiguration(['type' => 'category']),
                    ],
                ],
            ],
            'rootline' => [],
            'site' => null,
        ];
        $input = $this->addTcaSchemata($input);

        $result = $this->createSubject()->addData($input);

        // Verify items are populated (flat item list from foreign table)
        self::assertNotEmpty($result['processedTca']['columns']['categories']['config']['items']);

        // Compare structural result without items (tested separately in AJAX tests)
        unset($result['processedTca']['columns']['categories']['config']['items']);
        $expected = $input;
        $expected['databaseRow']['categories'] = ['2'];
        $expected['processedTca']['columns']['categories']['config']['treeConfig'] = [
            'parentField' => 'parent',
            'appearance' => [
                'expandAll' => true,
                'showHeader' => true,
                'maxLevels' => 99,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function addDataOverridesDefaultFieldConfigurationByTSconfig(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '2',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => $this->getFieldConfiguration(['type' => 'category']),
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'tt_content.' => [
                        'categories.' => [
                            'config.' => [
                                'treeConfig.' => [
                                    'startingPoints' => '1',
                                    'appearance.' => [
                                        'expandAll' => false,
                                        'maxLevels' => 10,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'rootline' => [],
            'site' => null,
        ];
        $input = $this->addTcaSchemata($input);

        $result = $this->createSubject()->addData($input);
        self::assertNotEmpty($result['processedTca']['columns']['categories']['config']['items']);
        unset($result['processedTca']['columns']['categories']['config']['items']);

        $expected = $input;
        $expected['databaseRow']['categories'] = ['2'];
        $expected['processedTca']['columns']['categories']['config']['treeConfig'] = [
            'parentField' => 'parent',
            'startingPoints' => '1',
            'appearance' => [
                'expandAll' => false,
                'showHeader' => true,
                'maxLevels' => 10,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public static function addDataOverridesDefaultFieldConfigurationBySiteConfigDataProvider(): array
    {
        return [
            'one setting' => [
                'inputStartingPoints' => '42,###SITE:categories.contentCategory###,12',
                'expectedStartingPoints' => '42,4711,12',
                'site' => new Site('some-site', 1, ['rootPageId' => 1, 'categories' => ['contentCategory' => 4711]]),
            ],
            'one setting, multiple categories as array' => [
                'inputStartingPoints' => '###SITE:categories.contentCategories###',
                'expectedStartingPoints' => '4711,4712,42',
                'site' => new Site('some-site', 1, ['rootPageId' => 1, 'categories' => ['contentCategories' => [4711, 4712, 42]]]),
            ],
            'one setting, multiple categories as csv' => [
                'inputStartingPoints' => '###SITE:categories.contentCategories###',
                'expectedStartingPoints' => '4711,4712,42',
                'site' => new Site('some-site', 1, ['rootPageId' => 1, 'categories' => ['contentCategories' => '4711,4712,42']]),
            ],
            'two settings' => [
                'inputStartingPoints' => '42,###SITE:categories.contentCategory###,12,###SITE:foobar###',
                'expectedStartingPoints' => '42,4711,12,1',
                'site' => new Site('some-site', 1, ['rootPageId' => 1, 'foobar' => 1, 'categories' => ['contentCategory' => 4711]]),
            ],
            'one invalid settings' => [
                'inputStartingPoints' => '42,12,###SITE:invalid###',
                'expectedStartingPoints' => '42,12',
                'site' => new Site('some-site', 1, ['rootPageId' => 1]),
            ],
            'one valid and one invalid setting' => [
                'inputStartingPoints' => '42,###SITE:invalid###,12,###SITE:categories.contentCategory###',
                'expectedStartingPoints' => '42,12,4711,4712',
                'site' => new Site('some-site', 1, ['rootPageId' => 1, 'categories' => ['contentCategory' => '4711,4712']]),
            ],
        ];
    }

    #[DataProvider('addDataOverridesDefaultFieldConfigurationBySiteConfigDataProvider')]
    #[Test]
    public function addDataOverridesDefaultFieldConfigurationBySiteConfig(string $inputStartingPoints, string $expectedStartingPoints, Site $site): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '2',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => $this->getFieldConfiguration([
                            'type' => 'category',
                            'treeConfig' => [
                                'startingPoints' => $inputStartingPoints,
                            ],
                        ]),
                    ],
                ],
            ],
            'site' => $site,
            'rootline' => [],
        ];
        $input = $this->addTcaSchemata($input);

        $result = $this->createSubject()->addData($input);
        self::assertNotEmpty($result['processedTca']['columns']['categories']['config']['items']);
        unset($result['processedTca']['columns']['categories']['config']['items']);

        $expected = $input;
        $expected['databaseRow']['categories'] = ['2'];
        $expected['processedTca']['columns']['categories']['config']['treeConfig'] = [
            'parentField' => 'parent',
            'startingPoints' => $expectedStartingPoints,
            'appearance' => [
                'expandAll' => true,
                'showHeader' => true,
                'maxLevels' => 99,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public static function addDataResolvesMarkersInStartingPointsDataProvider(): array
    {
        return [
            'current pid' => [
                'inputStartingPoints' => '###CURRENT_PID###',
                'expectedStartingPoints' => '89',
                'rootline' => [],
                'pageTsConfig' => [],
            ],
            'site root from rootline' => [
                'inputStartingPoints' => '###SITEROOT###',
                'expectedStartingPoints' => '5',
                'rootline' => [['uid' => 89, 'is_siteroot' => 0], ['uid' => 5, 'is_siteroot' => 1]],
                'pageTsConfig' => [],
            ],
            'page tsconfig id' => [
                'inputStartingPoints' => '###PAGE_TSCONFIG_ID###',
                'expectedStartingPoints' => '13',
                'rootline' => [],
                'pageTsConfig' => ['PAGE_TSCONFIG_ID' => '13'],
            ],
            'page tsconfig idlist' => [
                'inputStartingPoints' => '###PAGE_TSCONFIG_IDLIST###',
                'expectedStartingPoints' => '13,14',
                'rootline' => [],
                'pageTsConfig' => ['PAGE_TSCONFIG_IDLIST' => '13, 14, invalid'],
            ],
            'unresolved markers are removed' => [
                'inputStartingPoints' => '42,###PAGE_TSCONFIG_ID###,###SITEROOT###,12',
                'expectedStartingPoints' => '42,12',
                'rootline' => [],
                'pageTsConfig' => [],
            ],
            'marker combined with site configuration marker' => [
                'inputStartingPoints' => '###CURRENT_PID###,###SITE:categories.contentCategory###',
                'expectedStartingPoints' => '89,4711',
                'rootline' => [],
                'pageTsConfig' => [],
            ],
        ];
    }

    #[DataProvider('addDataResolvesMarkersInStartingPointsDataProvider')]
    #[Test]
    public function addDataResolvesMarkersInStartingPoints(string $inputStartingPoints, string $expectedStartingPoints, array $rootline, array $pageTsConfig): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '2',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => $this->getFieldConfiguration([
                            'type' => 'category',
                            'treeConfig' => [
                                'startingPoints' => $inputStartingPoints,
                            ],
                        ]),
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'tt_content.' => [
                        'categories.' => $pageTsConfig,
                    ],
                ],
            ],
            'site' => new Site('some-site', 1, ['rootPageId' => 1, 'categories' => ['contentCategory' => 4711]]),
            'rootline' => $rootline,
        ];

        $result = $this->createSubject()->addData($this->addTcaSchemata($input));

        self::assertSame($expectedStartingPoints, $result['processedTca']['columns']['categories']['config']['treeConfig']['startingPoints']);
    }

    #[Test]
    public function addDataResolvesMarkersInStartingPointsOverriddenByPageTsConfig(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '2',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => $this->getFieldConfiguration([
                            'type' => 'category',
                            'treeConfig' => [
                                'startingPoints' => '42',
                            ],
                        ]),
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'tt_content.' => [
                        'categories.' => [
                            'config.' => [
                                'treeConfig.' => [
                                    'startingPoints' => '###CURRENT_PID###,###SITE:categories.contentCategory###',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'site' => new Site('some-site', 1, ['rootPageId' => 1, 'categories' => ['contentCategory' => 4711]]),
            'rootline' => [],
        ];

        $result = $this->createSubject()->addData($this->addTcaSchemata($input));

        self::assertSame('89,4711', $result['processedTca']['columns']['categories']['config']['treeConfig']['startingPoints']);
    }

    #[Test]
    public function addDataProcessesCategoryFieldValue(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '2',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => $this->getFieldConfiguration(['type' => 'category', 'relationship' => 'manyToMany']),
                    ],
                ],
            ],
            'rootline' => [],
            'site' => null,
        ];
        $input = $this->addTcaSchemata($input);

        $result = $this->createSubject()->addData($input);
        self::assertNotEmpty($result['processedTca']['columns']['categories']['config']['items']);
        unset($result['processedTca']['columns']['categories']['config']['items']);

        $expected = $input;
        $expected['databaseRow']['categories'] = [
            '29',
            '30',
        ];
        $expected['processedTca']['columns']['categories']['config']['treeConfig'] = [
            'parentField' => 'parent',
            'appearance' => [
                'expandAll' => true,
                'showHeader' => true,
                'maxLevels' => 99,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function addDataThorwsExceptionForStaticItems(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'selectTreeCompileItems' => true,
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '31',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => $this->getFieldConfiguration([
                            'type' => 'category',
                            'items' => [
                                [
                                    'Static item',
                                    1234,
                                    'icon-identifier',
                                ],
                            ],
                        ]),
                    ],
                ],
            ],
        ];

        $this->expectExceptionCode(1627336557);
        $this->expectException(\RuntimeException::class);

        $this->createSubject()->addData($input);
    }

    #[Test]
    public function addDataBuildsTreeForSingle(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'selectTreeCompileItems' => true,
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '31',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => $this->getFieldConfiguration([
                            'type' => 'category',
                            'relationship' => 'oneToOne',
                        ]),
                    ],
                ],
            ],
            'rootline' => [],
            'site' => null,
        ];
        $input = $this->addTcaSchemata($input);

        $expected = $input;
        $expected['databaseRow']['categories'] = ['31'];
        $expected['processedTca']['columns']['categories']['config']['treeConfig'] = [
            'parentField' => 'parent',
            'appearance' => [
                'expandAll' => true,
                'showHeader' => true,
                'maxLevels' => 99,
            ],
        ];
        // Expect fetched category items
        $expected['processedTca']['columns']['categories']['config']['items'] = $this->getExpectedCategoryItems([31]);

        self::assertEquals($expected, $this->createSubject()->addData($input));
    }

    #[Test]
    public function addDataBuildsTreeForCsv(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'selectTreeCompileItems' => true,
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '29,30',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => $this->getFieldConfiguration([
                            'type' => 'category',
                            'relationship' => 'oneToMany',
                            'maxitems' => 123,
                            'treeConfig' => [
                                'appearance' => [
                                    'expandAll' => false,
                                    'showHeader' => false,
                                ],
                            ],
                        ]),
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'tt_content.' => [
                        'categories.' => [
                            'removeItems' => '31',
                        ],
                    ],
                ],
            ],
            'rootline' => [],
            'site' => null,
        ];
        $input = $this->addTcaSchemata($input);

        $expected = $input;
        $expected['databaseRow']['categories'] = [
            '29',
            '30',
        ];
        $expected['processedTca']['columns']['categories']['config']['treeConfig'] = [
            'parentField' => 'parent',
            'appearance' => [
                'expandAll' => false,
                'showHeader' => false,
                'maxLevels' => 99,
            ],
        ];
        // Expect fetched category items
        $expected['processedTca']['columns']['categories']['config']['items'] = $this->getExpectedCategoryItems([29, 30]);

        // TSconfig "removeItems" should be respected
        unset($expected['processedTca']['columns']['categories']['config']['items'][2]);
        $expected['processedTca']['columns']['categories']['config']['items'][1]['hasChildren'] = false;
        $expected['processedTca']['columns']['categories']['config']['items'] = array_values(
            $expected['processedTca']['columns']['categories']['config']['items']
        );

        self::assertEquals($expected, $this->createSubject()->addData($input));
    }

    #[Test]
    public function addDataBuildsTreeForMM(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'selectTreeCompileItems' => true,
            'effectivePid' => 89,
            'databaseRow' => [
                'uid' => 298,
                'categories' => '2',
            ],
            'processedTca' => [
                'columns' => [
                    'categories' => [
                        'config' => $this->getFieldConfiguration(['type' => 'category', 'relationship' => 'manyToMany']),
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'tt_content.' => [
                        'categories.' => [
                            'keepItems' => '28,29,30',
                        ],
                    ],
                ],
            ],
            'rootline' => [],
            'site' => null,
        ];
        $input = $this->addTcaSchemata($input);

        $expected = $input;
        $expected['databaseRow']['categories'] = [
            '29',
            '30',
        ];
        $expected['processedTca']['columns']['categories']['config']['treeConfig'] = [
            'parentField' => 'parent',
            'appearance' => [
                'expandAll' => true,
                'showHeader' => true,
                'maxLevels' => 99,
            ],
        ];
        // Expect fetched category items
        $expected['processedTca']['columns']['categories']['config']['items'] = $this->getExpectedCategoryItems([29, 30]);

        // TSconfig "keepItems" should be respected
        unset($expected['processedTca']['columns']['categories']['config']['items'][2]);
        $expected['processedTca']['columns']['categories']['config']['items'][1]['hasChildren'] = false;
        $expected['processedTca']['columns']['categories']['config']['items'] = array_values(
            $expected['processedTca']['columns']['categories']['config']['items']
        );

        self::assertEquals($expected, $this->createSubject()->addData($input));
    }

    private function createSubject(): TcaCategory
    {
        $category = new TcaCategory();
        $category->injectConnectionPool($this->get(ConnectionPool::class));
        $category->injectIconFactory($this->get(IconFactory::class));
        return $category;
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

    /**
     * This adds the default category configuration as
     * done by TcaPreparation->configureCategoryRelations
     */
    private function getFieldConfiguration(array $input): array
    {
        $default = [
            'relationship' => 'oneToOne',
            'foreign_table' => 'sys_category',
            'foreign_table_where' => ' AND {#sys_category}.{#sys_language_uid} IN (-1, 0)',
            'size' => 20,
            'default' => 0,
            'maxitems' => 1,
        ];

        if (($input['relationship'] ?? '') === 'manyToMany') {
            $default['MM'] = 'sys_category_record_mm';
            $default['MM_opposite_field'] = 'items';
            $default['MM_match_fields'] = [
                'tablenames' => 'tt_content',
                'fieldname' => 'categories',
            ];
            $default['maxitems'] = 99999;
        }

        return array_replace_recursive($default, $input);
    }

    /**
     * Builds the expected category items array
     *
     * @return array[]
     */
    private function getExpectedCategoryItems(array $checked = []): array
    {
        return [
            [
                'identifier' => '0',
                'name' => 'Category',
                'icon' => 'mimetypes-x-sys_category',
                'overlayIcon' => '',
                'depth' => 0,
                'hasChildren' => true,
                'selectable' => false,
                'checked' => false,
            ],
            [
                'identifier' => '28',
                'name' => 'Category A',
                'icon' => 'mimetypes-x-sys_category',
                'overlayIcon' => '',
                'depth' => 1,
                'hasChildren' => true,
                'selectable' => true,
                'checked' => in_array(28, $checked, true),
            ],
            [
                'identifier' => '31',
                'name' => 'Category A.A',
                'icon' => 'mimetypes-x-sys_category',
                'overlayIcon' => '',
                'depth' => 2,
                'hasChildren' => false,
                'selectable' => true,
                'checked' => in_array(31, $checked, true),
            ],
            [
                'identifier' => '29',
                'name' => 'Category B',
                'icon' => 'mimetypes-x-sys_category',
                'overlayIcon' => '',
                'depth' => 1,
                'hasChildren' => false,
                'selectable' => true,
                'checked' => in_array(29, $checked, true),
            ],
            [
                'identifier' => '30',
                'name' => 'Category C',
                'icon' => 'mimetypes-x-sys_category',
                'overlayIcon' => '',
                'depth' => 1,
                'hasChildren' => false,
                'selectable' => true,
                'checked' => in_array(30, $checked, true),
            ],
        ];
    }
}
