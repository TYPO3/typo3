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
class RichtextTest extends \TYPO3\Components\TestingFramework\Core\UnitTestCase
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
            'classes.' => [
                'aClass.' => 'aConfig',
            ],
            'properties' => [
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
            'removeComments' => '1',
            'proc.' => [
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
            'classes.' => [
                'aClass.' => 'aConfig',
            ],
            'properties' => [
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
            'removeComments' => '1',
            'proc.' => [
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
            'classes.' => [
                'aClass.' => 'aConfig',
            ],
            'properties' => [
                'default.' => [
                    'removeComments' => '1',
                ],
            ]
        ];
        $expected = [
            'removeComments' => '1',
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
            'classes.' => [
                'aClass.' => 'aConfig',
            ],
            'properties' => [
                'default.' => [
                    'proc.' => [
                        'overruleMode' => 'ts_css',
                    ],
                ],
            ],
        ];
        $expected = [
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
}
