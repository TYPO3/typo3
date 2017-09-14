<?php
namespace TYPO3\Fluid\Tests\Functional\View;

use PHPUnit\Util\PHP\AbstractPhpProcess;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Response;
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

        $content = $this->fetchFrontendResponse($requestArguments)->getContent();

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

        $content = $this->fetchFrontendResponse($requestArguments)->getContent();

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

        $content = $this->fetchFrontendResponse($requestArguments)->getContent();

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

        $content = $this->fetchFrontendResponse($requestArguments)->getContent();

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

        $content = $this->fetchFrontendResponse($requestArguments)->getContent();

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

        $content = $this->fetchFrontendResponse($requestArguments)->getContent();

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

        $content = $this->fetchFrontendResponse($requestArguments)->getContent();

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

        $content = $this->fetchFrontendResponse($requestArguments)->getContent();

        $this->assertContains('Base Template', $content);
        $this->assertContains('Override Template', $content);
    }

    /**
     * @param array $requestArguments
     * @param bool $failOnFailure
     * @return Response
     */
    protected function fetchFrontendResponse(array $requestArguments, $failOnFailure = true)
    {
        $arguments = [
            'documentRoot' => $this->instancePath,
            'requestUrl' => 'http://localhost' . '/?' . GeneralUtility::implodeArrayForUrl('', $requestArguments),
        ];

        $template = new \Text_Template(TYPO3_PATH_PACKAGES . 'typo3/testing-framework/Resources/Core/Functional/Fixtures/Frontend/request.tpl');
        $template->setVar(
            [
                'arguments' => var_export($arguments, true),
                'originalRoot' => ORIGINAL_ROOT,
                'vendorPath' => TYPO3_PATH_PACKAGES,
            ]
        );

        $php = AbstractPhpProcess::factory();
        $response = $php->runJob($template->render());
        $result = json_decode($response['stdout'], true);

        if ($result === null) {
            $this->fail('Frontend Response is empty');
        }

        if ($failOnFailure && $result['status'] === Response::STATUS_Failure) {
            $this->fail('Frontend Response has failure:' . LF . $result['error']);
        }

        $response = new Response($result['status'], $result['content'], $result['error']);

        return $response;
    }
}
