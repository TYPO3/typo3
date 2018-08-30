<?php
namespace TYPO3\CMS\Fluid\Tests\Functional\View;

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

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TemplatesPathsTest extends FunctionalTestCase
{
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
                                    'Template' => [
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

    public function setUp()
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
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

        $this->assertContains($expectedTemplate, $content);
        $this->assertContains($expectedPartial, $content);
        $this->assertContains($expectedLayout, $content);
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

        $this->assertContains($expectedTemplate, $content);
        $this->assertContains($expectedPartial, $content);
        $this->assertContains($expectedLayout, $content);
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

        $this->assertContains($expectedTemplate, $content);
        $this->assertContains($expectedPartial, $content);
        $this->assertContains($expectedLayout, $content);
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

        $this->assertContains($expectedTemplate, $content);
        $this->assertContains($expectedPartial, $content);
        $this->assertContains($expectedLayout, $content);
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

        $this->assertContains('PAGINATE WIDGET', $content);
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

        $this->assertContains('PAGINATE WIDGET', $content);
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

        $this->assertContains('Base Template', $content);
        $this->assertContains('Default Layout', $content);
        $this->assertContains('Default Partial', $content);
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

        $this->assertContains('Base Template', $content);
        $this->assertContains('Override Template', $content);
    }

    /**
     * @param array $requestArguments
     * @return string
     */
    protected function fetchFrontendResponseBody(array $requestArguments): string
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest())->withQueryParameters($requestArguments)
        );

        return (string)$response->getBody();
    }
}
