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

namespace TYPO3\CMS\Tstemplate\Tests\Unit\Controller;

use TYPO3\CMS\Tstemplate\Controller\TypoScriptObjectBrowserController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TypoScriptObjectBrowserControllerTest extends UnitTestCase
{
    /**
     * @dataProvider getSetupDataProvider
     * @test
     */
    public function getSetupTest($setup, $key, $expected): void
    {
        $subject = $this->getAccessibleMock(
            TypoScriptObjectBrowserController::class,
            null,
            [],
            '',
            false
        );
        $actual = $subject->_call('getSetup', $setup, $key);
        self::assertEquals($expected, $actual);
    }

    public function getSetupDataProvider(): array
    {
        return [
            'empty setup and key' => [
                [],
                '',
                [[], ''],
            ],
            'empty setup and not empty key' => [
                [],
                'key',
                [[], ''],
            ],
            'empty key' => [
                'typoScriptSetup' => [
                    '10.' => [
                        'value' => 'Hello World!',
                        'foo.' => [
                            'bar' => 5,
                        ],
                    ],
                    '10' => 'TEXT',
                ],
                'key' => '',
                'expected' => [
                    [
                        '10.' => [
                            'value' => 'Hello World!',
                            'foo.' => [
                                'bar' => 5,
                            ],
                        ],
                        '10' => 'TEXT',
                    ],
                    '',
                ],
            ],
            'special key "0" which is considered as empty' => [
                'typoScriptSetup' => [
                    '10.' => [
                        'value' => 'Hello World!',
                        'foo.' => [
                            'bar' => 5,
                        ],
                    ],
                    '10' => 'TEXT',
                ],
                'key' => '0',
                'expected' => [
                    [
                        '10.' => [
                            'value' => 'Hello World!',
                            'foo.' => [
                                'bar' => 5,
                            ],
                        ],
                        '10' => 'TEXT',
                    ],
                    '',
                ],
            ],
            'not empty key - 1st level' => [
                'typoScriptSetup' => [
                    '10.' => [
                        'value' => 'Hello World!',
                        'foo.' => [
                            'bar' => 5,
                        ],
                    ],
                    '10' => 'TEXT',
                ],
                'key' => '10',
                'expected' => [
                    [
                        'value' => 'Hello World!',
                        'foo.' => [
                            'bar' => 5,
                        ],
                    ],
                    'TEXT',
                ],
            ],
            'not empty key - 2nd level' => [
                'typoScriptSetup' => [
                    '10.' => [
                        'value' => 'Hello World!',
                        'foo.' => [
                            'bar' => 5,
                        ],
                    ],
                    '10' => 'TEXT',
                ],
                'key' => '10.foo',
                'expected' => [
                    [
                        'bar' => 5,
                    ],
                    '',
                ],
            ],
            'not empty key - 3rd level - leaf' => [
                'typoScriptSetup' => [
                    '10.' => [
                        'value' => 'Hello World!',
                        'foo.' => [
                            'bar' => 5,
                        ],
                    ],
                    '10' => 'TEXT',
                ],
                'key' => '10.foo.bar',
                'expected' => [
                    [],
                    '5',
                ],
            ],
            'not empty key - 4th, non existing level' => [
                'typoScriptSetup' => [
                    '10.' => [
                        'value' => 'Hello World!',
                        'foo.' => [
                            'bar' => 5,
                        ],
                    ],
                    '10' => 'TEXT',
                ],
                'key' => '10.foo.bar.baz',
                'expected' => [
                    [],
                    '',
                ],
            ],
        ];
    }
}
