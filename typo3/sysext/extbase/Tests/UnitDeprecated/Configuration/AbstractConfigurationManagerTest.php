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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Configuration;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractConfigurationManagerTest extends UnitTestCase
{
    /**
     * @var AbstractConfigurationManager|MockObject|AccessibleObjectInterface
     */
    protected $abstractConfigurationManager;

    /**
     * @var TypoScriptService|MockObject|AccessibleObjectInterface
     */
    protected $mockTypoScriptService;

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
        'MyExtension\\Controller\\Controller1' => [
            'alias' => 'Controller1',
            'actions' => ['action1', 'action2', 'action3']
        ],
        'MyExtension\\Controller\\Controller2' => [
            'alias' => 'Controller2',
            'actions' => ['action4', 'action5', 'action6'],
            'nonCacheableActions' => ['action4', 'action6']
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
        $this->mockTypoScriptService = $this->getAccessibleMock(TypoScriptService::class);
        $this->abstractConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
    }

    /**
     * @test
     */
    public function orderOfActionsCanBeOverriddenForCurrentPlugin(): void
    {
        $configuration = [
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
            'switchableControllerActions' => [
                'Controller1' => ['action2', 'action1', 'action3']
            ]
        ];
        $this->mockTypoScriptService->expects(self::any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->willReturn($configuration);
        $this->abstractConfigurationManager->setConfiguration($configuration);
        $this->abstractConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testPluginConfiguration);
        $this->abstractConfigurationManager->expects(self::once())->method('getControllerConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testSwitchableControllerActions);
        $this->abstractConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->willReturnCallback(function (
            $a
        ) {
            return $a;
        });
        $mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
        $expectedResult = [
            'MyExtension\\Controller\\Controller1' => [
                'className' => 'MyExtension\\Controller\\Controller1',
                'alias' => 'Controller1',
                'actions' => ['action2', 'action1', 'action3']
            ]
        ];
        $actualResult = $mergedConfiguration['controllerConfiguration'];
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function controllerOfSwitchableControllerActionsCanBeAFullyQualifiedClassName(): void
    {
        $configuration = [
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
            'switchableControllerActions' => [
                'MyExtension\\Controller\\Controller1' => ['action2', 'action1', 'action3'],
                '\\MyExtension\\Controller\\Controller2' => ['newAction2', 'action4', 'action5']
            ]
        ];
        $this->mockTypoScriptService->expects(self::any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->willReturn($configuration);
        $this->abstractConfigurationManager->setConfiguration($configuration);
        $this->abstractConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testPluginConfiguration);
        $this->abstractConfigurationManager->expects(self::once())->method('getControllerConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testSwitchableControllerActions);
        $this->abstractConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->willReturnCallback(function (
            $a
        ) {
            return $a;
        });
        $mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
        $expectedResult = [
            'MyExtension\\Controller\\Controller1' => [
                'className' => 'MyExtension\\Controller\\Controller1',
                'alias' => 'Controller1',
                'actions' => ['action2', 'action1', 'action3']
            ],
            'MyExtension\\Controller\\Controller2' => [
                'className' => 'MyExtension\\Controller\\Controller2',
                'alias' => 'Controller2',
                'actions' => ['newAction2', 'action4', 'action5'],
                'nonCacheableActions' => ['action4']
            ]
        ];
        $actualResult = $mergedConfiguration['controllerConfiguration'];
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function newActionsCanBeAddedForCurrentPlugin(): void
    {
        $configuration = [
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
            'switchableControllerActions' => [
                'Controller1' => ['action2', 'action1', 'action3', 'newAction']
            ]
        ];
        $this->mockTypoScriptService->expects(self::any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->willReturn($configuration);
        $this->abstractConfigurationManager->setConfiguration($configuration);
        $this->abstractConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testPluginConfiguration);
        $this->abstractConfigurationManager->expects(self::once())->method('getControllerConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testSwitchableControllerActions);
        $this->abstractConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->willReturnCallback(function (
            $a
        ) {
            return $a;
        });
        $mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
        $expectedResult = [
            'MyExtension\\Controller\\Controller1' => [
                'className' => 'MyExtension\\Controller\\Controller1',
                'alias' => 'Controller1',
                'actions' => ['action2', 'action1', 'action3', 'newAction']
            ]
        ];
        $actualResult = $mergedConfiguration['controllerConfiguration'];
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function controllersCanNotBeOverridden(): void
    {
        $configuration = [
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
            'switchableControllerActions' => [
                'NewController' => ['action1', 'action2']
            ]
        ];
        $this->mockTypoScriptService->expects(self::any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->willReturn($configuration);
        $this->abstractConfigurationManager->setConfiguration($configuration);
        $this->abstractConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testPluginConfiguration);
        $this->abstractConfigurationManager->expects(self::once())->method('getControllerConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testSwitchableControllerActions);
        $this->abstractConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->willReturnCallback(function (
            $a
        ) {
            return $a;
        });
        $mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
        $expectedResult = [];
        $actualResult = $mergedConfiguration['controllerConfiguration'];
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function cachingOfActionsCanNotBeChanged(): void
    {
        $configuration = [
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
            'switchableControllerActions' => [
                'Controller1' => ['newAction', 'action1'],
                'Controller2' => ['newAction2', 'action4', 'action5']
            ]
        ];
        $this->mockTypoScriptService->expects(self::any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->willReturn($configuration);
        $this->abstractConfigurationManager->setConfiguration($configuration);
        $this->abstractConfigurationManager->expects(self::once())->method('getPluginConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testPluginConfiguration);
        $this->abstractConfigurationManager->expects(self::once())->method('getControllerConfiguration')->with(
            'CurrentExtensionName',
            'CurrentPluginName'
        )->willReturn($this->testSwitchableControllerActions);
        $this->abstractConfigurationManager->expects(self::once())->method('getContextSpecificFrameworkConfiguration')->willReturnCallback(function (
            $a
        ) {
            return $a;
        });
        $mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
        $expectedResult = [
            'MyExtension\\Controller\\Controller1' => [
                'className' => 'MyExtension\\Controller\\Controller1',
                'alias' => 'Controller1',
                'actions' => ['newAction', 'action1']
            ],
            'MyExtension\\Controller\\Controller2' => [
                'className' => 'MyExtension\\Controller\\Controller2',
                'alias' => 'Controller2',
                'actions' => ['newAction2', 'action4', 'action5'],
                'nonCacheableActions' => ['action4']
            ]
        ];
        $actualResult = $mergedConfiguration['controllerConfiguration'];
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
    * @test
    */
    public function switchableControllerActionsAreNotOverriddenIfPluginNameIsSpecified(): void
    {
        /** @var AbstractConfigurationManager|MockObject|AccessibleObjectInterface $abstractConfigurationManager */
        $abstractConfigurationManager = $this->getAccessibleMock(
            AbstractConfigurationManager::class,
            [
                'overrideControllerConfigurationWithSwitchableControllerActions',
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
        $abstractConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $abstractConfigurationManager->setConfiguration(['switchableControllerActions' => ['overriddenSwitchableControllerActions']]);
        $abstractConfigurationManager->expects(self::any())->method('getPluginConfiguration')->willReturn([]);
        $abstractConfigurationManager->expects(self::never())->method('overrideControllerConfigurationWithSwitchableControllerActions');
        $abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
    }

    /**
     * @test
     */
    public function switchableControllerActionsAreOverriddenIfSpecifiedPluginIsTheCurrentPlugin(): void
    {
        /** @var AbstractConfigurationManager|MockObject|AccessibleObjectInterface $abstractConfigurationManager */
        $configuration = [
            'extensionName' => 'CurrentExtensionName',
            'pluginName' => 'CurrentPluginName',
            'switchableControllerActions' => ['overriddenSwitchableControllerActions']
        ];
        $abstractConfigurationManager = $this->getAccessibleMock(
            AbstractConfigurationManager::class,
            [
                'overrideControllerConfigurationWithSwitchableControllerActions',
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
        $this->mockTypoScriptService->expects(self::any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->willReturn($configuration);
        $abstractConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $abstractConfigurationManager->setConfiguration($configuration);
        $abstractConfigurationManager->expects(self::any())->method('getPluginConfiguration')->willReturn([]);
        $abstractConfigurationManager->expects(self::once())->method('overrideControllerConfigurationWithSwitchableControllerActions');
        $abstractConfigurationManager->getConfiguration('CurrentExtensionName', 'CurrentPluginName');
    }

    /**
     * @test
     */
    public function switchableControllerActionsAreOverriddenIfPluginNameIsNotSpecified(): void
    {
        /** @var AbstractConfigurationManager|MockObject|AccessibleObjectInterface $abstractConfigurationManager */
        $configuration = ['switchableControllerActions' => ['overriddenSwitchableControllerActions']];
        $abstractConfigurationManager = $this->getAccessibleMock(
            AbstractConfigurationManager::class,
            [
                'overrideControllerConfigurationWithSwitchableControllerActions',
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
        $this->mockTypoScriptService->expects(self::any())->method('convertTypoScriptArrayToPlainArray')->with($configuration)->willReturn($configuration);
        $abstractConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
        $abstractConfigurationManager->setConfiguration($configuration);
        $abstractConfigurationManager->expects(self::any())->method('getPluginConfiguration')->willReturn([]);
        $abstractConfigurationManager->expects(self::once())->method('overrideControllerConfigurationWithSwitchableControllerActions');
        $abstractConfigurationManager->getConfiguration();
    }
}
