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

namespace TYPO3\CMS\Extbase\Tests\Unit\Configuration;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FrontendConfigurationManagerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected ContentObjectRenderer&MockObject $mockContentObject;

    protected FrontendConfigurationManager&MockObject&AccessibleObjectInterface $frontendConfigurationManager;

    protected TypoScriptService&MockObject $mockTypoScriptService;

    protected array $testTypoScriptSetup = [
        'foo.' => [
            'bar' => 'baz',
        ],
        'config.' => [
            'tx_extbase.' => [
                'settings.' => [
                    'setting1' => 'value1',
                    'setting2' => 'value2',
                ],
                'view.' => [
                    'viewSub.' => [
                        'key1' => 'value1',
                        'key2' => 'value2',
                    ],
                ],
            ],
        ],
    ];

    protected array $testTypoScriptSetupConverted = [
        'foo' => [
            'bar' => 'baz',
        ],
        'config' => [
            'tx_extbase' => [
                'settings' => [
                    'setting1' => 'value1',
                    'setting2' => 'value2',
                ],
                'view' => [
                    'viewSub' => [
                        'key1' => 'value1',
                        'key2' => 'value2',
                    ],
                ],
            ],
        ],
    ];

    protected array $testPluginConfiguration = [
        'settings' => [
            'setting1' => 'overriddenValue1',
            'setting3' => 'additionalValue',
        ],
        'view' => [
            'viewSub' => [
                'key1' => 'overridden',
                'key3' => 'new key',
            ],
        ],
        'persistence' => [
            'storagePid' => '123',
        ],
    ];

    /**
     * Sets up this testcase
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockContentObject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['getTreeList'])
            ->getMock();
        $this->frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            ['dummy'],
            [],
            '',
            false
        );
        $this->frontendConfigurationManager->_set('contentObject', $this->mockContentObject);
        $this->mockTypoScriptService = $this->getMockBuilder(TypoScriptService::class)->getMock();
        $this->frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
    }

    /**
     * @test
     */
    public function setConfigurationResetsConfigurationCache(): void
    {
        $this->frontendConfigurationManager->_set('configurationCache', ['foo' => 'bar']);
        $this->frontendConfigurationManager->setConfiguration([]);
        self::assertEquals([], $this->frontendConfigurationManager->_get('configurationCache'));
    }

    /**
     * @test
     */
    public function setConfigurationSetsExtensionAndPluginName(): void
    {
        $configuration = [
            'extensionName' => 'SomeExtensionName',
            'pluginName' => 'SomePluginName',
        ];
        $this->frontendConfigurationManager->setConfiguration($configuration);
        self::assertEquals('SomeExtensionName', $this->frontendConfigurationManager->_get('extensionName'));
        self::assertEquals('SomePluginName', $this->frontendConfigurationManager->_get('pluginName'));
    }

    /**
     * @test
     */
    public function setConfigurationConvertsTypoScriptArrayToPlainArray(): void
    {
        $configuration = [
            'foo' => 'bar',
            'settings.' => ['foo' => 'bar'],
            'view.' => ['subkey.' => ['subsubkey' => 'subsubvalue']],
        ];
        $expectedResult = [
            'foo' => 'bar',
            'settings' => ['foo' => 'bar'],
            'view' => ['subkey' => ['subsubkey' => 'subsubvalue']],
        ];
        $this->mockTypoScriptService->expects(self::atLeastOnce())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->willReturn($expectedResult);
        $this->frontendConfigurationManager->setConfiguration($configuration);
        self::assertEquals($expectedResult, $this->frontendConfigurationManager->_get('configuration'));
    }

    /**
     * @test
     */
    public function getConfigurationReturnsCachedResultOfCurrentPlugin(): void
    {
        $this->frontendConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $this->frontendConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $this->frontendConfigurationManager->_set('configurationCache', [
            'currentextensionname_currentpluginname' => ['foo' => 'bar'],
            'someotherextension_somepluginname' => ['baz' => 'shouldnotbereturned'],
        ]);
        $expectedResult = ['foo' => 'bar'];
        $actualResult = $this->frontendConfigurationManager->getConfiguration();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationReturnsCachedResultForGivenExtension(): void
    {
        $this->frontendConfigurationManager->_set('configurationCache', [
            'someextensionname_somepluginname' => ['foo' => 'bar'],
            'someotherextension_somepluginname' => ['baz' => 'shouldnotbereturned'],
        ]);
        $expectedResult = ['foo' => 'bar'];
        $actualResult = $this->frontendConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRecursivelyMergesCurrentPluginConfigurationWithFrameworkConfiguration(): void
    {
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
            ],
            [],
            '',
            false
        );
        $frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $frontendConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $frontendConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $frontendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $this->mockTypoScriptService->expects(self::atLeastOnce())->method('convertTypoScriptArrayToPlainArray')->with($this->testTypoScriptSetup['config.']['tx_extbase.'])->willReturn($this->testTypoScriptSetupConverted['config']['tx_extbase']);
        $frontendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testPluginConfiguration);
        $expectedResult = [
            'settings' => [
                'setting1' => 'overriddenValue1',
                'setting2' => 'value2',
                'setting3' => 'additionalValue',
            ],
            'view' => [
                'viewSub' => [
                    'key1' => 'overridden',
                    'key2' => 'value2',
                    'key3' => 'new key',
                ],
            ],
            'persistence' => [
                'storagePid' => '123',
            ],
            'controllerConfiguration' => [],
        ];
        $frontendConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->with($expectedResult)->willReturn($expectedResult);
        $actualResult = $frontendConfigurationManager->getConfiguration();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRecursivelyMergesPluginConfigurationOfSpecifiedPluginWithFrameworkConfiguration(): void
    {
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
            ],
            [],
            '',
            false
        );
        $frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $frontendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $frontendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'SomeExtensionName',
            'SomePluginName'
        )->willReturn($this->testPluginConfiguration);
        $this->mockTypoScriptService->expects(self::atLeastOnce())->method('convertTypoScriptArrayToPlainArray')->with($this->testTypoScriptSetup['config.']['tx_extbase.'])->willReturn($this->testTypoScriptSetupConverted['config']['tx_extbase']);
        $expectedResult = [
            'settings' => [
                'setting1' => 'overriddenValue1',
                'setting2' => 'value2',
                'setting3' => 'additionalValue',
            ],
            'view' => [
                'viewSub' => [
                    'key1' => 'overridden',
                    'key2' => 'value2',
                    'key3' => 'new key',
                ],
            ],
            'persistence' => [
                'storagePid' => '123',
            ],
            'controllerConfiguration' => [],
        ];
        $frontendConfigurationManager->expects(self::never())->method('getContextSpecificFrameworkConfiguration');
        $actualResult = $frontendConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationDoesNotOverrideConfigurationWithContextSpecificFrameworkConfigurationIfDifferentPluginIsSpecified(): void
    {
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
            ],
            [],
            '',
            false
        );
        $frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $frontendConfigurationManager->expects(self::never())->method('getContextSpecificFrameworkConfiguration');
        $frontendConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
    }

    /**
     * @test
     */
    public function getConfigurationOverridesConfigurationWithContextSpecificFrameworkConfigurationIfNoPluginWasSpecified(): void
    {
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
            ],
            [],
            '',
            false
        );
        $frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $frontendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $frontendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with()->willReturn($this->testPluginConfiguration);
        $contextSpecificFrameworkConfiguration = [
            'context' => [
                'specific' => 'framework',
                'conf' => 'iguration',
            ],
        ];
        $frontendConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->willReturn($contextSpecificFrameworkConfiguration);
        $actualResult = $frontendConfigurationManager->getConfiguration();
        self::assertEquals($contextSpecificFrameworkConfiguration, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationOverridesConfigurationWithContextSpecificFrameworkConfigurationIfSpecifiedPluginIsTheCurrentPlugin(): void
    {
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
            ],
            [],
            '',
            false
        );
        $frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $frontendConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $frontendConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $frontendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $frontendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testPluginConfiguration);
        $contextSpecificFrameworkConfiguration = [
            'context' => [
                'specific' => 'framework',
                'conf' => 'iguration',
            ],
        ];
        $frontendConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->willReturn($contextSpecificFrameworkConfiguration);
        $actualResult = $frontendConfigurationManager->getConfiguration(
            'CurrentExtensionName',
            'CurrentPluginName'
        );
        self::assertEquals($contextSpecificFrameworkConfiguration, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationStoresResultInConfigurationCache(): void
    {
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
            ],
            [],
            '',
            false
        );
        $frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $frontendConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $frontendConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $frontendConfigurationManager->method('getPluginConfiguration')->willReturn(['foo' => 'bar']);
        $frontendConfigurationManager->getConfiguration();
        $frontendConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
        $expectedResult = [
            'currentextensionname_currentpluginname',
            'someotherextensionname_someothercurrentpluginname',
        ];
        $actualResult = array_keys($frontendConfigurationManager->_get('configurationCache'));
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRetrievesStoragePidIncludingGivenStoragePidWithRecursiveSetForSingleStoragePid(): void
    {
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
            ],
            [],
            '',
            false
        );
        $frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $pluginConfiguration = [
            'persistence' => [
                'storagePid' => 1,
                'recursive' => 99,
            ],
        ];
        $frontendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->willReturn($pluginConfiguration);
        $frontendConfigurationManager->expects(self::once())->method('getRecursiveStoragePids')->with([1]);
        $frontendConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
    }

    /**
     * @test
     */
    public function getConfigurationRetrievesStoragePidIncludingGivenStoragePidWithRecursiveSetForMultipleStoragePid(): void
    {
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
            ],
            [],
            '',
            false
        );
        $frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $pluginConfiguration = [
            'persistence' => [
                'storagePid' => '1,25',
                'recursive' => 99,
            ],
        ];
        $frontendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->willReturn($pluginConfiguration);
        $frontendConfigurationManager->expects(self::once())->method('getRecursiveStoragePids')->with([1, 25]);
        $frontendConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
    }

    /**
     * @test
     */
    public function getContentObjectReturnsInstanceOfContentObjectRenderer(): void
    {
        self::assertInstanceOf(ContentObjectRenderer::class, $this->frontendConfigurationManager->getContentObject());
    }

    /**
     * @test
     */
    public function getContentObjectTheCurrentContentObject(): void
    {
        $mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $this->frontendConfigurationManager->setContentObject($mockContentObject);
        self::assertSame($this->frontendConfigurationManager->getContentObject(), $mockContentObject);
    }

    /**
     * @test
     */
    public function getTypoScriptSetupReturnsSetupFromRequest(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray(['foo' => 'bar']);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('frontend.typoscript', $frontendTypoScript);
        self::assertEquals(['foo' => 'bar'], $this->frontendConfigurationManager->_call('getTypoScriptSetup'));
    }

    /**
     * @test
     */
    public function getPluginConfigurationReturnsEmptyArrayIfNoPluginConfigurationWasFound(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray(['foo' => 'bar']);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('frontend.typoscript', $frontendTypoScript);
        $expectedResult = [];
        $actualResult = $this->frontendConfigurationManager->_call(
            'getPluginConfiguration',
            'SomeExtensionName',
            'SomePluginName'
        );
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPluginConfigurationReturnsExtensionConfiguration(): void
    {
        $testSettings = [
            'settings.' => [
                'foo' => 'bar',
            ],
        ];
        $testSettingsConverted = [
            'settings' => [
                'foo' => 'bar',
            ],
        ];
        $testSetup = [
            'plugin.' => [
                'tx_someextensionname.' => $testSettings,
            ],
        ];
        $this->mockTypoScriptService->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->willReturn($testSettingsConverted);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray($testSetup);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('frontend.typoscript', $frontendTypoScript);
        $expectedResult = [
            'settings' => [
                'foo' => 'bar',
            ],
        ];
        $actualResult = $this->frontendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPluginConfigurationReturnsPluginConfiguration(): void
    {
        $testSettings = [
            'settings.' => [
                'foo' => 'bar',
            ],
        ];
        $testSettingsConverted = [
            'settings' => [
                'foo' => 'bar',
            ],
        ];
        $testSetup = [
            'plugin.' => [
                'tx_someextensionname_somepluginname.' => $testSettings,
            ],
        ];
        $this->mockTypoScriptService->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->willReturn($testSettingsConverted);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray($testSetup);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('frontend.typoscript', $frontendTypoScript);
        $expectedResult = [
            'settings' => [
                'foo' => 'bar',
            ],
        ];
        $actualResult = $this->frontendConfigurationManager->_call(
            'getPluginConfiguration',
            'SomeExtensionName',
            'SomePluginName'
        );
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPluginConfigurationRecursivelyMergesExtensionAndPluginConfiguration(): void
    {
        $testExtensionSettings = [
            'settings.' => [
                'foo' => 'bar',
                'some.' => [
                    'nested' => 'value',
                ],
            ],
        ];
        $testExtensionSettingsConverted = [
            'settings' => [
                'foo' => 'bar',
                'some' => [
                    'nested' => 'value',
                ],
            ],
        ];
        $testPluginSettings = [
            'settings.' => [
                'some.' => [
                    'nested' => 'valueOverride',
                    'new' => 'value',
                ],
            ],
        ];
        $testPluginSettingsConverted = [
            'settings' => [
                'some' => [
                    'nested' => 'valueOverride',
                    'new' => 'value',
                ],
            ],
        ];
        $testSetup = [
            'plugin.' => [
                'tx_someextensionname.' => $testExtensionSettings,
                'tx_someextensionname_somepluginname.' => $testPluginSettings,
            ],
        ];
        $this->mockTypoScriptService->expects(self::exactly(2))->method('convertTypoScriptArrayToPlainArray')
            ->withConsecutive([$testExtensionSettings], [$testPluginSettings])
            ->willReturnOnConsecutiveCalls($testExtensionSettingsConverted, $testPluginSettingsConverted);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray($testSetup);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('frontend.typoscript', $frontendTypoScript);
        $expectedResult = [
            'settings' => [
                'foo' => 'bar',
                'some' => [
                    'nested' => 'valueOverride',
                    'new' => 'value',
                ],
            ],
        ];
        $actualResult = $this->frontendConfigurationManager->_call(
            'getPluginConfiguration',
            'SomeExtensionName',
            'SomePluginName'
        );
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getControllerConfigurationReturnsEmptyArrayByDefault(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase'] = null;
        $expectedResult = [];
        $actualResult = $this->frontendConfigurationManager->_call(
            'getControllerConfiguration',
            'SomeExtensionName',
            'SomePluginName'
        );
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getControllerConfigurationReturnsConfigurationStoredInExtconf(): void
    {
        $controllerConfiguration = [
            'Controller1' => [
                'actions' => [
                    'action1',
                    'action2',
                ],
                'nonCacheableActions' => [
                    'action1',
                ],
            ],
            'Controller2' => [
                'actions' => [
                    'action3',
                    'action4',
                ],
            ],
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['SomeExtensionName']['plugins']['SomePluginName']['controllers'] = $controllerConfiguration;
        $expectedResult = $controllerConfiguration;
        $actualResult = $this->frontendConfigurationManager->_call(
            'getControllerConfiguration',
            'SomeExtensionName',
            'SomePluginName'
        );
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getContextSpecificFrameworkConfigurationCorrectlyCallsOverrideMethods(): void
    {
        $frameworkConfiguration = [
            'some' => [
                'framework' => 'configuration',
            ],
        ];
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'overrideStoragePidIfStartingPointIsSet',
                'overrideConfigurationFromPlugin',
                'overrideConfigurationFromFlexForm',
            ],
            [],
            '',
            false
        );
        $frontendConfigurationManager->expects(self::once())->method('overrideStoragePidIfStartingPointIsSet')->with($frameworkConfiguration)->willReturn(['overridden' => 'storagePid']);
        $frontendConfigurationManager->expects(self::once())->method('overrideConfigurationFromPlugin')->with(['overridden' => 'storagePid'])->willReturn(['overridden' => 'pluginConfiguration']);
        $frontendConfigurationManager->expects(self::once())->method('overrideConfigurationFromFlexForm')->with(['overridden' => 'pluginConfiguration'])->willReturn(['overridden' => 'flexFormConfiguration']);
        $expectedResult = ['overridden' => 'flexFormConfiguration'];
        $actualResult = $frontendConfigurationManager->_call(
            'getContextSpecificFrameworkConfiguration',
            $frameworkConfiguration
        );
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function mergeConfigurationIntoFrameworkConfigurationWorksAsExpected(): void
    {
        $configuration = [
            'persistence' => [
                'storagePid' => '0,1,2,3',
            ],
        ];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        self::assertSame(
            ['persistence' => ['storagePid' => '0,1,2,3']],
            $this->frontendConfigurationManager->_call(
                'mergeConfigurationIntoFrameworkConfiguration',
                $frameworkConfiguration,
                $configuration,
                'persistence'
            )
        );
    }

    /**
     * @test
     */
    public function overrideStoragePidIfStartingPointIsSetOverridesCorrectly(): void
    {
        $this->mockContentObject->data = ['pages' => '0', 'recursive' => 1];

        $pageRepositoryMock = $this->createMock(PageRepository::class);
        $pageRepositoryMock->method('getPageIdsRecursive')->willReturn([0, 1, 2, 3]);
        $this->frontendConfigurationManager->_set('pageRepository', $pageRepositoryMock);
        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        self::assertSame(
            ['persistence' => ['storagePid' => '0,1,2,3']],
            $this->frontendConfigurationManager->_call(
                'overrideStoragePidIfStartingPointIsSet',
                $frameworkConfiguration
            )
        );
    }

    /**
     * @test
     */
    public function overrideStoragePidIfStartingPointIsSetCorrectlyHandlesEmptyValuesFromPageRepository(): void
    {
        $this->mockContentObject->data = ['pages' => '0', 'recursive' => 1];
        $pageRepositoryMock = $this->createMock(PageRepository::class);
        $pageRepositoryMock->method('getPageIdsRecursive')->willReturn([0]);
        $this->frontendConfigurationManager->_set('pageRepository', $pageRepositoryMock);

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        self::assertSame(
            ['persistence' => ['storagePid' => '0']],
            $this->frontendConfigurationManager->_call(
                'overrideStoragePidIfStartingPointIsSet',
                $frameworkConfiguration
            )
        );
    }

    /**
     * @test
     */
    public function overrideConfigurationFromFlexFormChecksForDataIsString(): void
    {
        $flexFormService = $this->getMockBuilder(FlexFormService::class)
            ->onlyMethods(['convertFlexFormContentToArray'])
            ->getMock();
        $flexFormService->expects(self::once())->method('convertFlexFormContentToArray')->willReturn([
            'persistence' => [
                'storagePid' => '0,1,2,3',
            ],
        ]);

        $this->frontendConfigurationManager->_set('flexFormService', $flexFormService);
        $this->mockContentObject->data = ['pi_flexform' => '<XML_ARRAY>'];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        self::assertSame(
            ['persistence' => ['storagePid' => '0,1,2,3']],
            $this->frontendConfigurationManager->_call('overrideConfigurationFromFlexForm', $frameworkConfiguration)
        );
    }

    /**
     * @test
     */
    public function overrideConfigurationFromFlexFormChecksForDataIsStringAndEmpty(): void
    {
        $flexFormService = $this->getMockBuilder(FlexFormService::class)
            ->onlyMethods(['convertFlexFormContentToArray'])
            ->getMock();
        $flexFormService->expects(self::never())->method('convertFlexFormContentToArray');

        $this->frontendConfigurationManager->_set('flexFormService', $flexFormService);
        $this->mockContentObject->data = ['pi_flexform' => ''];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        self::assertSame(
            ['persistence' => ['storagePid' => '98']],
            $this->frontendConfigurationManager->_call('overrideConfigurationFromFlexForm', $frameworkConfiguration)
        );
    }

    /**
     * @test
     */
    public function overrideConfigurationFromFlexFormChecksForDataIsArray(): void
    {
        $flexFormService = $this->getMockBuilder(FlexFormService::class)
            ->onlyMethods(['convertFlexFormContentToArray'])
            ->getMock();
        $flexFormService->expects(self::never())->method('convertFlexFormContentToArray');

        $this->frontendConfigurationManager->_set('flexFormService', $flexFormService);
        $this->mockContentObject->data = ['pi_flexform' => ['persistence' => ['storagePid' => '0,1,2,3']]];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        self::assertSame(
            ['persistence' => ['storagePid' => '0,1,2,3']],
            $this->frontendConfigurationManager->_call('overrideConfigurationFromFlexForm', $frameworkConfiguration)
        );
    }

    /**
     * @test
     */
    public function overrideConfigurationFromFlexFormChecksForDataIsArrayAndEmpty(): void
    {
        $flexFormService = $this->getMockBuilder(FlexFormService::class)
            ->onlyMethods(['convertFlexFormContentToArray'])
            ->getMock();
        $flexFormService->expects(self::never())->method('convertFlexFormContentToArray');

        $this->frontendConfigurationManager->_set('flexFormService', $flexFormService);
        $this->mockContentObject->data = ['pi_flexform' => []];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        self::assertSame(
            ['persistence' => ['storagePid' => '98']],
            $this->frontendConfigurationManager->_call('overrideConfigurationFromFlexForm', $frameworkConfiguration)
        );
    }

    /**
     * @test
     */
    public function overrideConfigurationFromPluginOverridesCorrectly(): void
    {
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            ['getTypoScriptSetup'],
            [],
            '',
            false
        );
        $frontendConfigurationManager->_set('contentObject', $this->mockContentObject);
        $frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);

        $this->mockTypoScriptService->expects(self::once())->method('convertTypoScriptArrayToPlainArray')->willReturn([
            'persistence' => [
                'storagePid' => '0,1,2,3',
            ],
            'settings' => [
                'foo' => 'bar',
            ],
            'view' => [
                'foo' => 'bar',
            ],
        ]);
        $frontendConfigurationManager->method('getTypoScriptSetup')->willReturn([
            'plugin.' => [
                'tx_ext_pi1.' => [
                    'persistence.' => [
                        'storagePid' => '0,1,2,3',
                    ],
                    'settings.' => [
                        'foo' => 'bar',
                    ],
                    'view.' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ]);

        $frameworkConfiguration = [
            'extensionName' => 'ext',
            'pluginName' => 'pi1',
            'persistence' => [
                'storagePid' => '1',
            ],
            'settings' => [
                'foo' => 'qux',
            ],
            'view' => [
                'foo' => 'qux',
            ],
        ];
        self::assertSame(
            [
                'extensionName' => 'ext',
                'pluginName' => 'pi1',
                'persistence' => [
                    'storagePid' => '0,1,2,3',
                ],
                'settings' => [
                    'foo' => 'bar',
                ],
                'view' => [
                    'foo' => 'bar',
                ],
            ],
            $frontendConfigurationManager->_call('overrideConfigurationFromPlugin', $frameworkConfiguration)
        );
    }
}
