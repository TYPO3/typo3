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
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendConfigurationManagerTest extends UnitTestCase
{
    /**
     * @var BackendConfigurationManager|MockObject|AccessibleObjectInterface
     */
    protected $backendConfigurationManager;

    /**
     * @var TypoScriptService|MockObject|AccessibleObjectInterface
     */
    protected $mockTypoScriptService;

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
        $this->mockTypoScriptService->expects(self::any())->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->willReturn($testSettingsConverted);
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
        $this->mockTypoScriptService->expects(self::any())->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->willReturn($testSettingsConverted);
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
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['SomeExtensionName']['modules']['SomePluginName']['controllers'] = $controllerConfiguration;
        $expectedResult = $controllerConfiguration;
        $actualResult = $this->backendConfigurationManager->_call('getControllerConfiguration', 'SomeExtensionName', 'SomePluginName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreNotExtendedIfRecursiveSearchIsNotConfigured(): void
    {
        $storagePids = [1, 2, 3];

        $abstractConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            ['getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getControllerConfiguration'],
            [],
            '',
            false
        );

        $expectedResult = [1, 2, 3];
        $actualResult = $abstractConfigurationManager->_call('getRecursiveStoragePids', $storagePids);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreNotExtendedIfRecursiveSearchIsConfiguredForZeroLevels(): void
    {
        $storagePids = [1, 2, 3];
        $recursive = 0;

        $abstractConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            ['getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getControllerConfiguration'],
            [],
            '',
            false
        );

        $expectedResult = [1, 2, 3];
        $actualResult = $abstractConfigurationManager->_call('getRecursiveStoragePids', $storagePids, $recursive);
        self::assertEquals($expectedResult, $actualResult);
    }
}
