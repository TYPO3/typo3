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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaInlineTest extends UnitTestCase
{
    protected BackendUserAuthentication&MockObject $beUserMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beUserMock = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $this->beUserMock;
    }

    #[Test]
    public function addDataWithoutModifyRightsButWithInlineTypeWillNotParseChildren(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                        ],
                    ],
                ],
            ],
            'inlineFirstPid' => 0,
        ];

        $this->beUserMock
            ->expects($this->atLeastOnce())
            ->method('check')
            ->with(
                'tables_modify',
                $input['processedTca']['columns']['aField']['config']['foreign_table']
            )
            ->willReturn(false);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['children'] = [];
        self::assertEquals($expected, (new TcaInline($this->createMock(FlashMessageService::class)))->addData($input));
    }

    #[Test]
    public function addDataWithUserRightsButWithoutInlineTypeWillNotParseChildren(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'foreign_table' => 'aForeignTableName',
                        ],
                    ],
                ],
            ],
            'inlineFirstPid' => 0,
        ];

        $this->beUserMock
            ->expects($this->never())
            ->method('check')
            ->with(
                'tables_modify',
                $input['processedTca']['columns']['aField']['config']['foreign_table']
            );

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['type'] = 'input';
        self::assertEquals($expected, (new TcaInline($this->createMock(FlashMessageService::class)))->addData($input));
    }

    #[Test]
    public function addDataWithInlineTypeAndModifyRightsWillAddChildren(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                        ],
                    ],
                ],
            ],
            'inlineFirstPid' => 0,
            'inlineResolveExistingChildren' => false,
        ];

        $this->beUserMock
            ->expects($this->atLeastOnce())
            ->method('check')
            ->with(
                'tables_modify',
                $input['processedTca']['columns']['aField']['config']['foreign_table']
            )
            ->willReturn(true);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['children'] = [];
        self::assertEquals($expected, (new TcaInline($this->createMock(FlashMessageService::class)))->addData($input));
    }
}
