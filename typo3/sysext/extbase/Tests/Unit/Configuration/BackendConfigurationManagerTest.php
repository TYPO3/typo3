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
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class BackendConfigurationManagerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @var BackendConfigurationManager|MockObject|AccessibleObjectInterface
     */
    protected $backendConfigurationManager;

    /**
     * @var TypoScriptService|MockObject|AccessibleObjectInterface
     */
    protected $mockTypoScriptService;

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
        $this->backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            ['getTypoScriptSetup'],
            [],
            '',
            false
        );
        $this->mockTypoScriptService = $this->getMockBuilder(TypoScriptService::class)->getMock();
        $this->backendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
    }

    /**
     * @test
     */
    public function setConfigurationResetsConfigurationCache(): void
    {
        $this->backendConfigurationManager->_set('configurationCache', ['foo' => 'bar']);
        $this->backendConfigurationManager->setConfiguration([]);
        self::assertEquals([], $this->backendConfigurationManager->_get('configurationCache'));
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
        $this->backendConfigurationManager->setConfiguration($configuration);
        self::assertEquals('SomeExtensionName', $this->backendConfigurationManager->_get('extensionName'));
        self::assertEquals('SomePluginName', $this->backendConfigurationManager->_get('pluginName'));
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
        $this->backendConfigurationManager->setConfiguration($configuration);
        self::assertEquals($expectedResult, $this->backendConfigurationManager->_get('configuration'));
    }

    /**
     * @test
     */
    public function getConfigurationReturnsCachedResultOfCurrentPlugin(): void
    {
        $this->backendConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $this->backendConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $this->backendConfigurationManager->_set('configurationCache', [
            'currentextensionname_currentpluginname' => ['foo' => 'bar'],
            'someotherextension_somepluginname' => ['baz' => 'shouldnotbereturned'],
        ]);
        $expectedResult = ['foo' => 'bar'];
        $actualResult = $this->backendConfigurationManager->getConfiguration();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationReturnsCachedResultForGivenExtension(): void
    {
        $this->backendConfigurationManager->_set('configurationCache', [
            'someextensionname_somepluginname' => ['foo' => 'bar'],
            'someotherextension_somepluginname' => ['baz' => 'shouldnotbereturned'],
        ]);
        $expectedResult = ['foo' => 'bar'];
        $actualResult = $this->backendConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRecursivelyMergesCurrentPluginConfigurationWithFrameworkConfiguration(): void
    {
        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
                'getDefaultBackendStoragePid',
            ],
            [],
            '',
            false
        );
        $backendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $backendConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $backendConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $backendConfigurationManager->expects(self::any())->method('getDefaultBackendStoragePid')->willReturn(0);
        $backendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $this->mockTypoScriptService->expects(self::atLeastOnce())->method('convertTypoScriptArrayToPlainArray')->with($this->testTypoScriptSetup['config.']['tx_extbase.'])->willReturn($this->testTypoScriptSetupConverted['config']['tx_extbase']);
        $backendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
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
        $backendConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->with($expectedResult)->willReturn($expectedResult);
        $actualResult = $backendConfigurationManager->getConfiguration();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRecursivelyMergesPluginConfigurationOfSpecifiedPluginWithFrameworkConfiguration(): void
    {
        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
                'getDefaultBackendStoragePid',
            ],
            [],
            '',
            false
        );
        $backendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $backendConfigurationManager->expects(self::any())->method('getDefaultBackendStoragePid')->willReturn(0);
        $backendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $backendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
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
        $backendConfigurationManager->expects(self::never())->method('getContextSpecificFrameworkConfiguration');
        $actualResult = $backendConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationDoesNotOverrideConfigurationWithContextSpecificFrameworkConfigurationIfDifferentPluginIsSpecified(): void
    {
        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
                'getDefaultBackendStoragePid',
            ],
            [],
            '',
            false
        );
        $backendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $backendConfigurationManager->expects(self::any())->method('getDefaultBackendStoragePid')->willReturn(0);
        $backendConfigurationManager->expects(self::never())->method('getContextSpecificFrameworkConfiguration');
        $backendConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
    }

    /**
     * @test
     */
    public function getConfigurationOverridesConfigurationWithContextSpecificFrameworkConfigurationIfNoPluginWasSpecified(): void
    {
        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
                'getDefaultBackendStoragePid',
            ],
            [],
            '',
            false
        );
        $backendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $backendConfigurationManager->expects(self::any())->method('getDefaultBackendStoragePid')->willReturn(0);
        $backendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $backendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with()->willReturn($this->testPluginConfiguration);
        $contextSpecificFrameworkConfiguration = [
            'context' => [
                'specific' => 'framework',
                'conf' => 'iguration',
            ],
        ];
        $backendConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->willReturn($contextSpecificFrameworkConfiguration);
        $actualResult = $backendConfigurationManager->getConfiguration();
        self::assertEquals($contextSpecificFrameworkConfiguration, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationOverridesConfigurationWithContextSpecificFrameworkConfigurationIfSpecifiedPluginIsTheCurrentPlugin(): void
    {
        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
                'getDefaultBackendStoragePid',
            ],
            [],
            '',
            false
        );
        $backendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $backendConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $backendConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $backendConfigurationManager->expects(self::any())->method('getDefaultBackendStoragePid')->willReturn(0);
        $backendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $backendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testPluginConfiguration);
        $contextSpecificFrameworkConfiguration = [
            'context' => [
                'specific' => 'framework',
                'conf' => 'iguration',
            ],
        ];
        $backendConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->willReturn($contextSpecificFrameworkConfiguration);
        $actualResult = $backendConfigurationManager->getConfiguration(
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
        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
                'getDefaultBackendStoragePid',
            ],
            [],
            '',
            false
        );
        $backendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $backendConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $backendConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $backendConfigurationManager->expects(self::any())->method('getDefaultBackendStoragePid')->willReturn(0);
        $backendConfigurationManager->method('getPluginConfiguration')->willReturn(['foo' => 'bar']);
        $backendConfigurationManager->getConfiguration();
        $backendConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
        $expectedResult = [
            'currentextensionname_currentpluginname',
            'someotherextensionname_someothercurrentpluginname',
        ];
        $actualResult = array_keys($backendConfigurationManager->_get('configurationCache'));
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRetrievesStoragePidIncludingGivenStoragePidWithRecursiveSetForSingleStoragePid(): void
    {
        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
                'getDefaultBackendStoragePid',
            ],
            [],
            '',
            false
        );
        $backendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $backendConfigurationManager->expects(self::any())->method('getDefaultBackendStoragePid')->willReturn(0);
        $pluginConfiguration = [
            'persistence' => [
                'storagePid' => 1,
                'recursive' => 99,
            ],
        ];
        $backendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->willReturn($pluginConfiguration);
        $backendConfigurationManager->expects(self::once())->method('getRecursiveStoragePids')->with([1]);
        $backendConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
    }

    /**
     * @test
     */
    public function getConfigurationRetrievesStoragePidIncludingGivenStoragePidWithRecursiveSetForMultipleStoragePid(): void
    {
        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids',
                'getDefaultBackendStoragePid',
            ],
            [],
            '',
            false
        );
        $backendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $backendConfigurationManager->expects(self::any())->method('getDefaultBackendStoragePid')->willReturn(0);
        $pluginConfiguration = [
            'persistence' => [
                'storagePid' => '1,25',
                'recursive' => 99,
            ],
        ];
        $backendConfigurationManager->expects(self::once())->method('getPluginConfiguration')->willReturn($pluginConfiguration);
        $backendConfigurationManager->expects(self::once())->method('getRecursiveStoragePids')->with([1, 25]);
        $backendConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
    }

    /**
     * @test
     */
    public function getContentObjectReturnsInstanceOfContentObjectRenderer(): void
    {
        self::assertInstanceOf(ContentObjectRenderer::class, $this->backendConfigurationManager->getContentObject());
    }

    /**
     * @test
     */
    public function getContentObjectTheCurrentContentObject(): void
    {
        $mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $this->backendConfigurationManager->setContentObject($mockContentObject);
        self::assertSame($this->backendConfigurationManager->getContentObject(), $mockContentObject);
    }

    /**
     * @test
     */
    public function getCurrentPageIdReturnsPageIdFromGet(): void
    {
        $_GET['id'] = 123;
        $expectedResult = 123;
        $actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getCurrentPageIdReturnsPageIdFromPost(): void
    {
        $_GET['id'] = 123;
        $_POST['id'] = 321;
        $expectedResult = 321;
        $actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPluginConfigurationReturnsEmptyArrayIfNoPluginConfigurationWasFound(): void
    {
        $this->backendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn(['foo' => 'bar']);
        $expectedResult = [];
        $actualResult = $this->backendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
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
            'module.' => [
                'tx_someextensionname.' => $testSettings,
            ],
        ];
        $this->mockTypoScriptService->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->willReturn($testSettingsConverted);
        $this->backendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($testSetup);
        $expectedResult = [
            'settings' => [
                'foo' => 'bar',
            ],
        ];
        $actualResult = $this->backendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName');
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
            'module.' => [
                'tx_someextensionname_somepluginname.' => $testSettings,
            ],
        ];
        $this->mockTypoScriptService->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->willReturn($testSettingsConverted);
        $this->backendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($testSetup);
        $expectedResult = [
            'settings' => [
                'foo' => 'bar',
            ],
        ];
        $actualResult = $this->backendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
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
            'module.' => [
                'tx_someextensionname.' => $testExtensionSettings,
                'tx_someextensionname_somepluginname.' => $testPluginSettings,
            ],
        ];
        $this->mockTypoScriptService->expects(self::exactly(2))->method('convertTypoScriptArrayToPlainArray')
            ->withConsecutive([$testExtensionSettings], [$testPluginSettings])
            ->willReturnOnConsecutiveCalls($testExtensionSettingsConverted, $testPluginSettingsConverted);
        $this->backendConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($testSetup);
        $expectedResult = [
            'settings' => [
                'foo' => 'bar',
                'some' => [
                    'nested' => 'valueOverride',
                    'new' => 'value',
                ],
            ],
        ];
        $actualResult = $this->backendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getControllerConfigurationReturnsEmptyArrayByDefault(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase'] = null;
        $expectedResult = [];
        $actualResult = $this->backendConfigurationManager->_call('getControllerConfiguration', 'SomeExtensionName', 'SomePluginName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreNotExtendedIfRecursiveSearchIsNotConfigured(): void
    {
        $storagePids = [1, 2, 3];

        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            ['getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getControllerConfiguration'],
            [],
            '',
            false
        );

        $expectedResult = [1, 2, 3];
        $actualResult = $backendConfigurationManager->_call('getRecursiveStoragePids', $storagePids);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreNotExtendedIfRecursiveSearchIsConfiguredForZeroLevels(): void
    {
        $storagePids = [1, 2, 3];
        $recursive = 0;

        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            ['getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getControllerConfiguration'],
            [],
            '',
            false
        );

        $expectedResult = [1, 2, 3];
        $actualResult = $backendConfigurationManager->_call('getRecursiveStoragePids', $storagePids, $recursive);
        self::assertEquals($expectedResult, $actualResult);
    }
}
