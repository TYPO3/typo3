<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

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

use TYPO3\CMS\Core\Configuration\Richtext;

/**
 * Test case
 */
class RichtextTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function getConfigurationUsesOverruleModeFromType()
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
            'properties' => [
                'classes.' => [
                    'aClass' => 'aConfig',
                ],
                'default.' => [
                    'removeComments' => '1',
                ],
                'config.' => [
                    'aTable.' => [
                        'aField.' => [
                            'types.' => [
                                'textmedia.' => [
                                    'proc.' => [
                                        'overruleMode' => 'myTransformation',
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $expected = [
            'classes.' => [
                'aClass' => 'aConfig',
            ],
            'removeComments' => '1',
            'proc.' => [
                'overruleMode' => 'myTransformation',
            ],
            'classes' => [
                'aClass' => 'aConfig',
            ],
            'proc' => [
                'overruleMode' => 'myTransformation',
            ],
        ];
        // Accessible mock to $subject since getRtePageTsConfigOfPid calls BackendUtility::getPagesTSconfig()
        // which can't be mocked in a sane way
        $subject = $this->getAccessibleMock(Richtext::class, ['getRtePageTsConfigOfPid'], [], '', false);
        $subject->expects($this->once())->method('getRtePageTsConfigOfPid')->with(42)->willReturn($pageTsConfig);
        $output = $subject->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig);
        $this->assertSame($expected, $output);
    }

    /**
     * @test
     */
    public function getConfigurationUsesOverruleModeFromConfig()
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
            'properties' => [
                'classes.' => [
                    'aClass' => 'aConfig',
                ],
                'default.' => [
                    'removeComments' => '1',
                ],
                'config.' => [
                    'aTable.' => [
                        'aField.' => [
                            'proc.' => [
                                'overruleMode' => 'myTransformation',
                            ],
                        ]
                    ]
                ]
            ]
        ];
        $expected = [
            'classes.' => [
                'aClass' => 'aConfig',
            ],
            'removeComments' => '1',
            'proc.' => [
                'overruleMode' => 'myTransformation',
            ],
            'classes' => [
                'aClass' => 'aConfig',
            ],
            'proc' => [
                'overruleMode' => 'myTransformation',
            ],
        ];
        // Accessible mock to $subject since getRtePageTsConfigOfPid calls BackendUtility::getPagesTSconfig()
        // which can't be mocked in a sane way
        $subject = $this->getAccessibleMock(Richtext::class, ['getRtePageTsConfigOfPid'], [], '', false);
        $subject->expects($this->once())->method('getRtePageTsConfigOfPid')->with(42)->willReturn($pageTsConfig);
        $output = $subject->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig);
        $this->assertSame($expected, $output);
    }

    /**
     * @test
     */
    public function getConfigurationSetsOverruleModeIfMissing()
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
            'properties' => [
                'classes.' => [
                    'aClass' => 'aConfig',
                ],
                'default.' => [
                    'removeComments' => '1',
                ],
            ]
        ];
        $expected = [
            'classes.' => [
                'aClass' => 'aConfig',
            ],
            'removeComments' => '1',
            'classes' => [
                'aClass' => 'aConfig',
            ],
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];
        // Accessible mock to $subject since getRtePageTsConfigOfPid calls BackendUtility::getPagesTSconfig()
        // which can't be mocked in a sane way
        $subject = $this->getAccessibleMock(Richtext::class, ['getRtePageTsConfigOfPid'], [], '', false);
        $subject->expects($this->once())->method('getRtePageTsConfigOfPid')->with(42)->willReturn($pageTsConfig);
        $output = $subject->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig);
        $this->assertSame($expected, $output);
    }

    /**
     * @test
     */
    public function getConfigurationMigratesTsCss()
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
            'properties' => [
                'classes.' => [
                    'aClass' => 'aConfig',
                ],
                'default.' => [
                    'proc.' => [
                        'overruleMode' => 'ts_css',
                    ],
                ],
            ],
        ];
        $expected = [
            'classes.' => [
                'aClass' => 'aConfig',
            ],
            'proc.' => [
                'overruleMode' => 'default',
            ],
            'classes' => [
                'aClass' => 'aConfig',
            ],
            'proc' => [
                'overruleMode' => 'ts_css',
            ],
        ];
        // Accessible mock to $subject since getRtePageTsConfigOfPid calls BackendUtility::getPagesTSconfig()
        // which can't be mocked in a sane way
        $subject = $this->getAccessibleMock(Richtext::class, ['getRtePageTsConfigOfPid'], [], '', false);
        $subject->expects($this->once())->method('getRtePageTsConfigOfPid')->with(42)->willReturn($pageTsConfig);
        $output = $subject->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig);
        $this->assertSame($expected, $output);
    }

    /**
     * @test
     */
    public function getConfigurationOverridesByDefault()
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
            'properties' => [
                'classes.' => [
                    'aClass' => 'aConfig',
                ],
                'default.' => [
                    'classes.' => [
                        'aClass' => 'anotherConfig',
                    ],
                    'editor.' => [
                        'config.' => [
                            'contentsCss' => 'my.css'
                        ]
                    ],
                ],
            ],
        ];
        $expected = [
            'classes.' => [
                'aClass' => 'anotherConfig',
            ],
            'editor.' => [
                'config.' => [
                    'contentsCss' => 'my.css'
                ]
            ],
            'classes' => [
                'aClass' => 'anotherConfig',
            ],
            'editor' => [
                'config' => [
                    'contentsCss' => 'my.css'
                ]
            ],
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];
        // Accessible mock to $subject since getRtePageTsConfigOfPid calls BackendUtility::getPagesTSconfig()
        // which can't be mocked in a sane way
        $subject = $this->getAccessibleMock(Richtext::class, ['getRtePageTsConfigOfPid'], [], '', false);
        $subject->expects($this->once())->method('getRtePageTsConfigOfPid')->with(42)->willReturn($pageTsConfig);
        $output = $subject->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig);
        $this->assertSame($expected, $output);
    }

    /**
     * @test
     */
    public function getConfigurationOverridesByFieldSpecificConfig()
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
            'properties' => [
                'classes.' => [
                    'aClass' => 'aConfig',
                ],
                'default.' => [
                    'classes.' => [
                        'aClass' => 'anotherConfig',
                    ],
                ],
                'config.' => [
                    'aTable.' => [
                        'aField.' => [
                            'classes.' => [
                                'aClass' => 'aThirdConfig',
                            ],
                            'editor.' => [
                                'config.' => [
                                    'contentsCss' => 'my.css'
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            // Config with pagets dots
            'classes.' => [
                'aClass' => 'aThirdConfig',
            ],
            'editor.' => [
                'config.' => [
                    'contentsCss' => 'my.css'
                ]
            ],
            // Config without pagets dots
            'classes' => [
                'aClass' => 'aThirdConfig',
            ],
            'editor' => [
                'config' => [
                    'contentsCss' => 'my.css'
                ]
            ],
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];
        // Accessible mock to $subject since getRtePageTsConfigOfPid calls BackendUtility::getPagesTSconfig()
        // which can't be mocked in a sane way
        $subject = $this->getAccessibleMock(Richtext::class, ['getRtePageTsConfigOfPid'], [], '', false);
        $subject->expects($this->once())->method('getRtePageTsConfigOfPid')->with(42)->willReturn($pageTsConfig);
        $output = $subject->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig);
        $this->assertSame($expected, $output);
    }

    /**
     * @test
     */
    public function getConfigurationOverridesByFieldAndTypeSpecificConfig()
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
            'properties' => [
                'classes.' => [
                    'aClass' => 'aConfig',
                ],
                'default.' => [
                    'classes.' => [
                        'aClass' => 'anotherConfig',
                    ],
                ],
                'config.' => [
                    'aTable.' => [
                        'aField.' => [
                            'classes.' => [
                                'aClass' => 'aThirdConfig',
                            ],
                            'editor.' => [
                                'config.' => [
                                    'contentsCss' => 'my.css'
                                ]
                            ],
                            'types.' => [
                                'textmedia.' => [
                                    'classes.' => [
                                        'aClass' => 'aTypeSpecifcConfig',
                                    ],
                                    'editor.' => [
                                        'config.' => [
                                            'contentsCss' => 'your.css'
                                        ]
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            // Config with pagets dots
            'classes.' => [
                'aClass' => 'aTypeSpecifcConfig',
            ],
            'editor.' => [
                'config.' => [
                    'contentsCss' => 'your.css'
                ]
            ],
            // Config without pagets dots
            'classes' => [
                'aClass' => 'aTypeSpecifcConfig',
            ],
            'editor' => [
                'config' => [
                    'contentsCss' => 'your.css'
                ]
            ],
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];
        // Accessible mock to $subject since getRtePageTsConfigOfPid calls BackendUtility::getPagesTSconfig()
        // which can't be mocked in a sane way
        $subject = $this->getAccessibleMock(Richtext::class, ['getRtePageTsConfigOfPid'], [], '', false);
        $subject->expects($this->once())->method('getRtePageTsConfigOfPid')->with(42)->willReturn($pageTsConfig);
        $output = $subject->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig);
        $this->assertSame($expected, $output);
    }

    /**
     * @test
     */
    public function getConfigurationPageTsOverridesPreset()
    {
        $pageId = 42;
        $presetKey = 'default';

        $preset = [
            'editor' => [
                'config' => [
                    'width' => 100
                ],
            ],
        ];

        $pageTsConfigArray = [
            'properties' => [
                'preset' => $presetKey,
                'editor.' => [
                    'config.' => [
                        'width' => 200
                    ],
                ],
            ],
        ];

        $subject = $this->getAccessibleMock(
            Richtext::class,
            ['loadConfigurationFromPreset', 'getRtePageTsConfigOfPid'],
            [],
            '',
            false
            );
        $subject->expects($this->once())->method('loadConfigurationFromPreset')->with($presetKey)->willReturn($preset);
        $subject->expects($this->once())->method('getRtePageTsConfigOfPid')->with($pageId)->willReturn($pageTsConfigArray);

        $output = $subject->getConfiguration('tt_content', 'bodytext', $pageId, 'textmedia', $pageTsConfigArray);

        $expected = [
            'editor' => [
                'config' => [
                    'width' => 200
                ],
            ],
            'preset' => 'default',
            'editor.' => [
                'config.' => [
                    'width' => 200
                ],
            ],
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];

        $this->assertSame($expected, $output);
    }
}
