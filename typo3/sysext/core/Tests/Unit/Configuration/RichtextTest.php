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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\Event\AfterRichtextConfigurationPreparedEvent;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\RteCKEditor\Configuration\CKEditor5Migrator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RichtextTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $eventDispatcher = new class () implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                if ($event instanceof AfterRichtextConfigurationPreparedEvent) {
                    $event->setConfiguration((new CKEditor5Migrator($event->getConfiguration()))->get());
                }
                return $event;
            }
        };
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);
    }

    #[Test]
    public function getConfigurationUsesOverruleModeFromType(): void
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
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
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'classes.' => [
                'aClass' => 'aConfig',
            ],
            'removeComments' => '1',
            'proc.' => [
                'overruleMode' => 'myTransformation',
            ],
            'preset' => 'default',
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
        self::assertSame($expected, $output);
    }

    #[Test]
    public function getConfigurationUsesOverruleModeFromConfig(): void
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
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
                    ],
                ],
            ],
        ];
        $expected = [
            'classes.' => [
                'aClass' => 'aConfig',
            ],
            'removeComments' => '1',
            'proc.' => [
                'overruleMode' => 'myTransformation',
            ],
            'preset' => 'default',
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
        self::assertSame($expected, $output);
    }

    #[Test]
    public function getConfigurationSetsOverruleModeIfMissing(): void
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
            'classes.' => [
                'aClass' => 'aConfig',
            ],
            'default.' => [
                'removeComments' => '1',
            ],
        ];
        $expected = [
            'classes.' => [
                'aClass' => 'aConfig',
            ],
            'removeComments' => '1',
            'preset' => 'default',
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
        self::assertSame($expected, $output);
    }

    #[Test]
    public function getConfigurationOverridesByDefault(): void
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
            'classes.' => [
                'aClass' => 'aConfig',
            ],
            'default.' => [
                'classes.' => [
                    'aClass' => 'anotherConfig',
                ],
                'editor.' => [
                    'config.' => [
                        'contentsCss.' => [
                            '0' => 'my.css',
                        ],
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
                    'contentsCss.' => [
                        '0' => 'my.css',
                    ],
                ],
            ],
            'preset' => 'default',
            'classes' => [
                'aClass' => 'anotherConfig',
            ],
            'editor' => [
                'config' => [
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-start'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-end'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'contentsCss' => [
                        'my.css',
                    ],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
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
        self::assertSame($expected, $output);
    }

    #[Test]
    public function getConfigurationOverridesByFieldSpecificConfig(): void
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
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
                                'contentsCss.' => [
                                    '0' => 'my.css',
                                ],
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
                    'contentsCss.' => [
                        '0' => 'my.css',
                    ],
                ],
            ],
            'preset' => 'default',
            // Config without pagets dots
            'classes' => [
                'aClass' => 'aThirdConfig',
            ],
            'editor' => [
                'config' => [
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-start'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-end'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'contentsCss' => [
                        'my.css',
                    ],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
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
        self::assertSame($expected, $output);
    }

    #[Test]
    public function getConfigurationOverridesByFieldAndTypeSpecificConfig(): void
    {
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
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
                                'contentsCss.' => [
                                    '0' => 'my.css',
                                ],
                            ],
                        ],
                        'types.' => [
                            'textmedia.' => [
                                'classes.' => [
                                    'aClass' => 'aTypeSpecificConfig',
                                ],
                                'editor.' => [
                                    'config.' => [
                                        'contentsCss.' => [
                                            '0' => 'your.css',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            // Config with pagets dots
            'classes.' => [
                'aClass' => 'aTypeSpecificConfig',
            ],
            'editor.' => [
                'config.' => [
                    'contentsCss.' => [
                        '0' => 'your.css',
                    ],
                ],
            ],
            'preset' => 'default',
            // Config without pagets dots
            'classes' => [
                'aClass' => 'aTypeSpecificConfig',
            ],
            'editor' => [
                'config' => [
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-start'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-end'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'contentsCss' => [
                        'your.css',
                    ],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
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
        self::assertSame($expected, $output);
    }

    #[Test]
    public function getConfigurationPageTsOverridesPreset(): void
    {
        $pageId = 42;
        $presetKey = 'default';

        $preset = [
            'editor' => [
                'config' => [
                    'width' => 100,
                ],
            ],
        ];

        $pageTsConfigArray = [
            'preset' => $presetKey,
            'editor.' => [
                'config.' => [
                    'width' => 200,
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
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-start'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-end'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'width' => 200,
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'editor.' => [
                'config.' => [
                    'width' => 200,
                ],
            ],
            'preset' => 'default',
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];

        self::assertSame($expected, $output);
    }

    public static function dataProviderGetConfigurationFindPresetInPageTsOverridesPreset(): array
    {
        return [
            [
                'fieldConfig' => [
                    'type' => 'text',
                    'enableRichtext' => true,
                    'richtextConfiguration' => 'testRteConfigTca',
                ],
                'pageTsConfig' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'default.' => [
                        'preset' => 'testRteConfigTsconfigDefault',
                    ],
                    'config.' => [
                        'aTable.' => [
                            'aField.' => [
                                'preset' => 'testRteConfigTsconfigAField',
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'preset' => 'testRteConfigTsconfigAField',
                    'classes' => [
                        'aClass' => 'aConfig',
                    ],
                    'proc.' => [
                        'overruleMode' => 'default',
                    ],
                ],
                'message' => 'Preset of testRteConfig* in three place TCA',
            ],
            [
                'fieldConfig' => [
                    'type' => 'text',
                    'enableRichtext' => true,
                ],
                'pageTsConfig' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'default.' => [
                        'preset' => 'testRteConfigTsconfigDefault',
                    ],
                    'config.' => [
                        'aTable.' => [
                            'aField.' => [
                                'preset' => 'testRteConfigTsconfigAField',
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'preset' => 'testRteConfigTsconfigAField',
                    'classes' => [
                        'aClass' => 'aConfig',
                    ],
                    'proc.' => [
                        'overruleMode' => 'default',
                    ],
                ],
                'message' => 'Preset of testRteConfig* in two place TCA, lowest is pagetsconfig definition for field of table',

            ],
            [
                'fieldConfig' => [
                    'type' => 'text',
                    'enableRichtext' => true,
                    'richtextConfiguration' => 'testRteConfigTca',
                ],
                'pageTsConfig' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'default.' => [
                        'preset' => 'testRteConfigTsconfigDefault',
                    ],
                ],
                'expected' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'preset' => 'testRteConfigTca',
                    'classes' => [
                        'aClass' => 'aConfig',
                    ],
                    'proc.' => [
                        'overruleMode' => 'default',
                    ],
                ],
                'message' => 'Preset of testRteConfig* in two place TCA, lowest is definition in tca',

            ],
            [
                'fieldConfig' => [
                    'type' => 'text',
                    'enableRichtext' => true,
                    'richtextConfiguration' => 'testRteConfigTca',
                ],
                'pageTsConfig' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                ],
                'expected' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'preset' => 'testRteConfigTca',
                    'classes' => [
                        'aClass' => 'aConfig',
                    ],
                    'proc.' => [
                        'overruleMode' => 'default',
                    ],
                ],
                'message' => 'single Preset of testRteConfig* defined in TCA',

            ],
            [
                'fieldConfig' => [
                    'type' => 'text',
                    'enableRichtext' => true,
                ],
                'pageTsConfig' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'default.' => [
                        'preset' => 'testRteConfigTsconfigDefault',
                    ],
                ],
                'expected' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'preset' => 'testRteConfigTsconfigDefault',
                    'classes' => [
                        'aClass' => 'aConfig',
                    ],
                    'proc.' => [
                        'overruleMode' => 'default',
                    ],
                ],
                'message' => 'single Preset of testRteConfig* defined in PageTSconfig for default of RTE',
            ],
            [
                'fieldConfig' => [
                    'type' => 'text',
                    'enableRichtext' => true,
                    'richtextConfiguration' => 'testRteConfigTca',
                ],
                'pageTsConfig' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'config.' => [
                        'aTable.' => [
                            'aField.' => [
                                'preset' => 'testRteConfigTsconfigAField',
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'preset' => 'testRteConfigTsconfigAField',
                    'classes' => [
                        'aClass' => 'aConfig',
                    ],
                    'proc.' => [
                        'overruleMode' => 'default',
                    ],
                ],
                'message' => 'single Preset of testRteConfig* defined in PageTSconfig for field of table ',
            ],
            [
                'fieldConfig' => [
                    'type' => 'text',
                    'enableRichtext' => true,
                    'richtextConfiguration' => 'testRteConfigTca',
                ],
                'pageTsConfig' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'default.' => [
                        'preset' => 'testRteConfigTsconfigDefault',
                    ],
                    'config.' => [
                        'aTable.' => [
                            'aField.' => [
                                'preset' => 'testRteConfigTsconfigAField',
                                'types.' => [
                                    'textmedia.' => [
                                        'preset' => 'testRteConfigTsconfigATypes',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'preset' => 'testRteConfigTsconfigATypes',
                    'classes' => [
                        'aClass' => 'aConfig',
                    ],
                    'proc.' => [
                        'overruleMode' => 'default',
                    ],
                ],
                'message' => 'Preset of testRteConfigTsconfigA* in four place TCA',
            ],
            [
                'fieldConfig' => [
                    'type' => 'text',
                    'enableRichtext' => true,
                ],
                'pageTsConfig' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'config.' => [
                        'aTable.' => [
                            'aField.' => [
                                'preset' => 'testRteConfigTsconfigAField',
                                'types.' => [
                                    'textmedia.' => [
                                        'preset' => 'testRteConfigTsconfigATypes',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'preset' => 'testRteConfigTsconfigATypes',
                    'classes' => [
                        'aClass' => 'aConfig',
                    ],
                    'proc.' => [
                        'overruleMode' => 'default',
                    ],
                ],
                'message' => 'the preset for CType in pagetsconfig is more reliable than preset for field of tables',
            ],

            [
                'fieldConfig' => [
                    'type' => 'text',
                    'enableRichtext' => true,
                ],
                'pageTsConfig' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'config.' => [
                        'aTable.' => [
                            'aField.' => [
                                'preset' => 'testRteConfigTsconfigAField',
                                'types.' => [
                                    'textmedia.' => [
                                        'preset' => 'testRteConfigTsconfigATypes',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'classes.' => [
                        'aClass' => 'aConfig',
                    ],
                    'preset' => 'testRteConfigTsconfigATypes',
                    'classes' => [
                        'aClass' => 'aConfig',
                    ],
                    'proc.' => [
                        'overruleMode' => 'default',
                    ],
                ],
                'message' => 'the recordtype overrules the definition of an table-field',
            ],

        ];
    }

    #[DataProvider('dataProviderGetConfigurationFindPresetInPageTsOverridesPreset')]
    #[Test]
    public function getConfigurationFindPresetInPageTsOverridesPreset($fieldConfig, $pageTsConfig, $expected, $message): void
    {
        // Accessible mock to $subject since getRtePageTsConfigOfPid calls BackendUtility::getPagesTSconfig()
        // which can't be mocked in a sane way
        $subject = $this->getAccessibleMock(Richtext::class, ['getRtePageTsConfigOfPid'], [], '', false);
        $subject->expects($this->once())->method('getRtePageTsConfigOfPid')->with(42)->willReturn($pageTsConfig);
        $output = $subject->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig);
        self::assertSame($expected, $output);
    }
}
