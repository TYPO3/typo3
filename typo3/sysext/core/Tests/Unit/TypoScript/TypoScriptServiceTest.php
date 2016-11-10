<?php
namespace TYPO3\CMS\Core\Tests\Unit\Service;

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

use TYPO3\CMS\Core\TypoScript\TypoScriptService;

/**
 * Test case
 */
class TypoScriptServiceTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * data provider for convertTypoScriptArrayToPlainArray
     * @return array
     */
    public function convertTypoScriptArrayToPlainArrayTestdata()
    {
        return [
            'simple typoscript array' => [
                'typoScriptSettings' => [
                    '10.' => [
                        'value' => 'Hello World!',
                        'foo.' => [
                            'bar' => 5
                        ]
                    ],
                    '10' => 'TEXT'
                ],
                'expectedSettings' => [
                    '10' => [
                        'value' => 'Hello World!',
                        'foo' => [
                            'bar' => 5
                        ],
                        '_typoScriptNodeValue' => 'TEXT'
                    ]
                ]
            ],
            'typoscript with intermediate dots' => [
                'typoScriptSettings' => [
                    '10.' => [
                        'value' => 'Hello World!',
                        'foo.' => [
                            'bar' => 5
                        ]
                    ],
                    '10' => 'TEXT'
                ],
                'expectedSettings' => [
                    '10' => [
                        'value' => 'Hello World!',
                        'foo' => [
                            'bar' => 5
                        ],
                        '_typoScriptNodeValue' => 'TEXT'
                    ]
                ]
            ],
            'typoscript array with changed order' => [
                'typoScriptSettings' => [
                    '10' => 'TEXT',
                    '10.' => [
                        'value' => 'Hello World!',
                        'foo.' => [
                            'bar' => 5
                        ]
                    ]
                ],
                'expectedSettings' => [
                    '10' => [
                        'value' => 'Hello World!',
                        'foo' => [
                            'bar' => 5
                        ],
                        '_typoScriptNodeValue' => 'TEXT'
                    ]
                ]
            ],
            'nested typoscript array' => [
                'typoScriptSettings' => [
                    '10' => 'COA',
                    '10.' => [
                        '10' => 'TEXT',
                        '10.' => [
                            'value' => 'Hello World!',
                            'foo.' => [
                                'bar' => 5
                            ]
                        ],
                        '20' => 'COA',
                        '20.' => [
                            '10' => 'TEXT',
                            '10.' => [
                                'value' => 'Test',
                                'wrap' => '[|]'
                            ],
                            '20' => 'TEXT',
                            '20.' => [
                                'value' => 'Test',
                                'wrap' => '[|]'
                            ]
                        ],
                        '30' => 'custom'
                    ]
                ],
                'expectedSettings' => [
                    '10' => [
                        '10' => [
                            'value' => 'Hello World!',
                            'foo' => [
                                'bar' => 5
                            ],
                            '_typoScriptNodeValue' => 'TEXT'
                        ],
                        '20' => [
                            '10' => [
                                'value' => 'Test',
                                'wrap' => '[|]',
                                '_typoScriptNodeValue' => 'TEXT'
                            ],
                            '20' => [
                                'value' => 'Test',
                                'wrap' => '[|]',
                                '_typoScriptNodeValue' => 'TEXT'
                            ],
                            '_typoScriptNodeValue' => 'COA'
                        ],
                        '30' => 'custom',
                        '_typoScriptNodeValue' => 'COA'
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider convertTypoScriptArrayToPlainArrayTestdata
     * @param mixed $typoScriptSettings
     * @param mixed $expectedSettings
     */
    public function convertTypoScriptArrayToPlainArrayRemovesTrailingDotsWithChangedOrderInTheTypoScriptArray($typoScriptSettings, $expectedSettings)
    {
        $typoScriptService = new TypoScriptService();
        $processedSettings = $typoScriptService->convertTypoScriptArrayToPlainArray($typoScriptSettings);
        $this->assertEquals($expectedSettings, $processedSettings);
    }

    /**
     * Dataprovider for testcase "convertPlainArrayToTypoScriptArray"
     *
     * @return array
     */
    public function convertPlainArrayToTypoScriptArrayTestdata()
    {
        return [
            'simple typoscript' => [
                'extbaseTS' => [
                    '10' => [
                        'value' => 'Hallo',
                        '_typoScriptNodeValue' => 'TEXT'
                    ]
                ],
                'classic' => [
                    '10' => 'TEXT',
                    '10.' => [
                        'value' => 'Hallo'
                    ]
                ]
            ],
            'typoscript with null value' => [
                'extbaseTS' => [
                    '10' => [
                        'value' => 'Hallo',
                        '_typoScriptNodeValue' => 'TEXT'
                    ],
                    '20' => null
                ],
                'classic' => [
                    '10' => 'TEXT',
                    '10.' => [
                        'value' => 'Hallo'
                    ],
                    '20' => ''
                ]
            ],
            'ts with dots in key' => [
                'extbaseTS' => [
                    '1.0' => [
                        'value' => 'Hallo',
                        '_typoScriptNodeValue' => 'TEXT'
                    ]
                ],
                'classic' => [
                    '1.0' => 'TEXT',
                    '1.0.' => [
                        'value' => 'Hallo'
                    ]
                ]
            ],
            'ts with backslashes in key' => [
                'extbaseTS' => [
                    '1\\0\\' => [
                        'value' => 'Hallo',
                        '_typoScriptNodeValue' => 'TEXT'
                    ]
                ],
                'classic' => [
                    '1\\0\\' => 'TEXT',
                    '1\\0\\.' => [
                        'value' => 'Hallo'
                    ]
                ]
            ],
            'bigger typoscript' => [
                'extbaseTS' => [
                    '10' => [
                        '10' => [
                            'value' => 'Hello World!',
                            'foo' => [
                                'bar' => 5
                            ],
                            '_typoScriptNodeValue' => 'TEXT'
                        ],
                        '20' => [
                            '10' => [
                                'value' => 'Test',
                                'wrap' => '[|]',
                                '_typoScriptNodeValue' => 'TEXT'
                            ],
                            '20' => [
                                'value' => 'Test',
                                'wrap' => '[|]',
                                '_typoScriptNodeValue' => 'TEXT'
                            ],
                            '_typoScriptNodeValue' => 'COA'
                        ],
                        '_typoScriptNodeValue' => 'COA'
                    ]
                ],
                'classic' => [
                    '10' => 'COA',
                    '10.' => [
                        '10' => 'TEXT',
                        '10.' => [
                            'value' => 'Hello World!',
                            'foo.' => [
                                'bar' => 5
                            ]
                        ],
                        '20' => 'COA',
                        '20.' => [
                            '10' => 'TEXT',
                            '10.' => [
                                'value' => 'Test',
                                'wrap' => '[|]'
                            ],
                            '20' => 'TEXT',
                            '20.' => [
                                'value' => 'Test',
                                'wrap' => '[|]'
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider convertPlainArrayToTypoScriptArrayTestdata
     * @param mixed $extbaseTS
     * @param mixed $classic
     */
    public function convertPlainArrayToTypoScriptArray($extbaseTS, $classic)
    {
        $typoScriptService = new TypoScriptService();
        $converted = $typoScriptService->convertPlainArrayToTypoScriptArray($extbaseTS);
        $this->assertEquals($converted, $classic);
    }

    /**
     * @return array
     */
    public function explodeConfigurationForOptionSplitProvider()
    {
        return [
            [
                ['splitConfiguration' => 'a'],
                3,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'a'],
                    2 => ['splitConfiguration' => 'a']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b || c'],
                5,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'c'],
                    3 => ['splitConfiguration' => 'c'],
                    4 => ['splitConfiguration' => 'c']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b |*| c'],
                5,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'c'],
                    3 => ['splitConfiguration' => 'c'],
                    4 => ['splitConfiguration' => 'c']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b |*| c |*| d || e'],
                7,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'c'],
                    3 => ['splitConfiguration' => 'c'],
                    4 => ['splitConfiguration' => 'c'],
                    5 => ['splitConfiguration' => 'd'],
                    6 => ['splitConfiguration' => 'e']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b |*| c |*| d || e'],
                4,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'd'],
                    3 => ['splitConfiguration' => 'e']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b |*| c |*| d || e'],
                3,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'd'],
                    2 => ['splitConfiguration' => 'e']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b |*||*| c || d'],
                7,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'b'],
                    3 => ['splitConfiguration' => 'b'],
                    4 => ['splitConfiguration' => 'b'],
                    5 => ['splitConfiguration' => 'c'],
                    6 => ['splitConfiguration' => 'd']
                ]
            ],
            [
                ['splitConfiguration' => '|*||*| a || b'],
                7,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'a'],
                    2 => ['splitConfiguration' => 'a'],
                    3 => ['splitConfiguration' => 'a'],
                    4 => ['splitConfiguration' => 'a'],
                    5 => ['splitConfiguration' => 'a'],
                    6 => ['splitConfiguration' => 'b']
                ]
            ],
            [
                ['splitConfiguration' => 'a |*| b || c |*|'],
                7,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'c'],
                    3 => ['splitConfiguration' => 'b'],
                    4 => ['splitConfiguration' => 'c'],
                    5 => ['splitConfiguration' => 'b'],
                    6 => ['splitConfiguration' => 'c']
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider explodeConfigurationForOptionSplitProvider
     * @see https://docs.typo3.org/typo3cms/TyposcriptReference/ObjectsAndProperties/Index.html#objects-optionsplit
     */
    public function explodeConfigurationForOptionSplitTest($configuration, $splitCount, $expected)
    {
        $serviceObject = new TypoScriptService();
        $actual = $serviceObject->explodeConfigurationForOptionSplit($configuration, $splitCount);
        $this->assertSame($expected, $actual);
    }
}
