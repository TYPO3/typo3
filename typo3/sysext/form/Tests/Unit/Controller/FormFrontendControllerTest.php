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

namespace TYPO3\CMS\Form\Tests\Unit\Controller;

use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Tests\Unit\Fixtures\EventDispatcher\MockEventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Form\Controller\FormFrontendController;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FormFrontendControllerTest extends UnitTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    public function setUp(): void
    {
        parent::setUp();
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('runtime')->willReturn($cacheFrontendProphecy->reveal());
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
    }

    public function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function overrideByFlexFormSettingsReturnsNoOverriddenConfigurationIfFlexformOverridesDisabled(): void
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy',
        ], [], '', false);

        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        GeneralUtility::setSingletonInstance(ConfigurationService::class, $configurationServiceProphecy->reveal());

        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver',
            ])
        );

        $flexFormTools = new FlexFormTools(new MockEventDispatcher());
        $contentObject = new ContentObjectRenderer();
        $contentObject->data = [
            'pi_flexform' => $flexFormTools->flexArray2Xml([
                'data' => [
                    $sheetIdentifier => [
                        'lDEF' => [
                            'settings.finishers.EmailToReceiver.subject' => [
                                'vDEF' => 'Message Subject overridden',
                            ],
                            'settings.finishers.EmailToReceiver.recipients' => [
                                'el' => [
                                    'abc' => [
                                        '_arrayContainer' => [
                                            'el' => [
                                                'email' => [
                                                    'vDEF' => 'your.company@example.com overridden',
                                                ],
                                                'name' => [
                                                    'vDEF' => '',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'settings.finishers.EmailToReceiver.format' => [
                                'vDEF' => 'html overridden',
                            ],
                        ],
                    ],
                ],
            ]),
        ];

        $frontendConfigurationManager = $this->createMock(FrontendConfigurationManager::class);
        $frontendConfigurationManager
            ->method('getContentObject')
            ->willReturn($contentObject);

        $mockController->_set('configurationManager', $frontendConfigurationManager);

        $configurationServiceProphecy->getPrototypeConfiguration(Argument::cetera())->willReturn([
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'FormEngine' => [
                        'elements' => [
                            'subject' => [],
                            'recipients' => [],
                            'format' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $mockController->_set('settings', [
            'overrideFinishers' => 0,
            'finishers' => [
                'EmailToReceiver' => [
                    'subject' => 'Message Subject overridden',
                    'recipients' => [
                        'your.company@example.com overridden' => '',
                    ],
                    'format' => 'html overridden',
                ],
            ],
        ]);

        $input = [
            'identifier' => 'ext-form-identifier',
            'persistenceIdentifier' => '1:/foo',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject',
                        'recipients' => [
                            'your.company@example.com' => '',
                        ],
                        'format' => 'html',
                    ],
                ],
            ],
        ];

        $expected = [
            'identifier' => 'ext-form-identifier',
            'persistenceIdentifier' => '1:/foo',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject',
                        'recipients' => [
                            'your.company@example.com' => '',
                        ],
                        'format' => 'html',
                    ],
                ],
            ],
        ];

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);
        GeneralUtility::addInstance(FlexFormTools::class, new FlexFormTools($eventDispatcher));

        self::assertSame($expected, $mockController->_call('overrideByFlexFormSettings', $input));
    }

    /**
     * @test
     */
    public function overrideByFlexFormSettingsReturnsOverriddenConfigurationIfFlexformOverridesEnabled(): void
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy',
        ], [], '', false);

        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        GeneralUtility::setSingletonInstance(ConfigurationService::class, $configurationServiceProphecy->reveal());

        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver',
            ])
        );

        $flexFormTools = new FlexFormTools(new MockEventDispatcher());
        $contentObject = new ContentObjectRenderer();
        $contentObject->data = [
            'pi_flexform' => $flexFormTools->flexArray2Xml([
                'data' => [
                    $sheetIdentifier => [
                        'lDEF' => [
                            'settings.finishers.EmailToReceiver.subject' => [
                                'vDEF' => 'Message Subject overridden',
                            ],
                            'settings.finishers.EmailToReceiver.recipients' => [
                                'el' => [
                                    'abc' => [
                                        '_arrayContainer' => [
                                            'el' => [
                                                'email' => [
                                                    'vDEF' => 'your.company@example.com overridden',
                                                ],
                                                'name' => [
                                                    'vDEF' => '',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'settings.finishers.EmailToReceiver.format' => [
                                'vDEF' => 'html overridden',
                            ],
                        ],
                    ],
                ],
            ]),
        ];

        $frontendConfigurationManager = $this->createMock(FrontendConfigurationManager::class);
        $frontendConfigurationManager
            ->method('getContentObject')
            ->willReturn($contentObject);

        $mockController->_set('configurationManager', $frontendConfigurationManager);

        $configurationServiceProphecy->getPrototypeConfiguration(Argument::cetera())->willReturn([
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'FormEngine' => [
                        'elements' => [
                            'subject' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                            'recipients' => [
                                'type' => 'array',
                                'section' => true,
                                'sectionItemKey' => 'email',
                                'sectionItemValue' => 'name',
                            ],
                            'format' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $mockController->_set('settings', [
            'overrideFinishers' => 1,
            'finishers' => [
                'EmailToReceiver' => [
                    'subject' => 'Message Subject overridden',
                    'recipients' => [
                        'abcxyz' => [
                            'email' => 'your.company@example.com overridden',
                            'name' => '',
                        ],
                    ],
                    'format' => 'html overridden',
                ],
            ],
        ]);

        $input = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject',
                        'recipients' => [
                            'your.company@example.com' => '',
                        ],
                        'format' => 'html',
                    ],
                ],
            ],
        ];

        $expected = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject overridden',
                        'recipients' => [
                            'your.company@example.com overridden' => '',
                        ],
                        'format' => 'html overridden',
                        'translation' => [
                            'propertiesExcludedFromTranslation' => [
                                'subject',
                                'recipients',
                                'format',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);
        GeneralUtility::addInstance(FlexFormTools::class, new FlexFormTools($eventDispatcher));

        self::assertSame($expected, $mockController->_call('overrideByFlexFormSettings', $input));
    }

    /**
     * @test
     */
    public function overrideByFlexFormSettingsReturnsNotOverriddenConfigurationKeyIfFlexformOverridesAreNotRepresentedInFormEngineConfiguration(): void
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy',
        ], [], '', false);

        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        GeneralUtility::setSingletonInstance(ConfigurationService::class, $configurationServiceProphecy->reveal());

        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver',
            ])
        );

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);
        GeneralUtility::addInstance(FlexFormTools::class, new FlexFormTools($eventDispatcher));

        $flexFormTools = new FlexFormTools(new MockEventDispatcher());
        $contentObject = new ContentObjectRenderer();
        $contentObject->data = [
            'pi_flexform' => $flexFormTools->flexArray2Xml([
                'data' => [
                    $sheetIdentifier => [
                        'lDEF' => [
                            'settings.finishers.EmailToReceiver.subject' => [
                                'vDEF' => 'Message Subject overridden',
                            ],
                            'settings.finishers.EmailToReceiver.recipients' => [
                                'el' => [
                                    'abc' => [
                                        '_arrayContainer' => [
                                            'el' => [
                                                'email' => [
                                                    'vDEF' => 'your.company@example.com overridden',
                                                ],
                                                'name' => [
                                                    'vDEF' => '',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'settings.finishers.EmailToReceiver.format' => [
                                'vDEF' => 'html overridden',
                            ],
                        ],
                    ],
                ],
            ]),
        ];

        $frontendConfigurationManager = $this->createMock(FrontendConfigurationManager::class);
        $frontendConfigurationManager
            ->method('getContentObject')
            ->willReturn($contentObject);

        $mockController->_set('configurationManager', $frontendConfigurationManager);

        $configurationServiceProphecy->getPrototypeConfiguration(Argument::cetera())->willReturn([
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'FormEngine' => [
                        'elements' => [
                            'subject' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                            'recipients' => [
                                'type' => 'array',
                                'section' => true,
                                'sectionItemKey' => 'email',
                                'sectionItemValue' => 'name',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $mockController->_set('settings', [
            'overrideFinishers' => 1,
            'finishers' => [
                'EmailToReceiver' => [
                    'subject' => 'Message Subject overridden',
                    'recipients' => [
                        'abcxyz' => [
                            'email' => 'your.company@example.com overridden',
                            'name' => '',
                        ],
                    ],
                    'format' => 'html overridden',
                ],
            ],
        ]);

        $input = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject',
                        'recipients' => [
                            'your.company@example.com' => '',
                        ],
                        'format' => 'html',
                    ],
                ],
            ],
        ];

        $expected = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject overridden',
                        'recipients' => [
                            'your.company@example.com overridden' => '',
                        ],
                        'format' => 'html',
                        'translation' => [
                            'propertiesExcludedFromTranslation' => [
                                'subject',
                                'recipients',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $mockController->_call('overrideByFlexFormSettings', $input));
    }
}
