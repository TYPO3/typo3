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
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class TcaInlineTest extends UnitTestCase
{
    /**
     * @var TcaInline
     */
    protected $subject;

    /**
     * @var BackendUserAuthentication | ObjectProphecy
     */
    protected $beUserProphecy;

    protected function setUp()
    {
        $this->beUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $this->beUserProphecy->reveal();

        $this->subject = new TcaInline();
    }

    /**
     * @var array Set of default controls
     */
    protected $defaultConfig = [
        'processedTca' => [
            'columns' => [
                'aField' => [
                    'config' => [
                        'type' => 'inline',
                        'foreign_table' => 'aForeignTableName'
                    ],
                ],
            ],
        ],
        'inlineFirstPid' => 0,
    ];

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
        ];

        $this->beUserProphecy
            ->check(
                'tables_modify',
                $input['processedTca']['columns']['aField']['config']['foreign_table']
            )
            ->shouldBeCalled()
            ->willReturn(false);

        $this->assertEquals($this->defaultConfig, $this->subject->addData($input));
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
        ];

        $this->beUserProphecy
            ->check(
                'tables_modify',
                $input['processedTca']['columns']['aField']['config']['foreign_table']
            )
            ->shouldNotBeCalled();

        $expected = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['type'] = 'input';
        $this->assertEquals($expected, $this->subject->addData($input));
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
        ];

        $this->beUserProphecy
            ->check(
                'tables_modify',
                $input['processedTca']['columns']['aField']['config']['foreign_table']
            )
            ->shouldBeCalled()
            ->willReturn(true);

        $expected = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['children'] = [];
        $this->assertEquals($expected, $this->subject->addData($input));
    }
}
