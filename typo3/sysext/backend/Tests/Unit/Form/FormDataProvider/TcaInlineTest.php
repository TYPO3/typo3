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
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaInlineTest extends UnitTestCase
{
    /**
     * @var BackendUserAuthentication|ObjectProphecy
     */
    protected $beUserProphecy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $this->beUserProphecy->reveal();
    }

    /**
     * @test
     */
    public function addDataWithoutModifyRightsButWithInlineTypeWillNotParseChildren()
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

        $this->beUserProphecy
            ->check(
                'tables_modify',
                $input['processedTca']['columns']['aField']['config']['foreign_table']
            )
            ->shouldBeCalled()
            ->willReturn(false);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['children'] = [];
        self::assertEquals($expected, (new TcaInline())->addData($input));
    }

    /**
     * @test
     */
    public function addDataWithUserRightsButWithoutInlineTypeWillNotParseChildren()
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

        $this->beUserProphecy
            ->check(
                'tables_modify',
                $input['processedTca']['columns']['aField']['config']['foreign_table']
            )
            ->shouldNotBeCalled();

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['type'] = 'input';
        self::assertEquals($expected, (new TcaInline())->addData($input));
    }

    /**
     * @test
     */
    public function addDataWithInlineTypeAndModifyRightsWillAddChildren()
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

        $this->beUserProphecy
            ->check(
                'tables_modify',
                $input['processedTca']['columns']['aField']['config']['foreign_table']
            )
            ->shouldBeCalled()
            ->willReturn(true);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['children'] = [];
        self::assertEquals($expected, (new TcaInline())->addData($input));
    }
}
