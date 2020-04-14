<?php

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

use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaGroupTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function addDataReturnsFieldUnchangedIfFieldIsNotTypeGroup()
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

    /**
     * @test
     */
    public function addDataThrowsExceptionWithTypeGroupAndNoValidInternalType()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'foo',
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438780511);
        (new TcaGroup())->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsFolderData()
    {
        $input = [
            'databaseRow' => [
                'aField' => '1:/aFolder/anotherFolder/',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'folder',
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
        ];

        /** @var Folder|ObjectProphecy $relationHandlerProphecy */
        $folderProphecy = $this->prophesize(Folder::class);

        /** @var ResourceFactory|ObjectProphecy $relationHandlerProphecy */
        $resourceFactoryProphecy = $this->prophesize(ResourceFactory::class);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactoryProphecy->reveal());
        $resourceFactoryProphecy->retrieveFileOrFolderObject('1:/aFolder/anotherFolder/')
            ->shouldBeCalled()
            ->willReturn($folderProphecy->reveal());

        $expected = $input;
        $expected['databaseRow']['aField'] = [
            [
                'folder' => '1:/aFolder/anotherFolder/',
            ]
        ];
        $expected['processedTca']['columns']['aField']['config']['clipboardElements'] = [];
        self::assertSame($expected, (new TcaGroup())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDatabaseData()
    {
        $aFieldConfig = [
            'type' => 'group',
            'internal_type' => 'db',
            'MM' => 'mmTableName',
            'allowed' => 'aForeignTable',
            'maxitems' => 99999,
        ];
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'uid' => 42,
                'aField' => '1,2',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => $aFieldConfig,
                    ],
                ],
            ],
        ];

        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

        $clipboardProphecy = $this->prophesize(Clipboard::class);
        GeneralUtility::addInstance(Clipboard::class, $clipboardProphecy->reveal());
        $clipboardProphecy->initializeClipboard()->shouldBeCalled();
        $clipboardProphecy->elFromTable('aForeignTable')->shouldBeCalled()->willReturn([]);

        /** @var RelationHandler|ObjectProphecy $relationHandlerProphecy */
        $relationHandlerProphecy = $this->prophesize(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());
        $relationHandlerProphecy->start('1,2', 'aForeignTable', 'mmTableName', 42, 'aTable', $aFieldConfig)->shouldBeCalled();
        $relationHandlerProphecy->getFromDB()->shouldBeCalled();
        $relationHandlerProphecy->getResolvedItemArray()->shouldBeCalled()->willReturn([
            [
                'table' => 'aForeignTable',
                'uid' => 1,
            ],
            [
                'table' => 'aForeignTable',
                'uid' => 2,
            ],
        ]);

        $expected = $input;
        $expected['databaseRow']['aField'] = [
            [
                'table' => 'aForeignTable',
                'uid' => null,
                'title' => '',
                'row' => null,
            ],
            [
                'table' => 'aForeignTable',
                'uid' => null,
                'title' => '',
                'row' => null,
            ]
        ];
        $expected['processedTca']['columns']['aField']['config']['clipboardElements'] = [];

        self::assertSame($expected, (new TcaGroup())->addData($input));
    }
}
