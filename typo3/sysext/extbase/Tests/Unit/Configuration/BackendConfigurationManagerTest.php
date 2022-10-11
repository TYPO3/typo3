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
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class BackendConfigurationManagerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private array $testTypoScriptSetup = [
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

    private array $testPluginConfiguration = [
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
     * @test
     */
    public function setConfigurationResetsConfigurationCache(): void
    {
        $subject = $this->getAccessibleMock(BackendConfigurationManager::class, null, [new TypoScriptService()], '', true);
        $subject->_set('configurationCache', ['foo' => 'bar']);
        $subject->setConfiguration([]);
        self::assertEquals([], $subject->_get('configurationCache'));
    }

    /**
     * @test
     */
    public function setConfigurationSetsExtensionAndPluginName(): void
    {
        $subject = $this->getAccessibleMock(BackendConfigurationManager::class, null, [new TypoScriptService()], '', true);
        $subject->setConfiguration([
            'extensionName' => 'SomeExtensionName',
            'pluginName' => 'SomePluginName',
        ]);
        self::assertEquals('SomeExtensionName', $subject->_get('extensionName'));
        self::assertEquals('SomePluginName', $subject->_get('pluginName'));
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
        $subject = $this->getAccessibleMock(BackendConfigurationManager::class, null, [new TypoScriptService()], '', true);
        $subject->setConfiguration($configuration);
        self::assertEquals($expectedResult, $subject->_get('configuration'));
    }

    /**
     * @test
     */
    public function getConfigurationReturnsCachedResultOfCurrentPlugin(): void
    {
        $subject = $this->getAccessibleMock(BackendConfigurationManager::class, null, [], '', false);
        $subject->_set('extensionName', 'CurrentExtensionName');
        $subject->_set('pluginName', 'CurrentPluginName');
        $subject->_set('configurationCache', [
            'currentextensionname_currentpluginname' => ['foo' => 'bar'],
            'someotherextension_somepluginname' => ['baz' => 'shouldnotbereturned'],
        ]);
        $expectedResult = ['foo' => 'bar'];
        $actualResult = $subject->getConfiguration();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationReturnsCachedResultForGivenExtension(): void
    {
        $subject = $this->getAccessibleMock(BackendConfigurationManager::class, null, [], '', false);
        $subject->_set('configurationCache', [
            'someextensionname_somepluginname' => ['foo' => 'bar'],
            'someotherextension_somepluginname' => ['baz' => 'shouldnotbereturned'],
        ]);
        $expectedResult = ['foo' => 'bar'];
        $actualResult = $subject->getConfiguration('SomeExtensionName', 'SomePluginName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationRecursivelyMergesCurrentPluginConfigurationWithFrameworkConfiguration(): void
    {
        $subject = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            [
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getDefaultBackendStoragePid',
            ],
            [new TypoScriptService()],
            '',
            true
        );
        $subject->_set('extensionName', 'CurrentExtensionName');
        $subject->_set('pluginName', 'CurrentPluginName');
        $subject->expects(self::once())->method('getTypoScriptSetup')->willReturn($this->testTypoScriptSetup);
        $subject->expects(self::once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->willReturn($this->testPluginConfiguration);
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
        $actualResult = $subject->getConfiguration();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getConfigurationStoresResultInConfigurationCache(): void
    {
        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            [
                'getTypoScriptSetup',
                'getDefaultBackendStoragePid',
            ],
            [new TypoScriptService()],
            '',
            true
        );
        $backendConfigurationManager->_set('extensionName', 'CurrentExtensionName');
        $backendConfigurationManager->_set('pluginName', 'CurrentPluginName');
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
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getRecursiveStoragePids',
                'getDefaultBackendStoragePid',
            ],
            [new TypoScriptService()],
            '',
            true
        );
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
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getRecursiveStoragePids',
                'getDefaultBackendStoragePid',
            ],
            [new TypoScriptService()],
            '',
            true
        );
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
        self::assertInstanceOf(ContentObjectRenderer::class, (new BackendConfigurationManager(new TypoScriptService()))->getContentObject());
    }

    /**
     * @test
     */
    public function getContentObjectTheCurrentContentObject(): void
    {
        $subject = new BackendConfigurationManager(new TypoScriptService());
        $mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $subject->setContentObject($mockContentObject);
        self::assertSame($mockContentObject, $subject->getContentObject());
    }

    /**
     * @test
     */
    public function getCurrentPageIdReturnsPageIdFromGet(): void
    {
        $_GET['id'] = 123;
        $subject = $this->getAccessibleMock(BackendConfigurationManager::class, null, [], '', false);
        $actualResult = $subject->_call('getCurrentPageId');
        self::assertEquals(123, $actualResult);
    }

    /**
     * @test
     */
    public function getCurrentPageIdReturnsPageIdFromPost(): void
    {
        $_GET['id'] = 123;
        $_POST['id'] = 321;
        $subject = $this->getAccessibleMock(BackendConfigurationManager::class, null, [], '', false);
        $actualResult = $subject->_call('getCurrentPageId');
        self::assertEquals(321, $actualResult);
    }

    /**
     * @test
     */
    public function getPluginConfigurationReturnsEmptyArrayIfNoPluginConfigurationWasFound(): void
    {
        $subject = $this->getAccessibleMock(BackendConfigurationManager::class, ['getTypoScriptSetup'], [], '', false);
        $subject->expects(self::once())->method('getTypoScriptSetup')->willReturn(['foo' => 'bar']);
        $expectedResult = [];
        $actualResult = $subject->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
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
        $testSetup = [
            'module.' => [
                'tx_someextensionname.' => $testSettings,
            ],
        ];
        $subject = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            ['getTypoScriptSetup'],
            [new TypoScriptService()],
            '',
            true
        );
        $subject->expects(self::once())->method('getTypoScriptSetup')->willReturn($testSetup);
        $expectedResult = [
            'settings' => [
                'foo' => 'bar',
            ],
        ];

        $actualResult = $subject->_call('getPluginConfiguration', 'SomeExtensionName');
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
        $testSetup = [
            'module.' => [
                'tx_someextensionname_somepluginname.' => $testSettings,
            ],
        ];
        $subject = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            ['getTypoScriptSetup'],
            [new TypoScriptService()],
            '',
            true
        );
        $subject->expects(self::once())->method('getTypoScriptSetup')->willReturn($testSetup);
        $expectedResult = [
            'settings' => [
                'foo' => 'bar',
            ],
        ];
        $actualResult = $subject->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
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
        $typoScriptServiceMock = $this->getMockBuilder(TypoScriptService::class)->getMock();
        $typoScriptServiceMock->expects(self::exactly(2))->method('convertTypoScriptArrayToPlainArray')
            ->withConsecutive([$testExtensionSettings], [$testPluginSettings])
            ->willReturnOnConsecutiveCalls($testExtensionSettingsConverted, $testPluginSettingsConverted);
        $subject = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            ['getTypoScriptSetup'],
            [$typoScriptServiceMock],
            '',
            true
        );
        $subject->expects(self::once())->method('getTypoScriptSetup')->willReturn($testSetup);
        $expectedResult = [
            'settings' => [
                'foo' => 'bar',
                'some' => [
                    'nested' => 'valueOverride',
                    'new' => 'value',
                ],
            ],
        ];
        $actualResult = $subject->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
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
            ['getTypoScriptSetup', 'getPluginConfiguration'],
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
        $backendConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            ['getTypoScriptSetup', 'getPluginConfiguration'],
            [],
            '',
            false
        );
        $expectedResult = [1, 2, 3];
        $actualResult = $backendConfigurationManager->_call('getRecursiveStoragePids', $storagePids, 0);
        self::assertEquals($expectedResult, $actualResult);
    }
}
