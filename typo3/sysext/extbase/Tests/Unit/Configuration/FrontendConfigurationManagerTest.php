<?php
declare(strict_types = 1);

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

use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\Exception\ParseErrorException;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FrontendConfigurationManagerTest extends UnitTestCase
{
    /**
     * @var ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockContentObject;

    /**
     * @var FrontendConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
     */
    protected $frontendConfigurationManager;

    /**
     * @var TypoScriptService|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
     */
    protected $mockTypoScriptService;

    /**
     * Sets up this testcase
     */
    protected function setUp(): void
    {
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->tmpl = new \stdClass();
        $this->mockContentObject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['getTreeList'])
            ->getMock();
        $this->frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            ['dummy'],
            [],
            '',
            false
        );
        $this->frontendConfigurationManager->_set('contentObject', $this->mockContentObject);
        $this->mockTypoScriptService = $this->getAccessibleMock(TypoScriptService::class);
        $this->frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
    }

    /**
     * @test
     */
    public function getTypoScriptSetupReturnsSetupFromTsfe(): void
    {
        $GLOBALS['TSFE']->tmpl->setup = ['foo' => 'bar'];
        $this->assertEquals(['foo' => 'bar'], $this->frontendConfigurationManager->_call('getTypoScriptSetup'));
    }

    /**
     * @test
     */
    public function getPluginConfigurationReturnsEmptyArrayIfNoPluginConfigurationWasFound(): void
    {
        $GLOBALS['TSFE']->tmpl->setup = ['foo' => 'bar'];
        $expectedResult = [];
        $actualResult = $this->frontendConfigurationManager->_call(
            'getPluginConfiguration',
            'SomeExtensionName',
            'SomePluginName'
        );
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPluginConfigurationReturnsExtensionConfiguration(): void
    {
        $testSettings = [
            'settings.' => [
                'foo' => 'bar'
            ]
        ];
        $testSettingsConverted = [
            'settings' => [
                'foo' => 'bar'
            ]
        ];
        $testSetup = [
            'plugin.' => [
                'tx_someextensionname.' => $testSettings
            ]
        ];
        $this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->will($this->returnValue($testSettingsConverted));
        $GLOBALS['TSFE']->tmpl->setup = $testSetup;
        $expectedResult = [
            'settings' => [
                'foo' => 'bar'
            ]
        ];
        $actualResult = $this->frontendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPluginConfigurationReturnsPluginConfiguration(): void
    {
        $testSettings = [
            'settings.' => [
                'foo' => 'bar'
            ]
        ];
        $testSettingsConverted = [
            'settings' => [
                'foo' => 'bar'
            ]
        ];
        $testSetup = [
            'plugin.' => [
                'tx_someextensionname_somepluginname.' => $testSettings
            ]
        ];
        $this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->will($this->returnValue($testSettingsConverted));
        $GLOBALS['TSFE']->tmpl->setup = $testSetup;
        $expectedResult = [
            'settings' => [
                'foo' => 'bar'
            ]
        ];
        $actualResult = $this->frontendConfigurationManager->_call(
            'getPluginConfiguration',
            'SomeExtensionName',
            'SomePluginName'
        );
        $this->assertEquals($expectedResult, $actualResult);
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
                    'nested' => 'value'
                ]
            ]
        ];
        $testExtensionSettingsConverted = [
            'settings' => [
                'foo' => 'bar',
                'some' => [
                    'nested' => 'value'
                ]
            ]
        ];
        $testPluginSettings = [
            'settings.' => [
                'some.' => [
                    'nested' => 'valueOverridde',
                    'new' => 'value'
                ]
            ]
        ];
        $testPluginSettingsConverted = [
            'settings' => [
                'some' => [
                    'nested' => 'valueOverridde',
                    'new' => 'value'
                ]
            ]
        ];
        $testSetup = [
            'plugin.' => [
                'tx_someextensionname.' => $testExtensionSettings,
                'tx_someextensionname_somepluginname.' => $testPluginSettings
            ]
        ];
        $this->mockTypoScriptService->expects($this->at(0))->method('convertTypoScriptArrayToPlainArray')->with($testExtensionSettings)->will($this->returnValue($testExtensionSettingsConverted));
        $this->mockTypoScriptService->expects($this->at(1))->method('convertTypoScriptArrayToPlainArray')->with($testPluginSettings)->will($this->returnValue($testPluginSettingsConverted));
        $GLOBALS['TSFE']->tmpl->setup = $testSetup;
        $expectedResult = [
            'settings' => [
                'foo' => 'bar',
                'some' => [
                    'nested' => 'valueOverridde',
                    'new' => 'value'
                ]
            ]
        ];
        $actualResult = $this->frontendConfigurationManager->_call(
            'getPluginConfiguration',
            'SomeExtensionName',
            'SomePluginName'
        );
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getSwitchableControllerActionsReturnsEmptyArrayByDefault(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase'] = null;
        $expectedResult = [];
        $actualResult = $this->frontendConfigurationManager->_call(
            'getSwitchableControllerActions',
            'SomeExtensionName',
            'SomePluginName'
        );
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getSwitchableControllerActionsReturnsConfigurationStoredInExtconf(): void
    {
        $testSwitchableControllerActions = [
            'Controller1' => [
                'actions' => [
                    'action1',
                    'action2'
                ],
                'nonCacheableActions' => [
                    'action1'
                ]
            ],
            'Controller2' => [
                'actions' => [
                    'action3',
                    'action4'
                ]
            ]
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['SomeExtensionName']['plugins']['SomePluginName']['controllers'] = $testSwitchableControllerActions;
        $expectedResult = $testSwitchableControllerActions;
        $actualResult = $this->frontendConfigurationManager->_call(
            'getSwitchableControllerActions',
            'SomeExtensionName',
            'SomePluginName'
        );
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function overrideSwitchableControllerActionsFromFlexFormReturnsUnchangedFrameworkConfigurationIfNoFlexFormConfigurationIsFound(
    ): void {
        $frameworkConfiguration = [
            'pluginName' => 'Pi1',
            'extensionName' => 'SomeExtension',
            'controllerConfiguration' => [
                'Controller1' => [
                    'controller' => 'Controller1',
                    'actions' => 'action1 , action2'
                ],
                'Controller2' => [
                    'controller' => 'Controller2',
                    'actions' => 'action2 , action1,action3',
                    'nonCacheableActions' => 'action2, action3'
                ]
            ]
        ];
        $flexFormConfiguration = [];
        $actualResult = $this->frontendConfigurationManager->_call(
            'overrideSwitchableControllerActionsFromFlexForm',
            $frameworkConfiguration,
            $flexFormConfiguration
        );
        $this->assertSame($frameworkConfiguration, $actualResult);
    }

    /**
     * @test
     */
    public function overrideSwitchableControllerActionsFromFlexFormMergesNonCacheableActions(): void
    {
        $frameworkConfiguration = [
            'pluginName' => 'Pi1',
            'extensionName' => 'SomeExtension',
            'controllerConfiguration' => [
                'Controller1' => [
                    'actions' => ['action1 , action2']
                ],
                'Controller2' => [
                    'actions' => ['action2', 'action1', 'action3'],
                    'nonCacheableActions' => ['action2', 'action3']
                ]
            ]
        ];
        $flexFormConfiguration = [
            'switchableControllerActions' => 'Controller1  -> action2;Controller2->action3;  Controller2->action1'
        ];
        $expectedResult = [
            'pluginName' => 'Pi1',
            'extensionName' => 'SomeExtension',
            'controllerConfiguration' => [
                'Controller1' => [
                    'actions' => ['action2']
                ],
                'Controller2' => [
                    'actions' => ['action3', 'action1'],
                    'nonCacheableActions' => [1 => 'action3']
                ]
            ]
        ];
        $actualResult = $this->frontendConfigurationManager->_call(
            'overrideSwitchableControllerActionsFromFlexForm',
            $frameworkConfiguration,
            $flexFormConfiguration
        );
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function overrideSwitchableControllerActionsThrowsExceptionIfFlexFormConfigurationIsInvalid(): void
    {
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionCode(1257146403);
        $frameworkConfiguration = [
            'pluginName' => 'Pi1',
            'extensionName' => 'SomeExtension',
            'controllerConfiguration' => [
                'Controller1' => [
                    'actions' => ['action1 , action2']
                ],
                'Controller2' => [
                    'actions' => ['action2', 'action1', 'action3'],
                    'nonCacheableActions' => ['action2', 'action3']
                ]
            ]
        ];
        $flexFormConfiguration = [
            'switchableControllerActions' => 'Controller1->;Controller2->action3;Controller2->action1'
        ];
        $this->frontendConfigurationManager->_call(
            'overrideSwitchableControllerActionsFromFlexForm',
            $frameworkConfiguration,
            $flexFormConfiguration
        );
    }

    /**
     * @test
     */
    public function getContextSpecificFrameworkConfigurationCorrectlyCallsOverrideMethods(): void
    {
        $frameworkConfiguration = [
            'some' => [
                'framework' => 'configuration'
            ]
        ];
        /** @var FrontendConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface */
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'overrideStoragePidIfStartingPointIsSet',
                'overrideConfigurationFromPlugin',
                'overrideConfigurationFromFlexForm'
            ],
            [],
            '',
            false
        );
        $frontendConfigurationManager->expects($this->at(0))->method('overrideStoragePidIfStartingPointIsSet')->with($frameworkConfiguration)->will($this->returnValue(['overridden' => 'storagePid']));
        $frontendConfigurationManager->expects($this->at(1))->method('overrideConfigurationFromPlugin')->with(['overridden' => 'storagePid'])->will($this->returnValue(['overridden' => 'pluginConfiguration']));
        $frontendConfigurationManager->expects($this->at(2))->method('overrideConfigurationFromFlexForm')->with(['overridden' => 'pluginConfiguration'])->will($this->returnValue(['overridden' => 'flexFormConfiguration']));
        $expectedResult = ['overridden' => 'flexFormConfiguration'];
        $actualResult = $frontendConfigurationManager->_call(
            'getContextSpecificFrameworkConfiguration',
            $frameworkConfiguration
        );
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreExtendedIfRecursiveSearchIsConfigured(): void
    {
        $storagePid = '3,5,9';
        $recursive = 99;
        /** @var $abstractConfigurationManager FrontendConfigurationManager */
        $abstractConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'overrideSwitchableControllerActions',
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getSwitchableControllerActions'
            ],
            [],
            '',
            false
        );
        /** @var $cObjectMock ContentObjectRenderer */
        $cObjectMock = $this->createMock(ContentObjectRenderer::class);
        $cObjectMock->expects($this->any())
            ->method('getTreeList')
            ->will($this->onConsecutiveCalls('4', '', '898,12'));
        $abstractConfigurationManager->setContentObject($cObjectMock);

        $expectedResult = '4,898,12';
        $actualResult = $abstractConfigurationManager->_call('getRecursiveStoragePids', $storagePid, $recursive);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreExtendedIfRecursiveSearchIsConfiguredAndWithPidIncludedForNegativePid(): void
    {
        $storagePid = '-3,5,9';
        $recursive = 99;
        /** @var $abstractConfigurationManager FrontendConfigurationManager */
        $abstractConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'overrideSwitchableControllerActions',
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getSwitchableControllerActions'
            ],
            [],
            '',
            false
        );
        /** @var $cObjectMock ContentObjectRenderer */
        $cObjectMock = $this->createMock(ContentObjectRenderer::class);
        $cObjectMock->expects($this->any())
            ->method('getTreeList')
            ->will($this->onConsecutiveCalls('3,4', '', '898,12'));
        $abstractConfigurationManager->setContentObject($cObjectMock);

        $expectedResult = '3,4,898,12';
        $actualResult = $abstractConfigurationManager->_call('getRecursiveStoragePids', $storagePid, $recursive);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreNotExtendedIfRecursiveSearchIsNotConfigured(): void
    {
        $storagePid = '1,2,3';

        /** @var $abstractConfigurationManager FrontendConfigurationManager */
        $abstractConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'overrideSwitchableControllerActions',
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getSwitchableControllerActions'
            ],
            [],
            '',
            false
        );
        /** @var $cObjectMock ContentObjectRenderer */
        $cObjectMock = $this->createMock(ContentObjectRenderer::class);
        $cObjectMock->expects($this->never())->method('getTreeList');
        $abstractConfigurationManager->setContentObject($cObjectMock);

        $expectedResult = '1,2,3';
        $actualResult = $abstractConfigurationManager->_call('getRecursiveStoragePids', $storagePid);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreNotExtendedIfRecursiveSearchIsConfiguredForZeroLevels(): void
    {
        $storagePid = '1,2,3';
        $recursive = 0;

        $abstractConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            [
                'overrideSwitchableControllerActions',
                'getContextSpecificFrameworkConfiguration',
                'getTypoScriptSetup',
                'getPluginConfiguration',
                'getSwitchableControllerActions'
            ],
            [],
            '',
            false
        );

        /** @var $cObjectMock ContentObjectRenderer */
        $cObjectMock = $this->createMock(ContentObjectRenderer::class);
        $cObjectMock->expects($this->never())->method('getTreeList');
        $abstractConfigurationManager->setContentObject($cObjectMock);

        $expectedResult = '1,2,3';
        $actualResult = $abstractConfigurationManager->_call('getRecursiveStoragePids', $storagePid, $recursive);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function mergeConfigurationIntoFrameworkConfigurationWorksAsExpected(): void
    {
        $configuration = [
            'persistence' => [
                'storagePid' => '0,1,2,3'
            ]
        ];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        $this->assertSame(
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
        $this->mockContentObject->expects($this->any())->method('getTreeList')->will($this->returnValue('1,2,3'));
        $this->mockContentObject->data = ['pages' => '0', 'recursive' => 1];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        $this->assertSame(
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
    public function overrideStoragePidIfStartingPointIsSetCorrectlyHandlesEmptyValuesFromGetTreeList(): void
    {
        $this->mockContentObject->expects($this->any())->method('getTreeList')->will($this->returnValue(''));
        $this->mockContentObject->data = ['pages' => '0', 'recursive' => 1];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        $this->assertSame(
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
        /** @var $flexFormService FlexFormService|\PHPUnit_Framework_MockObject_MockObject */
        $flexFormService = $this->getMockBuilder(FlexFormService::class)
            ->setMethods(['convertFlexFormContentToArray'])
            ->getMock();
        $flexFormService->expects($this->once())->method('convertFlexFormContentToArray')->will($this->returnValue([
            'persistence' => [
                'storagePid' => '0,1,2,3'
            ]
        ]));

        $this->frontendConfigurationManager->_set('flexFormService', $flexFormService);
        $this->mockContentObject->data = ['pi_flexform' => '<XML_ARRAY>'];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        $this->assertSame(
            ['persistence' => ['storagePid' => '0,1,2,3']],
            $this->frontendConfigurationManager->_call('overrideConfigurationFromFlexForm', $frameworkConfiguration)
        );
    }

    /**
     * @test
     */
    public function overrideConfigurationFromFlexFormChecksForDataIsStringAndEmpty(): void
    {
        /** @var $flexFormService FlexFormService|\PHPUnit_Framework_MockObject_MockObject */
        $flexFormService = $this->getMockBuilder(FlexFormService::class)
            ->setMethods(['convertFlexFormContentToArray'])
            ->getMock();
        $flexFormService->expects($this->never())->method('convertFlexFormContentToArray');

        $this->frontendConfigurationManager->_set('flexFormService', $flexFormService);
        $this->mockContentObject->data = ['pi_flexform' => ''];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        $this->assertSame(
            ['persistence' => ['storagePid' => '98']],
            $this->frontendConfigurationManager->_call('overrideConfigurationFromFlexForm', $frameworkConfiguration)
        );
    }

    /**
     * @test
     */
    public function overrideConfigurationFromFlexFormChecksForDataIsArray(): void
    {
        /** @var $flexFormService FlexFormService|\PHPUnit_Framework_MockObject_MockObject */
        $flexFormService = $this->getMockBuilder(FlexFormService::class)
            ->setMethods(['convertFlexFormContentToArray'])
            ->getMock();
        $flexFormService->expects($this->never())->method('convertFlexFormContentToArray');

        $this->frontendConfigurationManager->_set('flexFormService', $flexFormService);
        $this->mockContentObject->data = ['pi_flexform' => ['persistence' => ['storagePid' => '0,1,2,3']]];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        $this->assertSame(
            ['persistence' => ['storagePid' => '0,1,2,3']],
            $this->frontendConfigurationManager->_call('overrideConfigurationFromFlexForm', $frameworkConfiguration)
        );
    }

    /**
     * @test
     */
    public function overrideConfigurationFromFlexFormChecksForDataIsArrayAndEmpty(): void
    {
        /** @var $flexFormService FlexFormService|\PHPUnit_Framework_MockObject_MockObject */
        $flexFormService = $this->getMockBuilder(FlexFormService::class)
            ->setMethods(['convertFlexFormContentToArray'])
            ->getMock();
        $flexFormService->expects($this->never())->method('convertFlexFormContentToArray');

        $this->frontendConfigurationManager->_set('flexFormService', $flexFormService);
        $this->mockContentObject->data = ['pi_flexform' => []];

        $frameworkConfiguration = ['persistence' => ['storagePid' => '98']];
        $this->assertSame(
            ['persistence' => ['storagePid' => '98']],
            $this->frontendConfigurationManager->_call('overrideConfigurationFromFlexForm', $frameworkConfiguration)
        );
    }

    /**
     * @test
     */
    public function overrideConfigurationFromPluginOverridesCorrectly(): void
    {
        /** @var $frontendConfigurationManager FrontendConfigurationManager */
        $frontendConfigurationManager = $this->getAccessibleMock(
            FrontendConfigurationManager::class,
            ['getTypoScriptSetup'],
            [],
            '',
            false
        );
        $frontendConfigurationManager->_set('contentObject', $this->mockContentObject);
        $frontendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);

        $this->mockTypoScriptService->expects($this->once())->method('convertTypoScriptArrayToPlainArray')->will($this->returnValue([
            'persistence' => [
                'storagePid' => '0,1,2,3'
            ],
            'settings' => [
                'foo' => 'bar'
            ],
            'view' => [
                'foo' => 'bar'
            ],
        ]));
        $frontendConfigurationManager->expects($this->any())->method('getTypoScriptSetup')->will($this->returnValue([
            'plugin.' => [
                'tx_ext_pi1.' => [
                    'persistence.' => [
                        'storagePid' => '0,1,2,3'
                    ],
                    'settings.' => [
                        'foo' => 'bar'
                    ],
                    'view.' => [
                        'foo' => 'bar'
                    ],
                ]
            ]
        ]));

        $frameworkConfiguration = [
            'extensionName' => 'ext',
            'pluginName' => 'pi1',
            'persistence' => [
                'storagePid' => '1'
            ],
            'settings' => [
                'foo' => 'qux'
            ],
            'view' => [
                'foo' => 'qux'
            ],
        ];
        $this->assertSame(
            [
                'extensionName' => 'ext',
                'pluginName' => 'pi1',
                'persistence' => [
                    'storagePid' => '0,1,2,3',
                ],
                'settings' => [
                    'foo' => 'bar'
                ],
                'view' => [
                    'foo' => 'bar'
                ],
            ],
            $frontendConfigurationManager->_call('overrideConfigurationFromPlugin', $frameworkConfiguration)
        );
    }
}
