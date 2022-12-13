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

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManager;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConfigurationManagerTest extends UnitTestCase
{
    use ProphecyTrait;

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

        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $GLOBALS['TYPO3_REQUEST'] = $serverRequestProphecy->reveal();

        $typoScriptServiceProphecy = $this->prophesize(TypoScriptService::class);
        $typoScriptServiceProphecy->resolvePossibleTypoScriptConfiguration($formTypoScript['settings']['yamlSettingsOverrides'])->willReturn($evaluatedFormTypoScript);
        GeneralUtility::addInstance(TypoScriptService::class, $typoScriptServiceProphecy->reveal());

        $concreteConfigurationManagerProphecy = $this->prophesize(FrontendConfigurationManager::class);
        $concreteConfigurationManagerProphecy->getConfiguration('form', null)->willReturn($formTypoScript);

        $configurationManagerMock->_set('concreteConfigurationManager', $concreteConfigurationManagerProphecy->reveal());
        $configurationManagerMock->method('getTypoScriptSettings')->with(self::anything())->willReturn([]);

        self::assertSame($expected, $configurationManagerMock->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form'));
    }
}
