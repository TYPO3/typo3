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

use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractConfigurationManagerTest extends UnitTestCase
{
    /**
     * @var AbstractConfigurationManager|\PHPUnit\Framework\MockObject\MockObject|AccessibleObjectInterface
     */
    protected $abstractConfigurationManager;

    /**
     * @var TypoScriptService|\PHPUnit\Framework\MockObject\MockObject|AccessibleObjectInterface
     */
    protected $mockTypoScriptService;

    /**
     * @var array
     */
    protected $testTypoScriptSetup = [
        'foo.' => [
            'bar' => 'baz'
        ],
        'config.' => [
            'tx_extbase.' => [
                'settings.' => [
                    'setting1' => 'value1',
                    'setting2' => 'value2'
                ],
                'view.' => [
                    'viewSub.' => [
                        'key1' => 'value1',
                        'key2' => 'value2'
                    ]
                ]
            ]
        ]
    ];

    /**
     * @var array
     */
    protected $testTypoScriptSetupConverted = [
        'foo' => [
            'bar' => 'baz'
        ],
        'config' => [
            'tx_extbase' => [
                'settings' => [
                    'setting1' => 'value1',
                    'setting2' => 'value2'
                ],
                'view' => [
                    'viewSub' => [
                        'key1' => 'value1',
                        'key2' => 'value2'
                    ]
                ]
            ]
        ]
    ];

    /**
     * @var array
     */
    protected $testPluginConfiguration = [
        'settings' => [
            'setting1' => 'overriddenValue1',
            'setting3' => 'additionalValue'
        ],
        'view' => [
            'viewSub' => [
                'key1' => 'overridden',
                'key3' => 'new key'
            ]
        ],
        'persistence' => [
            'storagePid' => '123'
        ]
    ];

    /**
     * Sets up this testcase
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->abstractConfigurationManager = $this->getAccessibleMock(
            AbstractConfigurationManager::class,
            [
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getControllerConfiguration',
                'getRecursiveStoragePids'
            ],
            [],
            '',
            false
        );
        $this->mockTypoScriptService = $this->getMockBuilder(TypoScriptService::class)->getMock();
        $this->abstractConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
    }

    /**
     * @test
     */
    public function setConfigurationResetsConfigurationCache(): void
    {
        $this->abstractConfigurationManager->_set('configurationCache', ['foo' => 'bar']);
        $this->abstractConfigurationManager->setConfiguration([]);
        self::assertEquals([], $this->abstractConfigurationManager->_get('configurationCache'));
    }

    /**
     * @test
     */
    public function setConfigurationSetsExtensionAndPluginName(): void
    {
        $configuration = [
            'extensionName' => 'SomeExtensionName',
            'pluginName' => 'SomePluginName'
        ];
        $this->abstractConfigurationManager->setConfiguration($configuration);
        self::assertEquals('SomeExtensionName', $this->abstractConfigurationManager->_get('extensionName'));
        self::assertEquals('SomePluginName', $this->abstractConfigurationManager->_get('pluginName'));
    }

    /**
     * @test
     */
    public function setConfigurationConvertsTypoScriptArrayToPlainArray(): void
    {
        $configuration = [
            'foo' => 'bar',
            'settings.' => ['foo' => 'bar'],
            'view.' => ['subkey.' => ['subsubkey' => 'subsubvalue']]
        ];
        $expectedResult = [
            'foo' => 'bar',
            'settings' => ['foo' => 'bar'],
            'view' => ['subkey' => ['subsubkey' => 'subsubvalue']]
        ];
        $this->mockTypoScriptService->expects(self::atLeastOnce())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->willReturn($expectedResult);
        $this->abstractConfigurationManager->setConfiguration($configuration);
        self::assertEquals($expectedResult, $this->abstractConfigurationManager->_get('configuration'));
    }

    /**
     * @test
     */
    public function getConfigurationReturnsCachedResultOfCurrentPlugin(): void
    {
        $this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $this->abstractConfigurationManager->_set('configurationCache', [
            'currentextensionname_currentpluginname' => ['foo' => 'bar'],
            'someotherextension_somepluginname' => ['baz' => 'shouldnotbereturned']
        ]);
        $expectedResult = ['foo' => 'bar'];
        $actualResult = $this->abstractConfigurationManager->getConfiguration();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationReturnsCachedResultForGivenExtension(): void
    {
        $this->abstractConfigurationManager->_set('configurationCache', [
            'someextensionname_somepluginname' => ['foo' => 'bar'],
            'someotherextension_somepluginname' => ['baz' => 'shouldnotbereturned']
        ]);
        $expectedResult = ['foo' => 'bar'];
        $actualResult = $this->abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRecursivelyMergesCurrentPluginConfigurationWithFrameworkConfiguration(): void
    {
        $this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $this->abstractConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $this->mockTypoScriptService->expects(self::atLeastOnce())->method('convertTypoScriptArrayToPlainArray')->with($this->testTypoScriptSetup['config.']['tx_extbase.'])->willReturn($this->testTypoScriptSetupConverted['config']['tx_extbase']);
        $this->abstractConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testPluginConfiguration);
        $expectedResult = [
            'settings' => [
                'setting1' => 'overriddenValue1',
                'setting2' => 'value2',
                'setting3' => 'additionalValue'
            ],
            'view' => [
                'viewSub' => [
                    'key1' => 'overridden',
                    'key2' => 'value2',
                    'key3' => 'new key'
                ]
            ],
            'persistence' => [
                'storagePid' => '123'
            ],
            'controllerConfiguration' => []
        ];
        $this->abstractConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->with($expectedResult)->willReturn($expectedResult);
        $actualResult = $this->abstractConfigurationManager->getConfiguration();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRecursivelyMergesPluginConfigurationOfSpecifiedPluginWithFrameworkConfiguration(
    ): void {
        $this->abstractConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $this->abstractConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'SomeExtensionName',
            'SomePluginName'
        )->willReturn($this->testPluginConfiguration);
        $this->mockTypoScriptService->expects(self::atLeastOnce())->method('convertTypoScriptArrayToPlainArray')->with($this->testTypoScriptSetup['config.']['tx_extbase.'])->willReturn($this->testTypoScriptSetupConverted['config']['tx_extbase']);
        $expectedResult = [
            'settings' => [
                'setting1' => 'overriddenValue1',
                'setting2' => 'value2',
                'setting3' => 'additionalValue'
            ],
            'view' => [
                'viewSub' => [
                    'key1' => 'overridden',
                    'key2' => 'value2',
                    'key3' => 'new key'
                ]
            ],
            'persistence' => [
                'storagePid' => '123'
            ],
            'controllerConfiguration' => []
        ];
        $this->abstractConfigurationManager->expects(self::never())->method('getContextSpecificFrameworkConfiguration');
        $actualResult = $this->abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationDoesNotOverrideConfigurationWithContextSpecificFrameworkConfigurationIfDifferentPluginIsSpecified(
    ): void {
        $this->abstractConfigurationManager->expects(self::never())->method('getContextSpecificFrameworkConfiguration');
        $this->abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
    }

    /**
     * @test
     */
    public function getConfigurationOverridesConfigurationWithContextSpecificFrameworkConfigurationIfNoPluginWasSpecified(
    ): void {
        $this->abstractConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $this->abstractConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with()->willReturn($this->testPluginConfiguration);
        $contextSpecificFrameworkConfiguration = [
            'context' => [
                'specific' => 'framework',
                'conf' => 'iguration'
            ]
        ];
        $this->abstractConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->willReturn($contextSpecificFrameworkConfiguration);
        $actualResult = $this->abstractConfigurationManager->getConfiguration();
        self::assertEquals($contextSpecificFrameworkConfiguration, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationOverridesConfigurationWithContextSpecificFrameworkConfigurationIfSpecifiedPluginIsTheCurrentPlugin(
    ): void {
        $this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $this->abstractConfigurationManager->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $this->abstractConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testPluginConfiguration);
        $contextSpecificFrameworkConfiguration = [
            'context' => [
                'specific' => 'framework',
                'conf' => 'iguration'
            ]
        ];
        $this->abstractConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->willReturn($contextSpecificFrameworkConfiguration);
        $actualResult = $this->abstractConfigurationManager->getConfiguration(
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
        $this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $this->abstractConfigurationManager->expects(self::any())->method('getPluginConfiguration')->willReturn(['foo' => 'bar']);
        $this->abstractConfigurationManager->getConfiguration();
        $this->abstractConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
        $expectedResult = [
            'currentextensionname_currentpluginname',
            'someotherextensionname_someothercurrentpluginname'
        ];
        $actualResult = array_keys($this->abstractConfigurationManager->_get('configurationCache'));
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRetrievesStoragePidIncludingGivenStoragePidWithRecursiveSetForSingleStoragePid(
    ): void {
        $pluginConfiguration = [
            'persistence' => [
                'storagePid' => 1,
                'recursive' => 99
            ]
        ];
        $this->abstractConfigurationManager->expects(self::once())->method('getPluginConfiguration')->willReturn($pluginConfiguration);
        $this->abstractConfigurationManager->expects(self::once())->method('getRecursiveStoragePids')->with([-1]);
        $this->abstractConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
    }

    /**
     * @test
     */
    public function getConfigurationRetrievesStoragePidIncludingGivenStoragePidWithRecursiveSetForMultipleStoragePid(
    ): void {
        $pluginConfiguration = [
            'persistence' => [
                'storagePid' => '1,25',
                'recursive' => 99
            ]
        ];
        $this->abstractConfigurationManager->expects(self::once())->method('getPluginConfiguration')->willReturn($pluginConfiguration);
        $this->abstractConfigurationManager->expects(self::once())->method('getRecursiveStoragePids')->with([-1, -25]);
        $this->abstractConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
    }

    /**
     * @test
     */
    public function getContentObjectReturnsNullIfNoContentObjectHasBeenSet(): void
    {
        self::assertNull($this->abstractConfigurationManager->getContentObject());
    }

    /**
     * @test
     */
    public function getContentObjectTheCurrentContentObject(): void
    {
        /** @var ContentObjectRenderer|\PHPUnit\Framework\MockObject\MockObject $mockContentObject */
        $mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $this->abstractConfigurationManager->setContentObject($mockContentObject);
        self::assertSame($this->abstractConfigurationManager->getContentObject(), $mockContentObject);
    }
}
