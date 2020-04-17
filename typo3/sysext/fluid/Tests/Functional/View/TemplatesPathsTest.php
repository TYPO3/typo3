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

namespace TYPO3\CMS\Fluid\Tests\Functional\View;

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\FluidTest\Controller\TemplateController;

class TemplatesPathsTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var array
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/fluid_test',
    ];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = [
        'fluid',
    ];

    /**
     * @var array
     */
    protected $configurationToUseInTestInstance = [
        'EXTCONF' => [
            'extbase' => [
                'extensions' => [
                    'FluidTest' => [
                        'plugins' => [
                            'Pi' => [
                                'controllers' => [
                                    TemplateController::class => [
                                        'className' => TemplateController::class,
                                        'alias' => 'Template',
                                        'actions' => [
                                            'baseTemplate',
                                        ],
                                        'nonCacheableActions' => [
                                            'baseTemplate',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
            [
                $this->buildErrorHandlingConfiguration('Fluid', [404])
            ]
        );
        $this->setUpFrontendRootPage(1, ['EXT:fluid_test/Configuration/TypoScript/Basic.ts']);
    }

    /**
     * @return array
     */
    public function differentOverrideScenariosDataProvider(): array
    {
        return [
            'base' => [
                'base',
                'Base Template',
                'Base Partial',
                'Base Layout',
            ],
            'overrideAll' => [
                'overrideAll',
                'Override Template',
                'Override Partial',
                'Override Layout',
            ],
            'templateOverride' => [
                'templateOverride',
                'TemplateOverride',
                'Base Partial',
                'Base Layout',
            ],
            'templateOverrideManual' => [
                'templateOverrideManual',
                'TemplateOverride',
                'Base Partial',
                'Base Layout',
            ],
            'partialOverride' => [
                'partialOverride',
                'Base Template',
                'PartialOverride',
                'Base Layout',
            ],
            'partialOverrideManual' => [
                'partialOverrideManual',
                'Base Template',
                'PartialOverride',
                'Base Layout',
            ],
            'layoutOverride' => [
                'layoutOverride',
                'Base Template',
                'Base Partial',
                'LayoutOverride',
            ],
            'layoutOverrideManual' => [
                'layoutOverrideManual',
                'Base Template',
                'Base Partial',
                'LayoutOverride',
            ],
        ];
    }

    /**
     * @test
     * @param string $overrideType
     * @param string $expectedTemplate
     * @param string $expectedPartial
     * @param string $expectedLayout
     * @dataProvider differentOverrideScenariosDataProvider
     */
    public function baseRenderingWorksForCObject($overrideType, $expectedTemplate, $expectedPartial, $expectedLayout)
    {
        $requestArguments = [
            'id' => '1',
            'override' => $overrideType,
            'mode' => 'fluidTemplate',
        ];

        $content = $this->fetchFrontendResponseBody($requestArguments);

        self::assertStringContainsString($expectedTemplate, $content);
        self::assertStringContainsString($expectedPartial, $content);
        self::assertStringContainsString($expectedLayout, $content);
    }

    /**
     * @test
     * @param string $overrideType
     * @param string $expectedTemplate
     * @param string $expectedPartial
     * @param string $expectedLayout
     * @dataProvider differentOverrideScenariosDataProvider
     */
    public function baseRenderingWorksForControllerAsGlobalUsage($overrideType, $expectedTemplate, $expectedPartial, $expectedLayout)
    {
        $requestArguments = [
            'id' => '1',
            'override' => $overrideType,
            'mode' => 'controller',
        ];

        $content = $this->fetchFrontendResponseBody($requestArguments);

        self::assertStringContainsString($expectedTemplate, $content);
        self::assertStringContainsString($expectedPartial, $content);
        self::assertStringContainsString($expectedLayout, $content);
    }

    /**
     * @test
     * @param string $overrideType
     * @param string $expectedTemplate
     * @param string $expectedPartial
     * @param string $expectedLayout
     * @dataProvider differentOverrideScenariosDataProvider
     */
    public function baseRenderingWorksForControllerAsPluginUsage($overrideType, $expectedTemplate, $expectedPartial, $expectedLayout)
    {
        $requestArguments = [
            'id' => '1',
            'override' => $overrideType,
            'mode' => 'plugin',
            'pluginConfig' => 'extensionKey',
        ];

        $content = $this->fetchFrontendResponseBody($requestArguments);

        self::assertStringContainsString($expectedTemplate, $content);
        self::assertStringContainsString($expectedPartial, $content);
        self::assertStringContainsString($expectedLayout, $content);
    }

    /**
     * @test
     * @param string $overrideType
     * @param string $expectedTemplate
     * @param string $expectedPartial
     * @param string $expectedLayout
     * @dataProvider differentOverrideScenariosDataProvider
     */
    public function baseRenderingWorksForControllerAsPluginUsageWithPluginConfig($overrideType, $expectedTemplate, $expectedPartial, $expectedLayout)
    {
        $requestArguments = [
            'id' => '1',
            'override' => $overrideType,
            'mode' => 'plugin',
            'pluginConfig' => 'pluginName',
        ];

        $content = $this->fetchFrontendResponseBody($requestArguments);

        self::assertStringContainsString($expectedTemplate, $content);
        self::assertStringContainsString($expectedPartial, $content);
        self::assertStringContainsString($expectedLayout, $content);
    }

    /**
     * @test
     */
    public function widgetTemplateCanBeOverridden()
    {
        $requestArguments = [
            'id' => '1',
            'override' => 'base',
            'mode' => 'controller',
            'widgetConfig' => 'new',
        ];

        $content = $this->fetchFrontendResponseBody($requestArguments);

        self::assertStringContainsString('PAGINATE WIDGET', $content);
    }

    /**
     * @test
     */
    public function widgetTemplateCanBeOverriddenWithLegacyConfig()
    {
        $requestArguments = [
            'id' => '1',
            'override' => 'base',
            'mode' => 'controller',
            'widgetConfig' => 'old',
        ];

        $content = $this->fetchFrontendResponseBody($requestArguments);

        self::assertStringContainsString('PAGINATE WIDGET', $content);
    }

    /**
     * @test
     */
    public function baseRenderingWorksForControllerAsPluginUsageWithIncompleteConfig()
    {
        $requestArguments = [
            'id' => '1',
            'override' => 'base',
            'mode' => 'plugin',
            'pluginConfig' => 'incomplete',
        ];

        $content = $this->fetchFrontendResponseBody($requestArguments);

        self::assertStringContainsString('Base Template', $content);
        self::assertStringContainsString('Default Layout', $content);
        self::assertStringContainsString('Default Partial', $content);
    }

    /**
     * @test
     */
    public function baseRenderingWorksForControllerWithTwoPlugins()
    {
        $requestArguments = [
            'id' => '1',
            'mode' => '2plugins',
        ];

        $content = $this->fetchFrontendResponseBody($requestArguments);

        self::assertStringContainsString('Base Template', $content);
        self::assertStringContainsString('Override Template', $content);
    }

    /**
     * @param array $requestArguments
     * @return string
     */
    protected function fetchFrontendResponseBody(array $requestArguments): string
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest('https://website.local/en/'))->withQueryParameters($requestArguments)
        );

        return (string)$response->getBody();
    }
}
