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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManager;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConfigurationManagerTest extends FunctionalTestCase
{
    #[Test]
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
        $expected = [
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
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $serverRequest = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $configurationManagerMock = $this->getAccessibleMock(
            ConfigurationManager::class,
            [
                'getTypoScriptSettings',
                'getYamlSettingsFromCache',
            ],
            [
                $this->get(FrontendConfigurationManager::class),
                $this->get(BackendConfigurationManager::class),
            ]
        );
        $configurationManagerMock->method('getYamlSettingsFromCache')->with(self::anything())->willReturn($yamlSettings);
        $configurationManagerMock->method('getTypoScriptSettings')->with(self::anything())->willReturn([]);
        $configurationManagerMock->setRequest($serverRequest);
        $result = $configurationManagerMock->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form');
        self::assertSame($expected, $result);
    }
}
