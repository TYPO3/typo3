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

namespace TYPO3\CMS\Core\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Configuration\Event\AfterRichtextConfigurationPreparedEvent;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\TypoScript\AST\Node\AbstractNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\PageTsConfig;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RichtextTest extends FunctionalTestCase
{
    #[Test]
    public function afterRichtextConfigurationPreparedEventIsCalled(): void
    {
        $afterRichtextConfigurationPreparedEvent = null;
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
            'editor' => [
                'config' => [
                    'debug' => true,
                ],
            ],
        ];

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-richtext-configuration-prepared-listener',
            static function (AfterRichtextConfigurationPreparedEvent $event) use (&$afterRichtextConfigurationPreparedEvent, $expected): void {
                $afterRichtextConfigurationPreparedEvent = $event;
                $event->setConfiguration($expected);
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterRichtextConfigurationPreparedEvent::class, 'after-richtext-configuration-prepared-listener');

        $this->primeRtePageTsConfigCache(42, $pageTsConfig);
        $output = $this->get(Richtext::class)->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig);

        self::assertInstanceOf(AfterRichtextConfigurationPreparedEvent::class, $afterRichtextConfigurationPreparedEvent);
        self::assertEquals($expected, $output);
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
        $this->primeRtePageTsConfigCache(42, $pageTsConfig);
        self::assertSame($expected, $this->get(Richtext::class)->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig));
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
        $this->primeRtePageTsConfigCache(42, $pageTsConfig);
        self::assertSame($expected, $this->get(Richtext::class)->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig));
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
        $this->primeRtePageTsConfigCache(42, $pageTsConfig);
        self::assertSame($expected, $this->get(Richtext::class)->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig));
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
                    'contentsCss' => [
                        '0' => 'my.css',
                    ],
                ],
            ],
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];
        $this->primeRtePageTsConfigCache(42, $pageTsConfig);
        self::assertSame($expected, $this->get(Richtext::class)->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig));
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
            'classes' => [
                'aClass' => 'aThirdConfig',
            ],
            'editor' => [
                'config' => [
                    'contentsCss' => [
                        '0' => 'my.css',
                    ],
                ],
            ],
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];
        $this->primeRtePageTsConfigCache(42, $pageTsConfig);
        self::assertSame($expected, $this->get(Richtext::class)->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig));
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
            'classes' => [
                'aClass' => 'aTypeSpecificConfig',
            ],
            'editor' => [
                'config' => [
                    'contentsCss' => [
                        '0' => 'your.css',
                    ],
                ],
            ],
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];
        $this->primeRtePageTsConfigCache(42, $pageTsConfig);
        self::assertSame($expected, $this->get(Richtext::class)->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig));
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
                    'width' => '200',
                ],
            ],
        ];
        $this->primeRtePageTsConfigCache($pageId, $pageTsConfigArray);
        $subject = $this->getAccessibleMock(
            Richtext::class,
            ['loadConfigurationFromPreset'],
            [
                $this->get(EventDispatcherInterface::class),
                $this->get('cache.runtime'),
                $this->get(YamlFileLoader::class),
                $this->get(TypoScriptService::class),
            ]
        );
        $subject->expects($this->once())->method('loadConfigurationFromPreset')->with($presetKey)->willReturn($preset);
        $expected = [
            'editor' => [
                'config' => [
                    'width' => '200',
                ],
            ],
            'editor.' => [
                'config.' => [
                    'width' => '200',
                ],
            ],
            'preset' => 'default',
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];
        self::assertSame($expected, $subject->getConfiguration('tt_content', 'bodytext', $pageId, 'textmedia', $pageTsConfigArray));
    }

    public static function dataProviderGetConfigurationFindPresetInPageTsOverridesPreset(): array
    {
        return [
            'Preset of testRteConfig* in three place TCA' => [
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
            ],
            'Preset of testRteConfig* in two place TCA, lowest is pagetsconfig definition for field of table' => [
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
            ],
            'Preset of testRteConfig* in two place TCA, lowest is definition in tca' => [
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
            ],
            'single Preset of testRteConfig* defined in TCA' => [
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
            ],
            'single Preset of testRteConfig* defined in PageTSconfig for default of RTE' => [
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
            ],
            'single Preset of testRteConfig* defined in PageTSconfig for field of table' => [
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
            ],
            'Preset of testRteConfigTsconfigA* in four place TCA' => [
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
            ],
            'the preset for CType in pagetsconfig is more reliable than preset for field of tables' => [
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
            ],
            'the recordtype overrules the definition of an table-field' => [
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
            ],
        ];
    }

    #[DataProvider('dataProviderGetConfigurationFindPresetInPageTsOverridesPreset')]
    #[Test]
    public function getConfigurationFindPresetInPageTsOverridesPreset($fieldConfig, $pageTsConfig, $expected): void
    {
        $this->primeRtePageTsConfigCache(42, $pageTsConfig);
        self::assertSame($expected, $this->get(Richtext::class)->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig));
    }

    /**
     * Shortcut BackendUtility::getPagesTSconfig() by writing a pre-built PageTsConfig
     * object directly into the runtime cache it consults on first access.
     * This avoids mocking getRtePageTsConfigOfPid() and allows using real Richtext instances.
     */
    private function primeRtePageTsConfigCache(int $pid, array $rtePageTsConfig): void
    {
        $rteNode = new ChildNode('RTE');
        $this->addTsConfigChildren($rteNode, $rtePageTsConfig);
        $rootNode = new RootNode();
        $rootNode->addChild($rteNode);
        $runtimeCache = $this->get('cache.runtime');
        $runtimeCache->set('pageTsConfig-pid-to-hash-' . $pid, 'primed-hash-' . $pid);
        $runtimeCache->set('pageTsConfig-hash-to-object-primed-hash-' . $pid, new PageTsConfig($rootNode, []));
    }

    private function addTsConfigChildren(AbstractNode $parent, array $tsConfigArray): void
    {
        foreach ($tsConfigArray as $key => $value) {
            if (str_ends_with((string)$key, '.')) {
                $child = new ChildNode(substr((string)$key, 0, -1));
                $this->addTsConfigChildren($child, $value);
            } else {
                $child = new ChildNode((string)$key);
                $child->setValue((string)$value);
            }
            $parent->addChild($child);
        }
    }
}
