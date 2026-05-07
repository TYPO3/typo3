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

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManager;
use TYPO3\CMS\Form\Mvc\Configuration\FormYamlCollector;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConfigurationManagerTest extends FunctionalTestCase
{
    #[Test]
    #[IgnoreDeprecations]
    public function getYamlConfigurationTriggersDeprecationForLegacyTypoScriptYamlConfigurations(): void
    {
        $this->expectUserDeprecationMessage(
            'TypoScript-based registration of form YAML files via plugin.tx_form.settings.yamlConfigurations'
            . ' or module.tx_form.settings.yamlConfigurations has been deprecated in TYPO3 v14.2 and will'
            . ' be removed in TYPO3 v15.0. Use the auto-discovery directory convention'
            . ' EXT:my_extension/Configuration/Form/<SetName>/config.yaml instead.'
        );
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheMock->method('has')->willReturn(true);
        $cacheMock->method('get')->willReturn([]);
        $configurationManager = new ConfigurationManager(
            $this->createMock(YamlSource::class),
            $cacheMock,
            $this->createMock(TypoScriptService::class),
            new FormYamlCollector(),
        );
        $configurationManager->getYamlConfiguration(
            ['yamlConfigurations' => [10 => 'EXT:my_extension/Configuration/Yaml/MySetup.yaml']],
            false
        );
    }

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
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheMock->method('has')->willReturn(true);
        $cacheMock->method('get')->willReturn($yamlSettings);
        $configurationManagerMock = new ConfigurationManager(
            $this->createMock(YamlSource::class),
            $cacheMock,
            $this->createMock(TypoScriptService::class),
            new FormYamlCollector(),
        );
        $result = $configurationManagerMock->getYamlConfiguration([], true);
        self::assertSame($expected, $result);
    }
}
