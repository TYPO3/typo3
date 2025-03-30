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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaGroupTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TcaGroup.csv');
    }

    #[Test]
    public function addDataReturnsFieldUnchangedIfFieldIsNotTypeGroup(): void
    {
        $input = [
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'foo',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        self::assertSame($expected, (new TcaGroup())->addData($input));
    }

    #[Test]
    public function addDataSetsDatabaseData(): void
    {
        $aFieldConfig = [
            'type' => 'group',
            'allowed' => 'pages',
            'maxitems' => 99999,
        ];
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => 42,
                'aField' => '1,3', // Reference existing pages from fixture
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => $aFieldConfig,
                    ],
                ],
            ],
        ];

        $result = (new TcaGroup())->addData($input);

        // Verify the field was processed and contains the expected structure
        self::assertIsArray($result['databaseRow']['aField']);
        self::assertCount(2, $result['databaseRow']['aField']);

        self::assertEquals('pages', $result['databaseRow']['aField'][0]['table']);
        self::assertEquals('visible', $result['databaseRow']['aField'][0]['title']);
        self::assertEquals(1, $result['databaseRow']['aField'][0]['uid']);
        self::assertNotEmpty($result['databaseRow']['aField'][0]['row']);
        self::assertEquals('pages', $result['databaseRow']['aField'][1]['table']);
        self::assertEquals('hidden', $result['databaseRow']['aField'][1]['title']);
        self::assertEquals(3, $result['databaseRow']['aField'][1]['uid']);
        self::assertNotEmpty($result['databaseRow']['aField'][1]['row']);

        // Verify clipboard elements are set
        self::assertArrayHasKey('clipboardElements', $result['processedTca']['columns']['aField']['config']);
        self::assertIsArray($result['processedTca']['columns']['aField']['config']['clipboardElements']);
    }

    /**
     * This test checks if TcaGroup respects deleted elements
     */
    #[Test]
    public function respectsDeletedElements()
    {
        $aFieldConfig = [
            'type' => 'group',
            'allowed' => 'pages',
            'maxitems' => 99999,
        ];
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => 42,
                'aField' => '1,2,3,4',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => $aFieldConfig,
                    ],
                ],
            ],
        ];

        $result = (new TcaGroup())->addData($input);
        self::assertIsArray($result['databaseRow']['aField'], 'TcaGroup did not load items');

        $loadedUids = array_column($result['databaseRow']['aField'], 'uid');
        self::assertEquals($loadedUids, [1, 3, 4], 'TcaGroup did not load the correct items');
    }

    /**
     * This test checks if TcaGroup respects deleted elements in a workspace
     */
    #[Test]
    public function respectsDeletedElementsInWorkspace()
    {
        $aFieldConfig = [
            'type' => 'group',
            'allowed' => 'pages',
            'maxitems' => 99999,
        ];
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => 42,
                'aField' => '1,2,3,4',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => $aFieldConfig,
                    ],
                ],
            ],
        ];

        $GLOBALS['BE_USER']->workspace = 1;

        $result = (new TcaGroup())->addData($input);
        self::assertIsArray($result['databaseRow']['aField'], 'TcaGroup did not load items in a workspace');

        $loadedUids = array_column($result['databaseRow']['aField'], 'uid');
        self::assertEquals($loadedUids, [3, 4], 'TcaGroup did not load the correct items in a workspace');
    }
}
