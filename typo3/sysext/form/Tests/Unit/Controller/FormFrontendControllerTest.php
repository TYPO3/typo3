<?php

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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Controller\FormFrontendController;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FormFrontendControllerTest extends UnitTestCase
{
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
    public function overrideByFlexFormSettingsReturnsNoOverriddenConfigurationIfFlexformOverridesDisabled()
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy'
        ], [], '', false);

        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(Arguments::class)->willReturn(new Arguments());
        $objectManager->get(ConfigurationService::class)->willReturn($configurationServiceProphecy->reveal());

        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver'
            ])
        );

        $flexFormTools = new FlexFormTools();
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
            ->expects(self::any())
            ->method('getContentObject')
            ->willReturn($contentObject);

        $mockController->_set('configurationManager', $frontendConfigurationManager);
        $mockController->injectObjectManager($objectManager->reveal());

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

        self::assertSame($expected, $mockController->_call('overrideByFlexFormSettings', $input));
    }

    /**
     * @test
     */
    public function overrideByFlexFormSettingsReturnsOverriddenConfigurationIfFlexformOverridesEnabled()
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy'
        ], [], '', false);

        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(Arguments::class)->willReturn(new Arguments());
        $objectManager->get(ConfigurationService::class)->willReturn($configurationServiceProphecy->reveal());

        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver'
            ])
        );

        $flexFormTools = new FlexFormTools();
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
            ->expects(self::any())
            ->method('getContentObject')
            ->willReturn($contentObject);

        $mockController->_set('configurationManager', $frontendConfigurationManager);
        $mockController->injectObjectManager($objectManager->reveal());

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
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $mockController->_call('overrideByFlexFormSettings', $input));
    }

    /**
     * @test
     */
    public function overrideByFlexFormSettingsReturnsNotOverriddenConfigurationKeyIfFlexformOverridesAreNotRepresentedInFormEngineConfiguration()
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy'
        ], [], '', false);

        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(Arguments::class)->willReturn(new Arguments());
        $objectManager->get(ConfigurationService::class)->willReturn($configurationServiceProphecy->reveal());

        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver'
            ])
        );

        $flexFormTools = new FlexFormTools();
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
            ->expects(self::any())
            ->method('getContentObject')
            ->willReturn($contentObject);

        $mockController->_set('configurationManager', $frontendConfigurationManager);
        $mockController->injectObjectManager($objectManager->reveal());

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
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $mockController->_call('overrideByFlexFormSettings', $input));
    }

    /**
     * @test
     */
    public function overrideByTypoScriptSettingsReturnsNotOverriddenConfigurationIfNoTypoScriptOverridesExists()
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy'
        ], [], '', false);

        $typoScriptServiceProphecy = $this->prophesize(TypoScriptService::class);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(Arguments::class)->willReturn(new Arguments());
        $objectManager->get(TypoScriptService::class)->willReturn($typoScriptServiceProphecy->reveal());

        $mockController->injectObjectManager($objectManager->reveal());

        $typoScriptServiceProphecy
            ->resolvePossibleTypoScriptConfiguration(Argument::cetera())
            ->willReturnArgument(0);

        $mockController->_set('settings', [
            'formDefinitionOverrides' => [
            ],
        ]);

        $input = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => 'Label',
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Label',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => 'Label',
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Label',
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $mockController->_call('overrideByTypoScriptSettings', $input));
    }

    /**
     * @test
     */
    public function overrideByTypoScriptSettingsReturnsOverriddenConfigurationIfTypoScriptOverridesExists()
    {
        $mockController = $this->getAccessibleMock(FormFrontendController::class, [
            'dummy'
        ], [], '', false);

        $typoScriptServiceProphecy = $this->prophesize(TypoScriptService::class);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(Arguments::class)->willReturn(new Arguments());
        $objectManager->get(TypoScriptService::class)->willReturn($typoScriptServiceProphecy->reveal());

        $mockController->injectObjectManager($objectManager->reveal());

        $typoScriptServiceProphecy
            ->resolvePossibleTypoScriptConfiguration(Argument::cetera())
            ->willReturnArgument(0);

        $mockController->_set('settings', [
            'formDefinitionOverrides' => [
                'ext-form-identifier' => [
                    'label' => 'Label override',
                    'renderables' => [
                        0 => [
                            'renderables' => [
                                0 => [
                                    'label' => 'Label override',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $input = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => 'Label',
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Label',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'label' => 'Label override',
            'renderables' => [
                0 => [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Label',
                    'renderables' => [
                        0 => [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Label override',
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $mockController->_call('overrideByTypoScriptSettings', $input));
    }
}
