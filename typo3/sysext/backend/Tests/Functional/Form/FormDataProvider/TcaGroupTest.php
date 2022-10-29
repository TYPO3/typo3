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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TcaGroupTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TcaGroup.csv');
    }

    /**
     * This test checks if TcaGroup respects deleted elements
     *
     * @test
     */
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
     *
     * @test
     */
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
