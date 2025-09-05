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
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Form\Processor\SelectItemProcessor;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaSelectItemsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/backend/Tests/Functional/Fixtures/Extensions/test_tca_select_items'];
    protected array $pathsToProvideInTestInstance = ['typo3/sysext/backend/Tests/Functional/Form/Fixtures/TcaSelectItems/files/' => 'fileadmin/'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TcaSelectItems/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TcaSelectItems/base.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TcaSelectItems/sys_file_storage.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function addDataKeepExistingItems(): void
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
                                    'label' => 'foo',
                                    'value' => 'bar',
                                ],
                            ],
                        ],
                    ],
                    'anotherField' => [
                        'config' => [
                            'type' => 'group',
                            'items' => [
                                0 => [
                                    'label' => 'foo',
                                    'value' => 'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        self::assertSame($expected, (new TcaSelectItems($this->get(SelectItemProcessor::class)))->addData($input));
    }

    #[Test]
    public function addDataThrowsExceptionIfAnItemIsNotAnArray(): void
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
                                'label' => 'foo',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1439288036);

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->addData($input);
    }

    #[Test]
    public function addDataTranslatesItemLabels(): void
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
                                    'label' => 'LLL:EXT:test_tca_select_items/Resources/Private/Language/locallang.xlf:aLabel',
                                    'value' => 'aValue',
                                ],
                            ],
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'][0]['label'] = 'translated';
        $expected['processedTca']['columns']['aField']['config']['items'][0]['icon'] = null;
        $expected['processedTca']['columns']['aField']['config']['items'][0]['group'] = null;
        $expected['processedTca']['columns']['aField']['config']['items'][0]['description'] = null;

        $expected['databaseRow']['aField'] = ['aValue'];

        self::assertSame($expected, (new TcaSelectItems($this->get(SelectItemProcessor::class)))->addData($input));
    }

    #[Test]
    public function addDataAddsDividersIfItemGroupsAreDefined(): void
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
                                [
                                    'label' => 'aLabel',
                                    'value' => 'aValue',
                                    'icon' => 'an-icon-reference',
                                    'group' => 'non-existing-group',
                                    'description' => null,
                                ],
                                [
                                    'label' => 'anotherLabel',
                                    'value' => 'anotherValue',
                                    'icon' => 'an-icon-reference',
                                    'group' => 'example-group',
                                    'description' => null,
                                ],
                            ],
                            'itemGroups' => [
                                'example-group' => 'My Example Group',
                            ],
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = ['aValue'];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            [
                'label' => 'My Example Group',
                'value' => '--div--',
                'group' => 'example-group',
            ],
            [
                'label' => 'anotherLabel',
                'value' => 'anotherValue',
                'icon' => 'an-icon-reference',
                'group' => 'example-group',
                'description' => null,
            ],
            [
                'label' => 'non-existing-group',
                'value' => '--div--',
                'group' => 'non-existing-group',
            ],
            [
                'label' => 'aLabel',
                'value' => 'aValue',
                'icon' => 'an-icon-reference',
                'group' => 'non-existing-group',
                'description' => null,
            ],
        ];

        self::assertSame($expected, (new TcaSelectItems($this->get(SelectItemProcessor::class)))->addData($input));
    }

    #[Test]
    public function addDataAddsItemGroupsFromForeignTable(): void
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'databaseRow' => [
                'aField' => 'invalid',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                [
                                    'label' => 'anotherLabel',
                                    'value' => 'anotherValue',
                                    'icon' => 'an-icon-reference',
                                    'group' => 'example-group',
                                    'description' => null,
                                ],
                            ],
                            'itemGroups' => [
                                'example-group' => 'My Example Group',
                                'itemgroup1' => 'Item group foreign table 1',
                                'itemgroup2' => 'Item group foreign table 2',
                            ],
                            'foreign_table' => 'foreign_table',
                            'foreign_table_item_group' => 'itemgroup',
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
        ];

        $expectedItemGroups = [
            'none', // Invalid database value gets special "none" group
            'example-group', // Header item for 'example-group'
            'example-group', // Item 'anotherValue'
            'itemgroup1', // Header item for 'itemgroup1' <- dynamic from database value
            'itemgroup1', // Item uid=1
            'itemgroup2', // Header item for 'itemgroup2' <- dynamic from database value
            'itemgroup2', // Item uid=2
            'itemgroup2', // Item uid=6
            'itemgroup3', // Header item for 'itemgroup3' <- dynamic from database value
            'itemgroup3', // Item uid=3
            'itemgroup3', // Item uid=4
            'itemgroup3', // Item uid=5
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $result = $selectItems->addData($input);
        $resultItems = $result['processedTca']['columns']['aField']['config']['items'];
        $resultItemGroups = array_column($resultItems, 'group');

        self::assertSame($expectedItemGroups, $resultItemGroups);
    }

    #[Test]
    public function addDataKeepsIconFromItem(): void
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
                                    'label' => 'aLabel',
                                    'value' => 'aValue',
                                    'icon' => 'an-icon-reference',
                                    'group' => null,
                                    'description' => null,
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

        self::assertSame($expected, (new TcaSelectItems($this->get(SelectItemProcessor::class)))->addData($input));
    }

    #[Test]
    public function addDataAddsFileItemsWithConfiguredFileFolder(): void
    {
        $directory = Environment::getVarPath() . '/' . StringUtility::getUniqueId('test-') . '/';
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'fileFolderConfig' => [
                                'folder' => $directory,
                                'allowedExtensions' => 'gif',
                                'depth' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        mkdir($directory);
        touch($directory . 'anImage.gif');
        touch($directory . 'aFile.txt');
        mkdir($directory . '/subdir');
        touch($directory . '/subdir/anotherImage.gif');
        mkdir($directory . '/subdir/subsubdir');
        touch($directory . '/subdir/subsubdir/anotherImage.gif');

        $expectedItems = [
            0 => [
                'label' => 'anImage.gif',
                'value' => 'anImage.gif',
                'icon' => $directory . 'anImage.gif',
                'group' => null,
                'description' => null,
            ],
            1 => [
                'label' => 'subdir/anotherImage.gif',
                'value' => 'subdir/anotherImage.gif',
                'icon' => $directory . 'subdir/anotherImage.gif',
                'group' => null,
                'description' => null,
            ],
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $result = $selectItems->addData($input);

        self::assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    #[Test]
    public function addDataAddsFileItemsWithOverwrittenFileFolder(): void
    {
        $directory = Environment::getVarPath() . '/' . StringUtility::getUniqueId('test-') . '/';
        $overriddenDirectory = Environment::getVarPath() . '/' . StringUtility::getUniqueId('test-overridden-') . '/';
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'fileFolderConfig' => [
                                'folder' => $directory,
                                'allowedExtensions' => 'gif',
                                'depth' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'config.' => [
                                'fileFolderConfig.' => [
                                    'folder' => $overriddenDirectory,
                                    'allowedExtensions' => 'svg',
                                    'depth' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        mkdir($directory);
        touch($directory . 'anImage.gif');
        touch($directory . 'aFile.txt');
        touch($directory . 'aIcon.svg');
        mkdir($directory . '/subdir');
        touch($directory . '/subdir/anotherImage.gif');
        touch($directory . '/subdir/anotherFile.txt');
        touch($directory . '/subdir/anotherIcon.txt');

        mkdir($overriddenDirectory);
        touch($overriddenDirectory . 'anOverriddenImage.gif');
        touch($overriddenDirectory . 'anOverriddenFile.txt');
        touch($overriddenDirectory . 'anOverriddenIcon.svg');
        mkdir($overriddenDirectory . '/subdir');
        touch($overriddenDirectory . '/subdir/anotherOverriddenImage.gif');
        touch($overriddenDirectory . '/subdir/anotherOverriddenFile.txt');
        touch($overriddenDirectory . '/subdir/anotherOverriddenIcon.svg');

        $expectedItems = [
            0 => [
                'label' => 'anOverriddenIcon.svg',
                'value' => 'anOverriddenIcon.svg',
                'icon' => $overriddenDirectory . 'anOverriddenIcon.svg',
                'group' => null,
                'description' => null,
            ],
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $result = $selectItems->addData($input);

        self::assertSame($expectedItems, $result['processedTca']['columns']['aField']['config']['items']);
    }

    #[Test]
    public function addDataThrowsExceptionForInvalidFileFolder(): void
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
                            'fileFolderConfig' => [
                                'folder' => 'EXT:non_existing/Resources/Public/',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1479399227);
        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->addData($input);
    }

    #[Test]
    public function addDataAddsItemsByAddItemsFromPageTsConfig(): void
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
                                    'label' => 'keepMe',
                                    'value' => 'keep',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
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
                            'addItems.' => [
                                '1' => 'addMe',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'][1] = [
            'label' => 'addMe',
            'value' => 1,
            'icon' => null,
            'group' => null,
            'description' => null,
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $selectItems->addData($input);

        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataAddsItemsByAddItemsWithGroupFromPageTsConfig(): void
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
                                    'label' => 'keepMe',
                                    'value' => 'keep',
                                    'icon' => null,
                                    'group' => 'none',
                                    'description' => null,
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
                            'addItems.' => [
                                '1' => 'addMe',
                                '1.' => [
                                    'group' => 'custom-group',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'][1] = [
            'label' => 'custom-group',
            'value' => '--div--',
            'group' => 'custom-group',
        ];
        $expected['processedTca']['columns']['aField']['config']['items'][2] = [
            'label' => 'addMe',
            'value' => 1,
            'icon' => null,
            'group' => 'custom-group',
            'description' => null,
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataAddsItemsByAddItemsWithDuplicateValuesFromPageTsConfig(): void
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
                                    'label' => 'keepMe',
                                    'value' => 'keep',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
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
                            'addItems.' => [
                                'keep' => 'addMe',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'][1] = [
            'label' => 'addMe',
            'value' => 'keep',
            'icon' => null,
            'group' => null,
            'description' => null,
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    public static function addDataReplacesMarkersInForeignTableClauseDataProvider(): array
    {
        return [
            'replace REC_FIELD' => [
                'AND foreign_table.title=\'###REC_FIELD_rowField###\'',
                [
                    [
                        'label' => 'Item 1',
                        'value' => 1,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [],
            ],
            'replace REC_FIELD within FlexForm' => [
                'AND foreign_table.title=###REC_FIELD_rowFieldFlexForm###',
                [
                    [
                        'label' => 'Item 2',
                        'value' => 2,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'databaseRow' => [
                        'rowFieldThree' => [
                            0 => 'rowFieldThreeValue',
                        ],
                    ],
                    'flexParentDatabaseRow' => [
                        'rowFieldFlexForm' => [
                            0 => 'Item 2',
                        ],
                    ],
                ],
            ],
            'replace REC_FIELD fullQuote' => [
                'AND foreign_table.title=###REC_FIELD_rowField###',
                [
                    [
                        'label' => 'Item 1',
                        'value' => 1,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [],
            ],
            'replace REC_FIELD fullQuoteWithArray' => [
                'AND foreign_table.title=###REC_FIELD_rowFieldThree###',
                [
                    [
                        'label' => 'Item 3',
                        'value' => 3,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'databaseRow' => [
                        'rowFieldThree' => [
                            0 => 'Item 3',
                        ],
                    ],
                ],
            ],
            'replace REC_FIELD multiple markers' => [
                'AND ( foreign_table.title=\'###REC_FIELD_rowField###\' OR foreign_table.title=###REC_FIELD_rowFieldTwo### )',
                [
                    [
                        'label' => 'Item 1',
                        'value' => 1,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                    [
                        'label' => 'Item 2',
                        'value' => 2,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [],
            ],
            'replace CURRENT_PID' => [
                'AND foreign_table.uid=###CURRENT_PID###',
                [
                    [
                        'label' => 'Item 1',
                        'value' => 1,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [],
            ],
            'replace CURRENT_PID within FlexForm' => [
                'AND foreign_table.uid=###CURRENT_PID###',
                [
                    [
                        'label' => 'Item 4',
                        'value' => 4,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'flexParentDatabaseRow' => [
                        'pid' => '4',
                    ],
                ],
            ],
            'replace CURRENT_PID integer cast' => [
                'AND foreign_table.uid=###CURRENT_PID###',
                [
                    [
                        'label' => 'Item 4',
                        'value' => 4,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'effectivePid' => '4string',
                ],
            ],
            'replace THIS_UID' => [
                'AND foreign_table.uid=###THIS_UID###',
                [
                    [
                        'label' => 'Item 5',
                        'value' => 5,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [],
            ],
            'replace THIS_UID integer cast' => [
                'AND foreign_table.uid=###THIS_UID###',
                [
                    [
                        'label' => 'Item 5',
                        'value' => 5,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'databaseRow' => [
                        'uid' => '5string',
                    ],
                ],
            ],
            'replace SITEROOT' => [
                'AND foreign_table.uid=###SITEROOT###',
                [
                    [
                        'label' => 'Item 6',
                        'value' => 6,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [],
            ],
            'replace SITEROOT integer cast' => [
                'AND foreign_table.uid=###SITEROOT###',
                [
                    [
                        'label' => 'Item 6',
                        'value' => 6,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'rootline' => [
                        1 => [
                            'uid' => '6string',
                        ],
                    ],
                ],
            ],
            'replace PAGE_TSCONFIG_ID' => [
                'AND foreign_table.uid=###PAGE_TSCONFIG_ID###',
                [
                    [
                        'label' => 'Item 3',
                        'value' => 3,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'pageTsConfig' => [
                        'TCEFORM.' => [
                            'tca_select_items.' => [
                                'aField.' => [
                                    'PAGE_TSCONFIG_ID' => '3',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'replace PAGE_TSCONFIG_ID integer cast' => [
                'AND foreign_table.uid=###PAGE_TSCONFIG_ID###',
                [
                    [
                        'label' => 'Item 3',
                        'value' => 3,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'pageTsConfig' => [
                        'TCEFORM.' => [
                            'tca_select_items.' => [
                                'aField.' => [
                                    'PAGE_TSCONFIG_ID' => '3string',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'replace PAGE_TSCONFIG_STR' => [
                'AND foreign_table.uid=\'###PAGE_TSCONFIG_STR###\'',
                [
                    [
                        'label' => 'Item 4',
                        'value' => 4,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'pageTsConfig' => [
                        'TCEFORM.' => [
                            'tca_select_items.' => [
                                'aField.' => [
                                    'PAGE_TSCONFIG_STR' => '4',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'replace PAGE_TSCONFIG_IDLIST' => [
                'AND foreign_table.uid IN (###PAGE_TSCONFIG_IDLIST###)',
                [
                    [
                        'label' => 'Item 3',
                        'value' => 3,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                    [
                        'label' => 'Item 4',
                        'value' => 4,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'pageTsConfig' => [
                        'TCEFORM.' => [
                            'tca_select_items.' => [
                                'aField.' => [
                                    'PAGE_TSCONFIG_IDLIST' => '3,4',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'replace PAGE_TSCONFIG_IDLIST cleans list' => [
                'AND foreign_table.uid IN (###PAGE_TSCONFIG_IDLIST###)',
                [
                    [
                        'label' => 'Item 3',
                        'value' => 3,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                    [
                        'label' => 'Item 4',
                        'value' => 4,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'pageTsConfig' => [
                        'TCEFORM.' => [
                            'tca_select_items.' => [
                                'aField.' => [
                                    'PAGE_TSCONFIG_IDLIST' => 'a, 3, b, 4, c',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'replace SITE:rootPageId' => [
                'AND foreign_table.uid = ###SITE:rootPageId###',
                [
                    [
                        'label' => 'Item 6',
                        'value' => 6,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [],
            ],
            'replace SITE:mySetting.foobar' => [
                'AND foreign_table.title = ###SITE:mySetting.foobar###',
                [
                    [
                        'label' => 'Item 5',
                        'value' => 5,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [],
            ],
            'replace SITE:mySetting.doesNotExist' => [
                'AND foreign_table.title = ###SITE:mySetting.doesNotExist###',
                [],
                [],
            ],
            'replace SITE:rootPageId, SITE:mySetting.foobar and PAGE_TSCONFIG_IDLIST' => [
                'AND ( foreign_table.uid = ###SITE:rootPageId### OR foreign_table.title = ###SITE:mySetting.foobar### OR foreign_table.uid IN (###PAGE_TSCONFIG_IDLIST###) )',
                [
                    [
                        'label' => 'Item 3',
                        'value' => 3,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                    [
                        'label' => 'Item 4',
                        'value' => 4,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                    [
                        'label' => 'Item 5',
                        'value' => 5,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                    [
                        'label' => 'Item 6',
                        'value' => 6,
                        'icon' => 'default-not-found',
                        'group' => null,
                        'description' => null,
                    ],
                ],
                [
                    'pageTsConfig' => [
                        'TCEFORM.' => [
                            'tca_select_items.' => [
                                'aField.' => [
                                    'PAGE_TSCONFIG_IDLIST' => 'a, 3, b, 4, c',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('addDataReplacesMarkersInForeignTableClauseDataProvider')]
    #[Test]
    public function addDataReplacesMarkersInForeignTableClause(string $foreignTableWhere, array $expectedItems, array $inputOverride): void
    {
        $input = [
            'tableName' => 'tca_select_items',
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 5,
                'rowField' => 'Item 1',
                'rowFieldTwo' => 'Item 2',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'foreign_table',
                            'foreign_table_where' => $foreignTableWhere,
                            'maxitems' => 1,
                        ],
                    ],
                ],
            ],
            'rootline' => [
                2 => [
                    'uid' => 999,
                    'is_siteroot' => 0,
                ],
                1 => [
                    'uid' => 6,
                    'is_siteroot' => 1,
                ],
                0 => [
                    'uid' => 0,
                    'is_siteroot' => null,
                ],
            ],
            'pageTsConfig' => [],
            'site' => new Site('some-site', 6, ['rootPageId' => 6, 'mySetting' => ['foobar' => 'Item 5']]),
        ];
        ArrayUtility::mergeRecursiveWithOverrule($input, $inputOverride);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'] = $expectedItems;
        $expected['databaseRow']['aField'] = [];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataThrowsExceptionIfForeignTableIsNotDefinedInTca(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'fTableNotDefined',
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1439569743);

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->addData($input);
    }

    #[Test]
    public function addDataForeignTableSplitsGroupOrderAndLimit(): void
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 1,
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'maxitems' => 99999,
                            'foreign_table' => 'foreign_table',
                            // @todo Using "uid", "pid" and "title" inside GROUP BY to satisfy the `ONLY_FULL_GROUP_BY` rule on
                            // @todo some dbms. Doing so renders the GROUP statement useless, though.
                            'foreign_table_where' => '
                                AND foreign_table.uid > 1
                                GROUP BY uid, pid, title, groupingfield1, groupingfield2
                                ORDER BY uid
                                LIMIT 1,2',
                        ],
                    ],
                ],
            ],
            'rootline' => [],
            'site' => null,
        ];

        $expected = $input;

        $expected['processedTca']['columns']['aField']['config']['items'] = [
            [
                'label' => 'Item 3',
                'value' => 3,
                'icon' => 'default-not-found',
                'group' => null,
                'description' => null,
            ],
            [
                'label' => 'Item 4',
                'value' => 4,
                'icon' => 'default-not-found',
                'group' => null,
                'description' => null,
            ],
        ];

        $expected['databaseRow']['aField'] = [];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataForeignTableQueuesFlashMessageOnDatabaseError(): void
    {
        $input = [
            'databaseRow' => [
                'uid' => 1,
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'non_existing_table',
                            'items' => [
                                0 => [
                                    'label' => 'itemLabel',
                                    'value' => 'itemValue',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
                                ],
                            ],
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
            'rootline' => [],
        ];

        $GLOBALS['TCA']['non_existing_table'] = [];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $expected = $input;
        $expected['databaseRow']['aField'] = [];

        $flashMessageService = $this->get(FlashMessageService::class);

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $selectItems->injectFlashMessageService($flashMessageService);

        self::assertEquals($expected, $selectItems->addData($input));
        self::assertCount(1, $flashMessageService->getMessageQueueByIdentifier()->getAllMessages());
    }

    #[Test]
    public function addDataForeignTableHandlesForeignTableRows(): void
    {
        $input = [
            'databaseRow' => [
                'uid' => 1,
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'foreign_table',
                            'foreign_table_prefix' => 'aPrefix',
                            'foreign_table_where' => 'AND foreign_table.uid = 1',
                            'items' => [],
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
            'site' => null,
            'rootline' => [],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            [
                'label' => 'aPrefixItem 1',
                'value' => 1,
                'icon' => 'default-not-found',
                'group' => null,
                'description' => null,
            ],
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataRemovesItemsThatAreRestrictedByUserStorageAddedByForeignTable(): void
    {
        $input = [
            'databaseRow' => [
                'uid' => 1,
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'sys_file_storage',
                            'items' => [
                                [
                                    'label' => 'some other file storage',
                                    'value' => 2,
                                ],
                            ],
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
            'rootline' => [],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            [
                'label' => 'fileadmin/ (auto-created)',
                'value' => 1,
                'icon' => 'mimetypes-x-sys_file_storage',
                'group' => null,
                'description' => null,
            ],
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataForeignTableResolvesIconFromSelicon(): void
    {
        $input = [
            'databaseRow' => [
                'uid' => 1,
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'foreign_table',
                            'foreign_table_where' => 'AND foreign_table.uid < 3',
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
            'site' => null,
            'rootline' => [],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            [
                'label' => 'Item 1',
                'value' => 1,
                'icon' => 'fileadmin/file1.png',
                'group' => null,
                'description' => null,
            ],
            [
                'label' => 'Item 2',
                'value' => 2,
                'icon' => 'fileadmin/file2.png',
                'group' => null,
                'description' => null,
            ],
        ];

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TcaSelectItems/sys_file_reference.csv');
        $GLOBALS['TCA']['foreign_table']['ctrl']['selicon_field'] = 'fal_field';

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $selectItems->injectFileRepository($this->get(FileRepository::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataRemovesItemsByKeepItemsPageTsConfig(): void
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
                                    'label' => 'keepMe',
                                    'value' => 'keep',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
                                ],
                                1 => [
                                    'label' => 'removeMe',
                                    'value' => 'remove',
                                ],
                                2 => [
                                    'label' => 'removeMe',
                                    'value' => 0,
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

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataRemovesAllItemsByEmptyKeepItemsPageTsConfig(): void
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
                ],
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

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataEvaluatesKeepItemsBeforeAddItemsFromPageTsConfig(): void
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
                                    'label' => 'keepMe',
                                    'value' => '1',
                                    'icon' => null,
                                    'group' => null,
                                ],
                                1 => [
                                    'label' => 'removeMe',
                                    'value' => 'remove',
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
                'label' => 'keepMe',
                'value' => '1',
                'icon' => null,
                'group' => null,
                'description' => null,
            ],
            1 => [
                'label' => 'addItem #1',
                'value' => '1',
                'icon' => null,
                'group' => null,
                'description' => null,
            ],
            2 => [
                'label' => 'addItem #12',
                'value' => '12',
                'icon' => null,
                'group' => null,
                'description' => null,
            ],
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataRemovesItemsByRemoveItemsPageTsConfig(): void
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
                                    'label' => 'keepMe',
                                    'value' => 'keep',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
                                ],
                                1 => [
                                    'label' => 'removeMe',
                                    'value' => 'remove',
                                ],
                                2 => [
                                    'label' => 'keep me',
                                    'value' => 0,
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
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
        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataRemovesItemsByZeroValueRemoveItemsPageTsConfig(): void
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
                                    'label' => 'keepMe',
                                    'value' => 'keep',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
                                ],
                                1 => [
                                    'label' => 'keepMe',
                                    'value' => 'keepMe2',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
                                ],
                                2 => [
                                    'label' => 'remove me',
                                    'value' => 0,
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
                            'removeItems' => '0',
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        unset($expected['processedTca']['columns']['aField']['config']['items'][2]);
        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataRemovesItemsAddedByAddItemsFromPageTsConfigByRemoveItemsPageTsConfig(): void
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
                                    'label' => 'keepMe',
                                    'value' => 'keep',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
                                ],
                                1 => [
                                    'label' => 'removeMe',
                                    'value' => 'remove',
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
                            'removeItems' => 'remove,add',
                            'addItems.' => [
                                'add' => 'addMe',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [];
        unset($expected['processedTca']['columns']['aField']['config']['items'][1]);

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataRemovesItemsByLanguageFieldUserRestriction(): void
    {
        $input = [
            'databaseRow' => [
                'aField' => '0',
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
                                    'label' => 'Default',
                                    'value' => '0',
                                ],
                                1 => [
                                    'label' => 'German',
                                    'value' => '1',
                                ],
                                2 => [
                                    'label' => 'Danish',
                                    'value' => '2',
                                ],
                            ],
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
            'site' => new Site(
                'test',
                1,
                [
                    'languages' => [
                        [
                            'languageId' => 0,
                            'title' => 'Default',
                            'locale' => 'en_US.UTF-8',
                        ],
                        [
                            'languageId' => 1,
                            'title' => 'German',
                            'locale' => 'de_DE.UTF-8',
                        ],
                        [
                            'languageId' => 2,
                            'title' => 'Danish',
                            'locale' => 'da_DK.UTF-8',
                        ],
                    ],
                ]
            ),
        ];

        $GLOBALS['BE_USER']->groupData['allowed_languages'] = '0,1';
        $expected = $input;
        $expected['databaseRow']['aField'] = [
            0 => '0',
        ];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            0 => [
                'label' => 'Default',
                'value' => '0',
                'icon' => null,
                'group' => null,
                'description' => null,
            ],
            1 => [
                'label' => 'German',
                'value' => '1',
                'icon' => null,
                'group' => null,
                'description' => null,
            ],
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataKeepsAllPagesDoktypesForAdminUser(): void
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
                                    'label' => 'keepMe',
                                    'value' => 'keep',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
                                ],
                            ],
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
        ];

        $backendUserMock = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserMock;
        $backendUserMock->expects($this->once())->method('isAdmin')->willReturn(true);

        $expected = $input;
        $expected['databaseRow']['doktype'] = ['keep'];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataKeepsAllowedPageTypesForNonAdminUser(): void
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
                                    'label' => 'keepMe',
                                    'value' => 'keep',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
                                ],
                                1 => [
                                    'label' => 'removeMe',
                                    'value' => 'remove',
                                ],
                            ],
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
        ];

        $GLOBALS['BE_USER']->groupData['pagetypes_select'] = 'foo,keep,anotherAllowedDoktype';

        $expected = $input;
        $expected['databaseRow']['doktype'] = ['keep'];
        unset($expected['processedTca']['columns']['doktype']['config']['items'][1]);

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataCallsItemsProcFunc(): void
    {
        $input = [
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => [],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'effectivePid' => 42,
            'site' => new Site('aSite', 456, []),
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [],
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                $parameters['items'] = [
                                    0 => [
                                        0 => 'aLabel',
                                        1 => 'aValue',
                                        2 => null,
                                        3 => null,
                                        4 => null,
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
                    'label' => 'aLabel',
                    'value' => 'aValue',
                    'icon' => null,
                    'group' => null,
                    'description' => null,
                ],
            ],
            'maxitems' => 99999,
        ];

        self::assertSame($expected, (new TcaSelectItems($this->get(SelectItemProcessor::class)))->addData($input));
    }

    /**
     * This test case combines the use of itemsProcFunc and foreign_table
     *
     * In the itemsProcFunc we will iterate over the items given from foreign_table and filter out every item that
     * does not have a uid of 2
     */
    #[Test]
    public function addDataItemsProcFuncWillUseItemsFromForeignTable(): void
    {
        $input = [
            'databaseRow' => [
                'uid' => 5,
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => [],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
            'effectivePid' => 1,
            'site' => new Site('aSite', 456, []),
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'foreign_table',
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                $filteredItems = [];
                                // Iterate over given items to filter them
                                foreach ($parameters['items'] as $item) {
                                    if ($item[1] === 2) { // uid === 2
                                        $filteredItems[] = [
                                            $item[0],   // label
                                            $item[1],   // uid
                                            null,       // icon
                                            null,       // groupID
                                            null,       // helpText
                                        ];
                                    }
                                }
                                $parameters['items'] = $filteredItems;
                            },
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
            'rootline' => [],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config'] = [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'foreign_table' => 'foreign_table',
            'items' => [
                0 => [
                    'label' => 'Item 2',
                    'value' => 2,
                    'icon' => null,
                    'group' => null,
                    'description' => null,
                ],
            ],
            'maxitems' => 99999,
        ];

        $expected['databaseRow']['aField'] = [];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    /**
     * This test case combines the use of itemsProcFunc, foreign_table and pageTsConfig
     *
     * In the itemsProcFunc we will iterate over the items given from foreign_table and filter out every item that
     * does not have a uid lower than 3.
     * The pageTsConfig will remove the item with the uid=2 from the list so only one item with uid=1 will remain
     */
    #[Test]
    public function addDataItemsProcFuncWillUseItemsFromForeignTableAndRemoveItemsByPageTsConfig(): void
    {
        $input = [
            'databaseRow' => [
                'uid' => 5,
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => [],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
            'effectivePid' => 1,
            'site' => new Site('aSite', 456, []),
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'foreign_table',
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                $filteredItems = [];
                                // Iterate over given items to filter them
                                foreach ($parameters['items'] as $item) {
                                    if ($item[1] < 3) { // uid < 2
                                        $filteredItems[] = [
                                            $item[0],   // label
                                            $item[1],   // uid
                                            null,       // icon
                                            null,       // groupId
                                            null,       // helpText
                                        ];
                                    }
                                }
                                $parameters['items'] = $filteredItems;
                            },
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'removeItems' => '2',
                        ],
                    ],
                ],
            ],
            'rootline' => [],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config'] = [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'foreign_table' => 'foreign_table',
            'items' => [
                0 => [
                    'label' => 'Item 1',
                    'value' => 1,
                    'icon' => null,
                    'group' => null,
                    'description' => null,
                ],
            ],
            'maxitems' => 99999,
        ];

        $expected['databaseRow']['aField'] = [];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    /**
     * This test case combines the use of itemsProcFunc, foreign_table and pageTsConfig
     *
     * In the itemsProcFunc we will iterate over the items given from foreign_table and filter out every item that
     * does not have the uid of 2.
     * The pageTsConfig will add an item with the uid=12 to the list so only one item with uid=1 will remain
     */
    #[Test]
    public function addDataItemsProcFuncWillUseItemsFromForeignTableAndAddItemsByPageTsConfig(): void
    {
        $input = [
            'databaseRow' => [
                'uid' => 5,
                'aField' => '',
            ],
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => [],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
            'effectivePid' => 1,
            'site' => new Site('aSite', 456, []),
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'foreign_table',
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                $filteredItems = [];
                                // Iterate over given items to filter them
                                foreach ($parameters['items'] as $item) {
                                    if ($item[1] === 2) { // uid must be 2
                                        $filteredItems[] = [
                                            $item[0],   // label
                                            $item[1],   // uid
                                            null,       // icon
                                            null,       // groupID
                                            null,       // helpText
                                        ];
                                    }
                                }
                                $parameters['items'] = $filteredItems;
                            },
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'addItems.' => [
                                '12' => 'Label of the added item',
                            ],
                        ],
                    ],
                ],
            ],
            'rootline' => [],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config'] = [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'foreign_table' => 'foreign_table',
            'items' => [
                0 => [
                    'label' => 'Item 2',
                    'value' => 2,
                    'icon' => null,
                    'group' => null,
                    'description' => null,
                ],
                1 => [
                    'label' => 'Label of the added item',
                    'value' => 12,
                    'icon' => null,
                    'group' => null,
                    'description' => null,
                ],
            ],
            'maxitems' => 99999,
        ];

        $expected['databaseRow']['aField'] = [];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function addDataItemsProcFuncReceivesParameters(): void
    {
        $input = [
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => ['config' => 'someValue'],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'effectivePid' => 42,
            'site' => new Site('aSite', 456, []),
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'itemsProcFunc.' => [
                                'itemParamKey' => 'itemParamValue',
                            ],
                        ],
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
                                    'label' => 'aLabel',
                                    'value' => 'aValue',
                                ],
                            ],
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                if ($parameters['items'][0]['label'] !== 'aLabel'
                                    || $parameters['items'][0]['value'] !== 'aValue'
                                    || $parameters['config']['aKey'] !== 'aValue'
                                    || $parameters['TSconfig'] !== [ 'itemParamKey' => 'itemParamValue' ]
                                    || $parameters['table'] !== 'aTable'
                                    || $parameters['row'] !== [ 'aField' => 'aValue' ]
                                    || $parameters['field'] !== 'aField'
                                    || $parameters['inlineParentUid'] !== 1
                                    || $parameters['inlineParentTableName'] !== 'aTable'
                                    || $parameters['inlineParentFieldName'] !== 'aField'
                                    || $parameters['inlineParentConfig'] !== ['config' => 'someValue']
                                    || $parameters['inlineTopMostParentUid'] !== 1
                                    || $parameters['inlineTopMostParentTableName'] !== 'topMostTable'
                                    || $parameters['inlineTopMostParentFieldName'] !== 'topMostField'
                                ) {
                                    throw new \UnexpectedValueException('broken', 1476109436);
                                }
                            },
                        ],
                    ],
                ],
            ],
        ];

        $flashMessageService = $this->get(FlashMessageService::class);
        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectFlashMessageService($flashMessageService);
        $selectItems->addData($input);

        $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        self::assertCount(0, $flashMessageQueue->getAllMessages());
    }

    #[Test]
    public function addDataItemsProcFuncEnqueuesFlashMessageOnException(): void
    {
        $input = [
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => [],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'effectivePid' => 42,
            'site' => new Site('aSite', 456, []),
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'itemsProcFunc.' => [
                                'itemParamKey' => 'itemParamValue',
                            ],
                        ],
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
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                throw new \UnexpectedValueException('anException', 1476109437);
                            },
                        ],
                    ],
                ],
            ],
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectFlashMessageService($this->get(FlashMessageService::class));
        $selectItems->addData($input);

        $flashMessageService = $this->get(FlashMessageService::class);
        $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        self::assertCount(1, $flashMessageQueue->getAllMessages());
    }

    #[Test]
    public function addDataTranslatesItemLabelsFromPageTsConfig(): void
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
                                    'label' => 'aLabel',
                                    'value' => 'aValue',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
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
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = ['aValue'];
        $expected['processedTca']['columns']['aField']['config']['items'][0]['label'] = 'labelOverride';

        self::assertSame($expected, (new TcaSelectItems($this->get(SelectItemProcessor::class)))->addData($input));
    }

    #[Test]
    public function addDataAddsIconsFromPageTsConfig(): void
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
                                    'label' => 'aLabel',
                                    'value' => 'aValue',
                                    'icon' => 'icon-identifier',
                                    'group' => null,
                                    'description' => null,
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
                            'altIcons.' => [
                                'aValue' => 'icon-identifier-override',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = ['aValue'];
        $expected['processedTca']['columns']['aField']['config']['items'][0]['icon'] = 'icon-identifier-override';

        self::assertSame($expected, (new TcaSelectItems($this->get(SelectItemProcessor::class)))->addData($input));
    }

    #[Test]
    public function processSelectFieldValueSetsMmForeignRelationValues(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tca_select_items',
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 42,
                // Two connected rows
                'mm_field' => 2,
            ],
            'processedTca' => [
                'columns' => [
                    'mm_field' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'maxitems' => 999,
                            'foreign_table' => 'foreign_table',
                            'MM' => 'select_ftable_mm',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));

        self::assertSame(['5', '6'], $selectItems->addData($input)['databaseRow']['mm_field']);
    }

    #[Test]
    public function processSelectFieldValueSetsForeignRelationValues(): void
    {
        $input = [
            'tableName' => 'tca_select_items',
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 42,
                'foreign_field' => '1,2,3,4',
            ],
            'processedTca' => [
                'columns' => [
                    'foreign_field' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'maxitems' => 999,
                            'foreign_table' => 'foreign_table',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));

        self::assertSame(['1', '2', '3', '4'], $selectItems->addData($input)['databaseRow']['foreign_field']);
    }

    #[Test]
    public function processSelectFieldValueRemovesInvalidDynamicValues(): void
    {
        $input = [
            'tableName' => 'tca_select_items',
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 5,
                'foreign_field' => '1,2,bar,foo',
            ],
            'processedTca' => [
                'columns' => [
                    'foreign_field' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingleBox',
                            'foreign_table' => 'foreign_table',
                            'maxitems' => 999,
                            'items' => [
                                ['label' => 'foo', 'value' => 'foo', 'icon' => null, 'group' => null, 'description' => null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));

        self::assertSame(['1', '2', 'foo'], $selectItems->addData($input)['databaseRow']['foreign_field']);
    }

    #[Test]
    public function processSelectFieldValueKeepsValuesFromStaticItems(): void
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
                                ['label' => 'foo', 'value' => 'foo', 'icon' => null, 'group' => null, 'description' => null],
                                ['label' => 'bar', 'value' => 'bar', 'icon' => null, 'group' => null, 'description' => null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = [
            'foo',
            'bar',
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function processSelectFieldValueReturnsEmptyValueForSingleSelect(): void
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

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function processSelectFieldValueTrimsEmptyValueForMultiValueSelect(): void
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
                                ['label' => 'a', 'value' => '', 'icon' => null, 'group' => null, 'description' => null],
                                ['label' => 'b', 'value' => 'b', 'icon' => null, 'group' => null, 'description' => null],
                                ['label' => 'c', 'value' => 'c', 'icon' => null, 'group' => null, 'description' => null],
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

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function processSelectFieldValueDoesNotCallRelationManagerForStaticOnlyItems(): void
    {
        $relationHandlerMock = $this->createMock(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerMock);
        $relationHandlerMock->expects($this->never())->method('start');
        $relationHandlerMock->expects($this->never())->method('getValueArray');

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
                                ['label' => 'foo', 'value' => 'foo', 'icon' => null, 'group' => null, 'description' => null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = ['foo'];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function processSelectFieldValueAddsInvalidValuesToItemsForSingleSelects(): void
    {
        $relationHandlerMock = $this->createMock(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerMock);
        $relationHandlerMock->expects($this->never())->method('start');
        $relationHandlerMock->expects($this->never())->method('getValueArray');

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
                                ['label' => 'foo', 'value' => 'foo', 'icon' => null, 'group' => null, 'description' => null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = ['foo'];
        $expected['processedTca']['columns']['aField']['config']['items'] = [
            ['label' => '[ MISSING LABEL ("bar") ]', 'value' => 'bar', 'icon' => null, 'group' => 'none', 'description' => null],
            ['label' => '[ MISSING LABEL ("2") ]', 'value' => '2', 'icon' => null, 'group' => 'none', 'description' => null],
            ['label' => '[ MISSING LABEL ("1") ]', 'value' => '1', 'icon' => null, 'group' => 'none', 'description' => null],
            ['label' => 'foo', 'value' => 'foo', 'icon' => null, 'group' => null, 'description' => null],
        ];
        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function processSelectFieldValueReturnsDuplicateValuesForMultipleSelect(): void
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
                                ['label' => '1', 'value' => '1', 'icon' => null, 'group' => null, 'description' => null],
                                ['label' => 'foo', 'value' => 'foo', 'icon' => null, 'group' => null, 'description' => null],
                                ['label' => 'bar', 'value' => 'bar', 'icon' => null, 'group' => null, 'description' => null],
                                ['label' => '2', 'value' => '2', 'icon' => null, 'group' => null, 'description' => null],
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
            'bar',
        ];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function processSelectFieldValueReturnsUniqueValuesForMultipleSelect(): void
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
                                ['label' => '1', 'value' => '1', 'icon' => null, 'group' => null, 'description' => null],
                                ['label' => 'foo', 'value' => 'foo', 'icon' => null, 'group' => null, 'description' => null],
                                ['label' => 'bar', 'value' => 'bar', 'icon' => null, 'group' => null, 'description' => null],
                                ['label' => '2', 'value' => '2', 'icon' => null, 'group' => null, 'description' => null],
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

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    #[Test]
    public function processSelectFieldValueReturnsEmptyArrayForNull(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'select_nullable' => null,
            ],
            'processedTca' => [
                'columns' => [
                    'select_nullable' => [
                        'label' => 'Select nullable',
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                [
                                    'label' => 'Item 1',
                                    'value' => 'item1',
                                    'icon' => null,
                                    'group' => null,
                                    'description' => null,
                                ],
                            ],
                            'maxitems' => 9999,
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['select_nullable'] = [];

        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($expected, $selectItems->addData($input));
    }

    public static function processSelectFieldSetsCorrectValuesForMmRelationsDataProvider(): array
    {
        return [
            'Relation with MM table and new status with default values' => [
                [
                    'tableName' => 'tca_select_items',
                    'command' => 'new',
                    'effectivePid' => 1,
                    'databaseRow' => [
                        'uid' => 'NEW1234',
                        'mm_field' => '24,35',
                    ],
                    'processedTca' => [
                        'columns' => [
                            'mm_field' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'maxitems' => 999,
                                    'MM' => 'select_ftable_mm',
                                    'foreign_table' => 'foreign_table',
                                    'items' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    24, 35,
                ],
            ],
            'Relation with MM table and item array in list but no new status' => [
                [
                    'tableName' => 'tca_select_items',
                    'command' => 'edit',
                    'effectivePid' => 42,
                    'databaseRow' => [
                        'uid' => 42,
                        'mm_field' => '2',
                    ],
                    'processedTca' => [
                        'columns' => [
                            'mm_field' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'maxitems' => 999,
                                    'MM' => 'select_ftable_mm',
                                    'foreign_table' => 'foreign_table',
                                    'items' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [5, 6],
            ],
            'Relation with MM table and maxitems = 1 processes field value (item count)' => [
                [
                    'tableName' => 'tca_select_items',
                    'command' => 'edit',
                    'effectivePid' => 42,
                    'databaseRow' => [
                        'uid' => 43,
                        // MM relation with one item has 1 in field value
                        'mm_field' => 1,
                    ],
                    'processedTca' => [
                        'columns' => [
                            'mm_field' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'maxitems' => 1,
                                    'MM' => 'select_ftable_mm',
                                    'foreign_table' => 'foreign_table',
                                    'items' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    2,
                ],
            ],
            'Relation with MM table and maxitems = 1 results in empty array if no items are set' => [
                [
                    'tableName' => 'tca_select_items',
                    'command' => 'edit',
                    'effectivePid' => 42,
                    'databaseRow' => [
                        'uid' => 58,
                        // MM relation with no items has 0 in field value
                        'mm_field' => 0,
                    ],
                    'processedTca' => [
                        'columns' => [
                            'mm_field' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'maxitems' => 1,
                                    'MM' => 'select_ftable_mm',
                                    'foreign_table' => 'foreign_table',
                                    'items' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [],
            ],
        ];
    }

    #[DataProvider('processSelectFieldSetsCorrectValuesForMmRelationsDataProvider')]
    #[Test]
    public function processSelectFieldSetsCorrectValuesForMmRelations(array $input, array $relationHandlerUids): void
    {
        $selectItems = (new TcaSelectItems($this->get(SelectItemProcessor::class)));
        $selectItems->injectConnectionPool($this->get(ConnectionPool::class));
        $selectItems->injectIconFactory($this->get(IconFactory::class));
        $selectItems->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        self::assertEquals($relationHandlerUids, $selectItems->addData($input)['databaseRow']['mm_field']);
    }
}
