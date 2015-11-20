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

use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class TcaGroupTest extends UnitTestCase
{
    /**
     * @var TcaGroup
     */
    protected $subject;

    /**
     * @var array
     */
    protected $singletonInstances;

    protected function setUp()
    {
        $this->subject = new TcaGroup();
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
    }

    protected function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

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
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionWithTypeGroupAndNoValidInternalType()
    {
        $input = [
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
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438780511);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsFileData()
    {
        $input = [
            'databaseRow' => [
                'aField' => '/aDir/aFile.txt,/anotherDir/anotherFile.css',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'file',
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['databaseRow']['aField'] = '%2FaDir%2FaFile.txt|aFile.txt,%2FanotherDir%2FanotherFile.css|anotherFile.css';
        $this->assertSame($expected, $this->subject->addData($input));
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
                        ],
                    ],
                ],
            ],
        ];

        /** @var Folder|ObjectProphecy $relationHandlerProphecy */
        $folderProphecy = $this->prophesize(Folder::class);
        $folderProphecy->getIdentifier()->shouldBeCalled()->willReturn('anotherFolder');

        /** @var ResourceFactory|ObjectProphecy $relationHandlerProphecy */
        $resourceFactoryProphecy = $this->prophesize(ResourceFactory::class);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactoryProphecy->reveal());
        $resourceFactoryProphecy->retrieveFileOrFolderObject('1:/aFolder/anotherFolder/')
            ->shouldBeCalled()
            ->willReturn($folderProphecy->reveal());

        $expected = $input;
        $expected['databaseRow']['aField'] = '1%3A%2FaFolder%2FanotherFolder%2F|anotherFolder';
        $this->assertSame($expected, $this->subject->addData($input));
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

        /** @var RelationHandler|ObjectProphecy $relationHandlerProphecy */
        $relationHandlerProphecy = $this->prophesize(RelationHandler::class);
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerProphecy->reveal());
        $relationHandlerProphecy->start('1,2', 'aForeignTable', 'mmTableName', 42, 'aTable', $aFieldConfig)->shouldBeCalled();
        $relationHandlerProphecy->getFromDB()->shouldBeCalled();
        $relationHandlerProphecy->readyForInterface()->shouldBeCalled()->willReturn('1|aLabel,2|anotherLabel');

        $expected = $input;
        $expected['databaseRow']['aField'] = '1|aLabel,2|anotherLabel';

        $this->assertSame($expected, $this->subject->addData($input));
    }
}
