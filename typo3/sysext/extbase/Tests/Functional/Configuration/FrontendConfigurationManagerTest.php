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

namespace TYPO3\CMS\Extbase\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Extbase\Event\Configuration\BeforeFlexFormConfigurationOverrideEvent;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FrontendConfigurationManagerTest extends FunctionalTestCase
{
    #[Test]
    public function beforeFlexFormConfigurationOverrideEventIsDispatched(): void
    {
        $typoScript = [
            'plugin.' => [
                'tx_foo.' => [
                    'settings.' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];
        $flexForm = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.foo">
                    <value index="vDEF">0</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray($typoScript);

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set('foo-flexform-listener', static function (BeforeFlexFormConfigurationOverrideEvent $event) {
            $event->setFlexFormConfiguration([
                'settings' => [
                    'foo' => 'from eventlistener',
                ],
            ]);
        });
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeFlexFormConfigurationOverrideEvent::class, 'foo-flexform-listener');

        $contentObject = new ContentObjectRenderer();
        $contentObject->data = ['pi_flexform' => $flexForm];
        $request = (new ServerRequest())->withAttribute('currentContentObject', $contentObject);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        $configuration = ['extensionName' => 'foo'];
        $subject = $this->get(FrontendConfigurationManager::class);
        self::assertSame('from eventlistener', $subject->getConfiguration($request, $configuration, 'foo')['settings']['foo']);
    }

    public static function overrideConfigurationFromFlexFormSettingsDataProvider(): iterable
    {
        yield 'no flexForm override configuration' => [
            'flexForm' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.booleanField">
                    <value index="vDEF">1</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
            'typoScript' => [
                'plugin.' => [
                    'tx_foo.' => [
                        'settings.' => [
                            'booleanField' => '0',
                        ],
                    ],
                    'tx_foo_foo.' => [
                        'settings.' => [
                            'booleanField' => '0',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'booleanField' => '1',
            ],
        ];

        yield 'flexForm override configuration empty' => [
            'flexForm' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.booleanField">
                    <value index="vDEF">1</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
            'typoScript' => [
                'plugin.' => [
                    'tx_foo.' => [
                        'ignoreFlexFormSettingsIfEmpty' => '',
                        'settings.' => [
                            'booleanField' => '0',
                        ],
                    ],
                    'tx_foo_foo.' => [
                        'settings.' => [
                            'booleanField' => '0',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'booleanField' => '1',
            ],
        ];

        yield 'flexForm override configuration for empty boolean field' => [
            'flexForm' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.booleanField">
                    <value index="vDEF">0</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
            'typoScript' => [
                'plugin.' => [
                    'tx_foo.' => [
                        'ignoreFlexFormSettingsIfEmpty' => 'booleanField',
                        'settings.' => [
                            'booleanField' => '1',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'booleanField' => '1',
            ],
        ];

        yield 'flexForm override configuration for empty boolean field and plugin configuration' => [
            'flexForm' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.booleanField">
                    <value index="vDEF">0</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
            'typoScript' => [
                'plugin.' => [
                    'tx_foo.' => [
                        'settings.' => [
                            'booleanField' => '0',
                        ],
                    ],
                    'tx_foo_foo.' => [
                        'ignoreFlexFormSettingsIfEmpty' => 'booleanField',
                        'settings.' => [
                            'booleanField' => '1',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'booleanField' => '1',
            ],
        ];

        yield 'flexForm override configuration for empty string field' => [
            'flexForm' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.stringField">
                    <value index="vDEF"></value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
            'typoScript' => [
                'plugin.' => [
                    'tx_foo.' => [
                        'ignoreFlexFormSettingsIfEmpty' => 'stringField',
                        'settings.' => [
                            'stringField' => 'default value',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'stringField' => 'default value',
            ],
        ];

        yield 'flexForm override configuration for empty string field and plugin configuration' => [
            'flexForm' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.stringField">
                    <value index="vDEF"></value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
            'typoScript' => [
                'plugin.' => [
                    'tx_foo.' => [
                        'ignoreFlexFormSettingsIfEmpty' => 'stringField',
                        'settings.' => [
                            'stringField' => '',
                        ],
                    ],
                    'tx_foo_foo.' => [
                        'settings.' => [
                            'stringField' => 'default value',
                        ],
                    ],
                ],
            ],
            'expected' => [
                'stringField' => 'default value',
            ],
        ];

        yield 'flexForm override configuration for empty string field in sub array' => [
            'flexForm' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.subarray.stringField">
                    <value index="vDEF"></value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
            'typoScript' => [
                'plugin.' => [
                    'tx_foo.' => [
                        'ignoreFlexFormSettingsIfEmpty' => 'subarray.stringField',
                        'settings.' => [
                            'subarray.' => [
                                'stringField' => 'default value',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'subarray' => [
                    'stringField' => 'default value',
                ],
            ],
        ];

        yield 'flexForm override configuration for empty string field in sub array and plugin configuration' => [
            'flexForm' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.subarray.stringField">
                    <value index="vDEF"></value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>',
            'typoScript' => [
                'plugin.' => [
                    'tx_foo.' => [
                        'settings.' => [
                            'subarray.' => [
                                'stringField' => '',
                            ],
                        ],
                    ],
                    'tx_foo_foo.' => [
                        'ignoreFlexFormSettingsIfEmpty' => 'subarray.stringField',
                        'settings.' => [
                            'subarray.' => [
                                'stringField' => 'default value',
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'subarray' => [
                    'stringField' => 'default value',
                ],
            ],
        ];
    }

    #[DataProvider('overrideConfigurationFromFlexFormSettingsDataProvider')]
    #[Test]
    public function overrideConfigurationFromFlexFormIgnoresConfiguredEmptyFlexFormSettings(
        string $flexForm,
        array $typoScript,
        array $expected
    ): void {
        $contentObject = new ContentObjectRenderer();
        $contentObject->data = ['pi_flexform' => $flexForm];
        $request = (new ServerRequest())->withAttribute('currentContentObject', $contentObject);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray($typoScript);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        $configuration = ['extensionName' => 'foo', 'pluginName' => 'foo'];
        $subject = $this->get(FrontendConfigurationManager::class);
        self::assertSame($expected, $subject->getConfiguration($request, $configuration, 'foo', 'foo')['settings']);
    }
}
