<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Configuration;

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

/**
 * Test case
 */
class AbstractConfigurationManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $abstractConfigurationManager;

    /**
     * @var \TYPO3\CMS\Extbase\Service\TypoScriptService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
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
     * @var array
     */
    protected $testSwitchableControllerActions = [
        'Controller1' => [
            'actions' => ['action1', 'action2', 'action3']
        ],
        'Controller2' => [
            'actions' => ['action4', 'action5', 'action6'],
            'nonCacheableActions' => ['action4', 'action6']
        ]
    ];

    /**
     * Sets up this testcase
     */
    protected function setUp()
    {
        $this->abstractConfigurationManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager::class, ['getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions', 'getRecursiveStoragePids']);
        $this->mockTypoScriptService = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Service\TypoScriptService::class);
        $this->abstractConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
    }

    /**
     * @test
     */
    public function setConfigurationResetsConfigurationCache()
    {
        $this->abstractConfigurationManager->_set('configurationCache', ['foo' => 'bar']);
        $this->abstractConfigurationManager->setConfiguration([]);
        $this->assertEquals([], $this->abstractConfigurationManager->_get('configurationCache'));
    }

    /**
     * @test
     */
    public function setConfigurationSetsExtensionAndPluginName()
    {
        $configuration = [
            'extensionName' => 'SomeExtensionName',
            'pluginName' => 'SomePluginName'
        ];
        $this->abstractConfigurationManager->setConfiguration($configuration);
        $this->assertEquals('SomeExtensionName', $this->abstractConfigurationManager->_get('extensionName'));
        $this->assertEquals('SomePluginName', $this->abstractConfigurationManager->_get('pluginName'));
    }

    /**
     * @test
     */
    public function setConfigurationConvertsTypoScriptArrayToPlainArray()
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
        $this->mockTypoScriptService->expects($this->atLeastOnce())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->will($this->returnValue($expectedResult));
        $this->abstractConfigurationManager->setConfiguration($configuration);
        $this->assertEquals($expectedResult, $this->abstractConfigurationManager->_get('configuration'));
    }

    /**
     * @test
     */
    public function getConfigurationReturnsCachedResultOfCurrentPlugin()
    {
        $this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $this->abstractConfigurationManager->_set('configurationCache', ['currentextensionname_currentpluginname' => ['foo' => 'bar'], 'someotherextension_somepluginname' => ['baz' => 'shouldnotbereturned']]);
        $expectedResult = ['foo' => 'bar'];
        $actualResult = $this->abstractConfigurationManager->getConfiguration();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationReturnsCachedResultForGivenExtension()
    {
        $this->abstractConfigurationManager->_set('configurationCache', ['someextensionname_somepluginname' => ['foo' => 'bar'], 'someotherextension_somepluginname' => ['baz' => 'shouldnotbereturned']]);
        $expectedResult = ['foo' => 'bar'];
        $actualResult = $this->abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRecursivelyMergesCurrentPluginConfigurationWithFrameworkConfiguration()
    {
        $this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $this->abstractConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue($this->testTypoScriptSetup));
        $this->mockTypoScriptService->expects($this->atLeastOnce())->method('convertTypoScriptArrayToPlainArray')->with($this->testTypoScriptSetup['config.']['tx_extbase.'])->will($this->returnValue($this->testTypoScriptSetupConverted['config']['tx_extbase']));
        $this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
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
            'controllerConfiguration' => null
        ];
        $this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->with($expectedResult)->will($this->returnValue($expectedResult));
        $actualResult = $this->abstractConfigurationManager->getConfiguration();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRecursivelyMergesPluginConfigurationOfSpecifiedPluginWithFrameworkConfiguration()
    {
        $this->abstractConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue($this->testTypoScriptSetup));
        $this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('SomeExtensionName', 'SomePluginName')->will($this->returnValue($this->testPluginConfiguration));
        $this->mockTypoScriptService->expects($this->atLeastOnce())->method('convertTypoScriptArrayToPlainArray')->with($this->testTypoScriptSetup['config.']['tx_extbase.'])->will($this->returnValue($this->testTypoScriptSetupConverted['config']['tx_extbase']));
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
            'controllerConfiguration' => null
        ];
        $this->abstractConfigurationManager->expects($this->never())->method('getContextSpecificFrameworkConfiguration');
        $actualResult = $this->abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationDoesNotOverrideConfigurationWithContextSpecificFrameworkConfigurationIfDifferentPluginIsSpecified()
    {
        $this->abstractConfigurationManager->expects($this->never())->method('getContextSpecificFrameworkConfiguration');
        $this->abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
    }

    /**
     * @test
     */
    public function getConfigurationOverridesConfigurationWithContextSpecificFrameworkConfigurationIfNoPluginWasSpecified()
    {
        $this->abstractConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue($this->testTypoScriptSetup));
        $this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with()->will($this->returnValue($this->testPluginConfiguration));
        $contextSpecifixFrameworkConfiguration = [
            'context' => [
                'specific' => 'framwork',
                'conf' => 'iguration'
            ]
        ];
        $this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnValue($contextSpecifixFrameworkConfiguration));
        $actualResult = $this->abstractConfigurationManager->getConfiguration();
        $this->assertEquals($contextSpecifixFrameworkConfiguration, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationOverridesConfigurationWithContextSpecificFrameworkConfigurationIfSpecifiedPluginIsTheCurrentPlugin()
    {
        $this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $this->abstractConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue($this->testTypoScriptSetup));
        $this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
        $contextSpecifixFrameworkConfiguration = [
            'context' => [
                'specific' => 'framwork',
                'conf' => 'iguration'
            ]
        ];
        $this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnValue($contextSpecifixFrameworkConfiguration));
        $actualResult = $this->abstractConfigurationManager->getConfiguration('CurrentExtensionName', 'CurrentPluginName');
        $this->assertEquals($contextSpecifixFrameworkConfiguration, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationStoresResultInConfigurationCache()
    {
        $this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
        $this->abstractConfigurationManager->expects($this->any())->method('getPluginConfiguration')->will($this->returnValue(['foo' => 'bar']));
        $this->abstractConfigurationManager->getConfiguration();
        $this->abstractConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
        $expectedResult = ['currentextensionname_currentpluginname', 'someotherextensionname_someothercurrentpluginname'];
        $actualResult = array_keys($this->abstractConfigurationManager->_get('configurationCache'));
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRetrievesStoragePidIncludingGivenStoragePidWithRecursiveSetForSingleStoragePid()
    {
        $pluginConfiguration = [
            'persistence' => [
                'storagePid' => 1,
                'recursive' => 99
            ]
        ];
        $this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->will($this->returnValue($pluginConfiguration));
        $this->abstractConfigurationManager->expects($this->once())->method('getRecursiveStoragePids')->with('-1');
        $this->abstractConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
    }

    /**
     * @test
     */
    public function getConfigurationRetrievesStoragePidIncludingGivenStoragePidWithRecursiveSetForMultipleStoragePid()
    {
        $pluginConfiguration = [
            'persistence' => [
                'storagePid' => '1,25',
                'recursive' => 99
            ]
        ];
        $this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->will($this->returnValue($pluginConfiguration));
        $this->abstractConfigurationManager->expects($this->once())->method('getRecursiveStoragePids')->with('-1,-25');
        $this->abstractConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
    }

    /**
     * switchableControllerActions *
     */
    /**
     * @test
     */
    public function switchableControllerActionsAreNotOverriddenIfPluginNameIsSpecified()
    {
        /** @var \TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $abstractConfigurationManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager::class, ['overrideSwitchableControllerActions', 'getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions', 'getRecursiveStoragePids']);
        $abstractConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $abstractConfigurationManager->setConfiguration(['switchableControllerActions' => ['overriddenSwitchableControllerActions']]);
        $abstractConfigurationManager->expects($this->any())->method('getPluginConfiguration')->will($this->returnValue([]));
        $abstractConfigurationManager->expects($this->never())->method('overrideSwitchableControllerActions');
        $abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
    }

    /**
     * @test
     */
    public function switchableControllerActionsAreOverriddenIfSpecifiedPluginIsTheCurrentPlugin()
    {
        /** @var \TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $configuration = ['extensionName' => 'CurrentExtensionName', 'pluginName' => 'CurrentPluginName', 'switchableControllerActions' => ['overriddenSwitchableControllerActions']];
        $abstractConfigurationManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager::class, ['overrideSwitchableControllerActions', 'getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions', 'getRecursiveStoragePids']);
        $this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->will($this->returnValue($configuration));
        $abstractConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $abstractConfigurationManager->setConfiguration($configuration);
        $abstractConfigurationManager->expects($this->any())->method('getPluginConfiguration')->will($this->returnValue([]));
        $abstractConfigurationManager->expects($this->once())->method('overrideSwitchableControllerActions');
        $abstractConfigurationManager->getConfiguration('CurrentExtensionName', 'CurrentPluginName');
    }

    /**
     * @test
     */
    public function switchableControllerActionsAreOverriddenIfPluginNameIsNotSpecified()
    {
        /** @var \TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $configuration = ['switchableControllerActions' => ['overriddenSwitchableControllerActions']];
        $abstractConfigurationManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager::class, ['overrideSwitchableControllerActions', 'getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions', 'getRecursiveStoragePids']);
        $this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->will($this->returnValue($configuration));
        $abstractConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $abstractConfigurationManager->setConfiguration($configuration);
        $abstractConfigurationManager->expects($this->any())->method('getPluginConfiguration')->will($this->returnValue([]));
        $abstractConfigurationManager->expects($this->once())->method('overrideSwitchableControllerActions');
        $abstractConfigurationManager->getConfiguration();
    }

    /**
     * @test
     */
    public function orderOfActionsCanBeOverriddenForCurrentPlugin()
    {
        $configuration = [
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
            'switchableControllerActions' => [
                'Controller1' => ['action2', 'action1', 'action3']
            ]
        ];
        $this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->will($this->returnValue($configuration));
        $this->abstractConfigurationManager->setConfiguration($configuration);
        $this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
        $this->abstractConfigurationManager->expects($this->once())->method('getSwitchableControllerActions')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testSwitchableControllerActions));
        $this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnCallBack(function ($a) {
            return $a;
        }));
        $mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
        $expectedResult = [
            'Controller1' => [
                'actions' => ['action2', 'action1', 'action3']
            ]
        ];
        $actualResult = $mergedConfiguration['controllerConfiguration'];
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function newActionsCanBeAddedForCurrentPlugin()
    {
        $configuration = [
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
            'switchableControllerActions' => [
                'Controller1' => ['action2', 'action1', 'action3', 'newAction']
            ]
        ];
        $this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->will($this->returnValue($configuration));
        $this->abstractConfigurationManager->setConfiguration($configuration);
        $this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
        $this->abstractConfigurationManager->expects($this->once())->method('getSwitchableControllerActions')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testSwitchableControllerActions));
        $this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnCallBack(function ($a) {
            return $a;
        }));
        $mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
        $expectedResult = [
            'Controller1' => [
                'actions' => ['action2', 'action1', 'action3', 'newAction']
            ]
        ];
        $actualResult = $mergedConfiguration['controllerConfiguration'];
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function controllersCanNotBeOverridden()
    {
        $configuration = [
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
            'switchableControllerActions' => [
                'NewController' => ['action1', 'action2']
            ]
        ];
        $this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->will($this->returnValue($configuration));
        $this->abstractConfigurationManager->setConfiguration($configuration);
        $this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
        $this->abstractConfigurationManager->expects($this->once())->method('getSwitchableControllerActions')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testSwitchableControllerActions));
        $this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnCallBack(function ($a) {
            return $a;
        }));
        $mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
        $expectedResult = [];
        $actualResult = $mergedConfiguration['controllerConfiguration'];
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function cachingOfActionsCanNotBeChanged()
    {
        $configuration = [
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
            'switchableControllerActions' => [
                'Controller1' => ['newAction', 'action1'],
                'Controller2' => ['newAction2', 'action4', 'action5']
            ]
        ];
        $this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->will($this->returnValue($configuration));
        $this->abstractConfigurationManager->setConfiguration($configuration);
        $this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
        $this->abstractConfigurationManager->expects($this->once())->method('getSwitchableControllerActions')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testSwitchableControllerActions));
        $this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnCallBack(function ($a) {
            return $a;
        }));
        $mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
        $expectedResult = [
            'Controller1' => [
                'actions' => ['newAction', 'action1']
            ],
            'Controller2' => [
                'actions' => ['newAction2', 'action4', 'action5'],
                'nonCacheableActions' => ['action4']
            ]
        ];
        $actualResult = $mergedConfiguration['controllerConfiguration'];
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getContentObjectReturnsNullIfNoContentObjectHasBeenSet()
    {
        $this->assertNull($this->abstractConfigurationManager->getContentObject());
    }

    /**
     * @test
     */
    public function getContentObjectTheCurrentContentObject()
    {
        $mockContentObject = $this->getMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $this->abstractConfigurationManager->setContentObject($mockContentObject);
        $this->assertSame($this->abstractConfigurationManager->getContentObject(), $mockContentObject);
    }
}
