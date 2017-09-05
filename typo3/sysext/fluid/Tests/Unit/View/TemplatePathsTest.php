<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\View;

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

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\TemplatePaths;

/**
 * Test case
 */
class TemplatePathsTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @return array
     */
    public function getPathSetterMethodTestValues()
    {
        $generator = function ($method, $indexType = 'numeric') {
            switch ($indexType) {
                default:
                case 'numeric':
                    $set = [
                        20 => 'bar',
                        0 => 'baz',
                        100 => 'boz',
                        10 => 'foo',
                    ];
                    $expected = [
                        0 => 'baz',
                        10 => 'foo',
                        20 => 'bar',
                        100 => 'boz',
                    ];
                    break;
                case 'alpha':
                    $set = [
                        'bcd' => 'bar',
                        'abc' => 'foo',
                    ];
                    $expected = [
                        'bcd' => 'bar',
                        'abc' => 'foo',
                    ];
                    break;
                case 'alphanumeric':
                    $set = [
                        0 => 'baz',
                        'bcd' => 'bar',
                        15 => 'boz',
                        'abc' => 'foo',
                    ];
                    $expected = [
                        0 => 'baz',
                        'bcd' => 'bar',
                        15 => 'boz',
                        'abc' => 'foo',
                    ];
                    break;
            }
            return [$method, $set, $expected];
        };
        return [
            'simple numeric index, template' => $generator(TemplatePaths::CONFIG_TEMPLATEROOTPATHS, 'numeric'),
            'alpha index, template' => $generator(TemplatePaths::CONFIG_TEMPLATEROOTPATHS, 'alpha'),
            'alpha-numeric index, template' => $generator(TemplatePaths::CONFIG_TEMPLATEROOTPATHS, 'alphanumeric'),
            'simple numeric index, partial' => $generator(TemplatePaths::CONFIG_PARTIALROOTPATHS, 'numeric'),
            'alpha index, partial' => $generator(TemplatePaths::CONFIG_PARTIALROOTPATHS, 'alpha'),
            'alpha-numeric index, partial' => $generator(TemplatePaths::CONFIG_PARTIALROOTPATHS, 'alphanumeric'),
            'simple numeric index, layout' => $generator(TemplatePaths::CONFIG_LAYOUTROOTPATHS, 'numeric'),
            'alpha index, layout' => $generator(TemplatePaths::CONFIG_LAYOUTROOTPATHS, 'alpha'),
            'alpha-numeric index, layout' => $generator(TemplatePaths::CONFIG_LAYOUTROOTPATHS, 'alphanumeric'),
        ];
    }

    /**
     * @test
     * @dataProvider getPathSetterMethodTestValues
     * @param string $method
     * @param array $paths
     * @param array $expected
     */
    public function pathSetterMethodSortsPathsByKeyDescending($method, array $paths, array $expected)
    {
        $setter = 'set' . ucfirst($method);
        $subject = $this->getMockBuilder(TemplatePaths::class)->setMethods(['sanitizePath'])->getMock();
        $subject->expects($this->any())->method('sanitizePath')->willReturnArgument(0);
        $subject->$setter($paths);
        $this->assertAttributeSame($expected, $method, $subject);
    }

    /**
     * Bulk test to confirm that configuration is returned correctly based on
     * different TypoScript and FE/BE mode, and mixed sorting results in a
     * correctly sorted set of template paths.
     *
     * @param bool $frontendMode
     * @param array $typoScript
     * @param array $expectedPaths
     * @test
     * @dataProvider getContextSpecificViewConfigurationTestValues
     */
    public function getContextSpecificViewConfigurationMergesAndSortsPaths($frontendMode, array $typoScript, array $expectedPaths)
    {
        $configurationManager = $this->getMockBuilder(ConfigurationManagerInterface::class)->getMockForAbstractClass();
        $configurationManager->expects($this->once())->method('getConfiguration')->willReturn($typoScript);
        $subject = $this->getMockBuilder(TemplatePaths::class)->setMethods(['getConfigurationManager', 'getExtensionPrivateResourcesPath', 'isBackendMode', 'isFrontendMode'])->getMock();
        $subject->expects($this->once())->method('getExtensionPrivateResourcesPath')->with('test')->willReturn('test/');
        $subject->expects($this->once())->method('getConfigurationManager')->willReturn($configurationManager);
        $subject->expects($this->any())->method('isBackendMode')->willReturn(!$frontendMode);
        $subject->expects($this->any())->method('isFrontendMode')->willReturn($frontendMode);
        $result = $this->callInaccessibleMethod($subject, 'getContextSpecificViewConfiguration', 'test');
        $this->assertSame($expectedPaths, $result);
    }

    /**
     * Confirmation that if an empty extension key context is passed,
     * TemplatePaths will be unable to return even fallback paths and
     * will instead return an empty array and never call any methods
     * to get TypoScript etc.
     *
     * @test
     */
    public function getContextSpecificViewConfigurationReturnsEmptyPathsForEmptyExtensionKey()
    {
        $subject = $this->getMockBuilder(TemplatePaths::class)->setMethods(['getConfigurationManager'])->getMock();
        $subject->expects($this->never())->method('getConfigurationManager');
        $this->callInaccessibleMethod($subject, 'getContextSpecificViewConfiguration', '');
    }

    /**
     * @return array
     */
    public function getContextSpecificViewConfigurationTestValues()
    {
        return [
            'complete TypoScript configuration with mixed-sorting paths get sorted correctly in backend mode' => [
                false,
                [
                    'module.' => [
                        'tx_test.' => [
                            'view.' => [
                                'templateRootPaths.' => [
                                    '30' => 'third',
                                    '10' => 'first',
                                    '20' => 'second'
                                ],
                                'partialRootPaths.' => [
                                    '20' => '2',
                                    '30' => '3',
                                    '10' => '1'
                                ],
                                'layoutRootPaths.' => [
                                    '130' => '3.',
                                    '10' => '1.',
                                    '120' => '2.'
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    'templateRootPaths' => [
                        'test/Templates/',
                        'first',
                        'second',
                        'third'
                    ],
                    'partialRootPaths' => [
                        'test/Partials/',
                        '1',
                        '2',
                        '3'
                    ],
                    'layoutRootPaths' => [
                        'test/Layouts/',
                        '1.',
                        '2.',
                        '3.'
                    ]
                ]
            ],
            'complete TypoScript configuration with mixed-sorting paths get sorted correctly in frontend mode' => [
                true,
                [
                    'plugin.' => [
                        'tx_test.' => [
                            'view.' => [
                                'templateRootPaths.' => [
                                    '30' => 'third',
                                    '10' => 'first',
                                    '20' => 'second'
                                ],
                                'partialRootPaths.' => [
                                    '20' => '2',
                                    '30' => '3',
                                    '10' => '1'
                                ],
                                'layoutRootPaths.' => [
                                    '130' => '3.',
                                    '10' => '1.',
                                    '120' => '2.'
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    'templateRootPaths' => [
                        'test/Templates/',
                        'first',
                        'second',
                        'third'
                    ],
                    'partialRootPaths' => [
                        'test/Partials/',
                        '1',
                        '2',
                        '3'
                    ],
                    'layoutRootPaths' => [
                        'test/Layouts/',
                        '1.',
                        '2.',
                        '3.'
                    ]
                ]
            ],
            'partial TypoScript configuration merges fallback templateRootPaths' => [
                true,
                [
                    'plugin.' => [
                        'tx_test.' => [
                            'view.' => [
                                'partialRootPaths.' => [
                                    '20' => '2',
                                    '30' => '3',
                                    '10' => '1'
                                ],
                                'layoutRootPaths.' => [
                                    '130' => '3.',
                                    '10' => '1.',
                                    '120' => '2.'
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    'templateRootPaths' => [
                        'test/Templates/'
                    ],
                    'partialRootPaths' => [
                        'test/Partials/',
                        '1',
                        '2',
                        '3'
                    ],
                    'layoutRootPaths' => [
                        'test/Layouts/',
                        '1.',
                        '2.',
                        '3.'
                    ]
                ]
            ],
            'partial TypoScript configuration merges fallback partialRootPaths' => [
                true,
                [
                    'plugin.' => [
                        'tx_test.' => [
                            'view.' => [
                                'templateRootPaths.' => [
                                    '20' => '2',
                                    '30' => '3',
                                    '10' => '1'
                                ],
                                'layoutRootPaths.' => [
                                    '130' => '3.',
                                    '10' => '1.',
                                    '120' => '2.'
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    'templateRootPaths' => [
                        'test/Templates/',
                        '1',
                        '2',
                        '3'
                    ],
                    'partialRootPaths' => [
                        'test/Partials/'
                    ],
                    'layoutRootPaths' => [
                        'test/Layouts/',
                        '1.',
                        '2.',
                        '3.'
                    ]
                ]
            ],
            'partial TypoScript configuration merges fallback layoutRootPaths' => [
                true,
                [
                    'plugin.' => [
                        'tx_test.' => [
                            'view.' => [
                                'templateRootPaths.' => [
                                    '20' => '2',
                                    '30' => '3',
                                    '10' => '1'
                                ],
                                'partialRootPaths.' => [
                                    '130' => '3.',
                                    '10' => '1.',
                                    '120' => '2.'
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    'templateRootPaths' => [
                        'test/Templates/',
                        '1',
                        '2',
                        '3'
                    ],
                    'partialRootPaths' => [
                        'test/Partials/',
                        '1.',
                        '2.',
                        '3.'
                    ],
                    'layoutRootPaths' => [
                        'test/Layouts/'
                    ]
                ]
            ],
            'partial TypoScript configuration merges fallback layoutRootPaths and partialRootPaths' => [
                true,
                [
                    'plugin.' => [
                        'tx_test.' => [
                            'view.' => [
                                'templateRootPaths.' => [
                                    '20' => '2',
                                    '30' => '3',
                                    '10' => '1'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'templateRootPaths' => [
                        'test/Templates/',
                        '1',
                        '2',
                        '3'
                    ],
                    'partialRootPaths' => [
                        'test/Partials/'
                    ],
                    'layoutRootPaths' => [
                        'test/Layouts/'
                    ]
                ]
            ],
            'missing TypoScript configuration returns fallback path collection' => [
                true,
                [],
                [
                    'templateRootPaths' => [
                        'test/Templates/'
                    ],
                    'partialRootPaths' => [
                        'test/Partials/'
                    ],
                    'layoutRootPaths' => [
                        'test/Layouts/'
                    ]
                ]
            ],
        ];
    }

    /**
     * Test to confirm that regardless of TypoScript configuration, an unknown FE/BE mode
     * results in purely fallback template paths being returned (since not knowing the
     * mode means inability to resolve correct TS TLO plugin./module.)
     *
     * @test
     */
    public function getContextSpecificViewConfigurationDoesNotResolveFromTypoScriptAndDoesNotSortInUnspecifiedMode()
    {
        $configurationManager = $this->getMockBuilder(ConfigurationManagerInterface::class)->getMockForAbstractClass();
        $configurationManager->expects($this->once())->method('getConfiguration')->willReturn([
            'plugin.' => [
                'tx_test.' => [
                    'view.' => [
                        'templateRootPaths.' => [
                            '30' => 'third',
                            '10' => 'first',
                            '20' => 'second'
                        ],
                        'partialRootPaths.' => [
                            '20' => '2',
                            '30' => '3',
                            '10' => '1'
                        ],
                        'layoutRootPaths.' => [
                            '130' => '3.',
                            '10' => '1.',
                            '120' => '2.'
                        ],
                    ]
                ]
            ]
        ]);
        $subject = $this->getMockBuilder(TemplatePaths::class)->setMethods(['getConfigurationManager', 'getExtensionPrivateResourcesPath', 'isBackendMode', 'isFrontendMode'])->getMock();
        $subject->expects($this->once())->method('getExtensionPrivateResourcesPath')->with('test')->willReturn('test/');
        $subject->expects($this->once())->method('getConfigurationManager')->willReturn($configurationManager);
        $subject->expects($this->once())->method('isBackendMode')->willReturn(false);
        $subject->expects($this->once())->method('isFrontendMode')->willReturn(false);
        $result = $this->callInaccessibleMethod($subject, 'getContextSpecificViewConfiguration', 'test');
        $this->assertSame([
            'templateRootPaths' => [
                'test/Templates/'
            ],
            'partialRootPaths' => [
                'test/Partials/'
            ],
            'layoutRootPaths' => [
                'test/Layouts/'
            ]
        ], $result);
    }

    /**
     * Test to confirm that regardless which setter methods for path collections
     * were called, the getContextSpecificViewConfiguration() method always returns
     * the paths that were configured in TypoScript and does not change any of the
     * paths set via the setter methods.
     *
     * @dataProvider getContextSpecificViewConfigurationIgnoresValues
     * @test
     */
    public function getContextSpecificViewConfigurationIgnoresValuesSetInPathSetterMethods($frontendMode, array $paths)
    {
        $configurationManager = $this->getMockBuilder(ConfigurationManagerInterface::class)->getMockForAbstractClass();
        $configurationManager->expects($this->once())->method('getConfiguration')->willReturn([
            'plugin.' => [
                'tx_test.' => [
                    'view.' => [
                        'templateRootPaths.' => [
                            '30' => 'third',
                            '10' => 'first',
                            '20' => 'second'
                        ],
                        'partialRootPaths.' => [
                            '20' => '2',
                            '30' => '3',
                            '10' => '1'
                        ],
                        'layoutRootPaths.' => [
                            '130' => '3.',
                            '10' => '1.',
                            '120' => '2.'
                        ],
                    ]
                ]
            ]
        ]);
        $subject = $this->getMockBuilder(TemplatePaths::class)->setMethods(['getConfigurationManager', 'getExtensionPrivateResourcesPath', 'isBackendMode', 'isFrontendMode', 'sanitizePath'])->getMock();
        $subject->expects($this->once())->method('getExtensionPrivateResourcesPath')->with('test')->willReturn('test/');
        $subject->expects($this->once())->method('getConfigurationManager')->willReturn($configurationManager);
        $subject->expects($this->once())->method('isBackendMode')->willReturn(!$frontendMode);
        $subject->expects($this->once())->method('isFrontendMode')->willReturn($frontendMode);

        // Set paths and expect sanitizePath() to be called, emulate return from sanitizePath()
        $subject->expects($this->atLeastOnce())->method('sanitizePath')->willReturnArgument(0);
        foreach ($paths as $key => $pathSet) {
            $setter = 'set' . ucfirst($key);
            $subject->$setter($pathSet);
        }

        $result = $this->callInaccessibleMethod($subject, 'getContextSpecificViewConfiguration', 'test');
        $this->assertSame([
            'templateRootPaths' => [
                'test/Templates/',
                'first',
                'second',
                'third'
            ],
            'partialRootPaths' => [
                'test/Partials/',
                '1',
                '2',
                '3'
            ],
            'layoutRootPaths' => [
                'test/Layouts/',
                '1.',
                '2.',
                '3.'
            ]
        ], $result);
        foreach ($paths as $key => $pathSet) {
            $this->assertAttributeSame($pathSet, $key, $subject);
        }
    }

    /**
     * @return array
     */
    public function getContextSpecificViewConfigurationIgnoresValues()
    {
        return [
            'has templateRootPaths set with setter method' => [
                true,
                [
                    'templateRootPaths' => [
                        '1.',
                        '2.'
                    ]
                ]
            ],
            'has partialRootPaths set with setter method' => [
                true,
                [
                    'partialRootPaths' => [
                        '1.',
                        '2.'
                    ]
                ]
            ],
            'has layoutRootPaths set with setter method' => [
                true,
                [
                    'layoutRootPaths' => [
                        '1.',
                        '2.'
                    ]
                ]
            ],
        ];
    }
}
