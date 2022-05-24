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

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFolder;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaFolderTest extends UnitTestCase
{
    use ProphecyTrait;

    protected bool $resetSingletonInstances = true;

    /**
     * @test
     */
    public function addDataReturnsFieldUnchangedIfFieldIsNotTypeFolder(): void
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
        self::assertSame($expected, (new TcaFolder())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsFolderData(): void
    {
        $input = [
            'databaseRow' => [
                'aField' => '1:/aFolder/anotherFolder/',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'folder',
                            'maxitems' => 99999,
                        ],
                    ],
                ],
            ],
        ];

        $folderProphecy = $this->prophesize(Folder::class);

        $resourceFactoryProphecy = $this->prophesize(ResourceFactory::class);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactoryProphecy->reveal());
        $resourceFactoryProphecy->retrieveFileOrFolderObject('1:/aFolder/anotherFolder/')
            ->shouldBeCalled()
            ->willReturn($folderProphecy->reveal());

        $expected = $input;
        $expected['databaseRow']['aField'] = [
            [
                'folder' => '1:/aFolder/anotherFolder/',
            ],
        ];
        self::assertSame($expected, (new TcaFolder())->addData($input));
    }
}
