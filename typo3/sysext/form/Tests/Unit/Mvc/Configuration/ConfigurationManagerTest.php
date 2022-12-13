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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Configuration;

use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManager;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConfigurationManagerTest extends UnitTestCase
{
    /**
     * @var bool
     */
    protected $resetSingletonInstances = true;

    public function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getConfigurationDoesNotEvaluateTypoScriptLookalikeInstructionsFromYamlSettingsInFrontendContext(): void
    {
        $yamlSettings = [
            'prototypes' => [
                'standard' => [
                    'formElementsDefinition' => [
                        'Form' => [
                            'renderingOptions' => [
                                'submitButtonLabel' => 'Foo',
                                'templateVariant' => 'version1',
                                'addQueryString' => [
                                    'value' => 'Baz',
                                    '_typoScriptNodeValue' => 'TEXT',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $formTypoScript = [
            'settings' => [
                'yamlSettingsOverrides' => [
                    'prototypes' => [
                        'standard' => [
                            'formElementsDefinition' => [
                                'Form' => [
                                    'renderingOptions' => [
                                        'submitButtonLabel' => [
                                            'value' => 'Bar',
                                            '_typoScriptNodeValue' => 'TEXT',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $evaluatedFormTypoScript = [
            'prototypes' => [
                'standard' => [
                    'formElementsDefinition' => [
                        'Form' => [
                            'renderingOptions' => [
                                'submitButtonLabel' => 'Bar',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'prototypes' => [
                'standard' => [
                    'formElementsDefinition' => [
                        'Form' => [
                            'renderingOptions' => [
                                'submitButtonLabel' => 'Bar',
                                'templateVariant' => 'version1',
                                'addQueryString' => [
                                    'value' => 'Baz',
                                    '_typoScriptNodeValue' => 'TEXT',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $configurationManagerMock = $this->getAccessibleMock(ConfigurationManager::class, [
            'getTypoScriptSettings',
            'getYamlSettingsFromCache',
        ], [], '', false);
        $configurationManagerMock->method('getYamlSettingsFromCache')->with(self::anything())->willReturn($yamlSettings);

        $environmentServiceProphecy = $this->prophesize(EnvironmentService::class);
        $environmentServiceProphecy->isEnvironmentInFrontendMode()->willReturn(true);

        $typoScriptServiceProphecy = $this->prophesize(TypoScriptService::class);
        $typoScriptServiceProphecy->resolvePossibleTypoScriptConfiguration($formTypoScript['settings']['yamlSettingsOverrides'])->willReturn($evaluatedFormTypoScript);

        $concreteConfigurationManagerProphecy = $this->prophesize(FrontendConfigurationManager::class);
        $concreteConfigurationManagerProphecy->getConfiguration('form', null)->willReturn($formTypoScript);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(TypoScriptService::class)->willReturn($typoScriptServiceProphecy->reveal());

        $configurationManagerMock->_set('objectManager', $objectManager->reveal());
        $configurationManagerMock->_set('concreteConfigurationManager', $concreteConfigurationManagerProphecy->reveal());
        $configurationManagerMock->_set('environmentService', $environmentServiceProphecy->reveal());

        $configurationManagerMock->method('getTypoScriptSettings')->with(self::anything())->willReturn([]);

        self::assertSame($expected, $configurationManagerMock->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form'));
    }
}
