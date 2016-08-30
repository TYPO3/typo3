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
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Test case
 */
class BackendConfigurationManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $backendConfigurationManager;

    /**
     * @var \TYPO3\CMS\Extbase\Service\TypoScriptService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $mockTypoScriptService;

    /**
     * Sets up this testcase
     */
    protected function setUp()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, []);
        $this->backendConfigurationManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager::class, ['getTypoScriptSetup']);
        $this->mockTypoScriptService = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Service\TypoScriptService::class);
        $this->backendConfigurationManager->_set('typoScriptService', $this->mockTypoScriptService);
    }

    /**
     * @test
     */
    public function getCurrentPageIdReturnsPageIdFromGet()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::_GETset(['id' => 123]);
        $expectedResult = 123;
        $actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getCurrentPageIdReturnsPageIdFromPost()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::_GETset(['id' => 123]);
        $_POST['id'] = 321;
        $expectedResult = 321;
        $actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getCurrentPageIdReturnsPidFromFirstRootTemplateIfIdIsNotSetAndNoRootPageWasFound()
    {
        $GLOBALS['TYPO3_DB']->expects($this->at(0))->method('exec_SELECTgetSingleRow')->with('uid', 'pages', 'deleted=0 AND hidden=0 AND is_siteroot=1', '', 'sorting')->will($this->returnValue([]));
        $GLOBALS['TYPO3_DB']->expects($this->at(1))->method('exec_SELECTgetSingleRow')->with('pid', 'sys_template', 'deleted=0 AND hidden=0 AND root=1', '', 'crdate')->will($this->returnValue(
            ['pid' => 123]
        ));
        $expectedResult = 123;
        $actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getCurrentPageIdReturnsUidFromFirstRootPageIfIdIsNotSet()
    {
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_SELECTgetSingleRow')->with('uid', 'pages', 'deleted=0 AND hidden=0 AND is_siteroot=1', '', 'sorting')->will($this->returnValue(
            ['uid' => 321]
        ));
        $expectedResult = 321;
        $actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getCurrentPageIdReturnsDefaultStoragePidIfIdIsNotSetNoRootTemplateAndRootPageWasFound()
    {
        $GLOBALS['TYPO3_DB']->expects($this->at(0))->method('exec_SELECTgetSingleRow')->with('uid', 'pages', 'deleted=0 AND hidden=0 AND is_siteroot=1', '', 'sorting')->will($this->returnValue([]));
        $GLOBALS['TYPO3_DB']->expects($this->at(1))->method('exec_SELECTgetSingleRow')->with('pid', 'sys_template', 'deleted=0 AND hidden=0 AND root=1', '', 'crdate')->will($this->returnValue([]));
        $expectedResult = \TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager::DEFAULT_BACKEND_STORAGE_PID;
        $actualResult = $this->backendConfigurationManager->_call('getCurrentPageId');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPluginConfigurationReturnsEmptyArrayIfNoPluginConfigurationWasFound()
    {
        $this->backendConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue(['foo' => 'bar']));
        $expectedResult = [];
        $actualResult = $this->backendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPluginConfigurationReturnsExtensionConfiguration()
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
            'module.' => [
                'tx_someextensionname.' => $testSettings
            ]
        ];
        $this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->will($this->returnValue($testSettingsConverted));
        $this->backendConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue($testSetup));
        $expectedResult = [
            'settings' => [
                'foo' => 'bar'
            ]
        ];
        $actualResult = $this->backendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPluginConfigurationReturnsPluginConfiguration()
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
            'module.' => [
                'tx_someextensionname_somepluginname.' => $testSettings
            ]
        ];
        $this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->will($this->returnValue($testSettingsConverted));
        $this->backendConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue($testSetup));
        $expectedResult = [
            'settings' => [
                'foo' => 'bar'
            ]
        ];
        $actualResult = $this->backendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPluginConfigurationRecursivelyMergesExtensionAndPluginConfiguration()
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
            'module.' => [
                'tx_someextensionname.' => $testExtensionSettings,
                'tx_someextensionname_somepluginname.' => $testPluginSettings
            ]
        ];
        $this->mockTypoScriptService->expects($this->at(0))->method('convertTypoScriptArrayToPlainArray')->with($testExtensionSettings)->will($this->returnValue($testExtensionSettingsConverted));
        $this->mockTypoScriptService->expects($this->at(1))->method('convertTypoScriptArrayToPlainArray')->with($testPluginSettings)->will($this->returnValue($testPluginSettingsConverted));
        $this->backendConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue($testSetup));
        $expectedResult = [
            'settings' => [
                'foo' => 'bar',
                'some' => [
                    'nested' => 'valueOverridde',
                    'new' => 'value'
                ]
            ]
        ];
        $actualResult = $this->backendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getSwitchableControllerActionsReturnsEmptyArrayByDefault()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase'] = null;
        $expectedResult = [];
        $actualResult = $this->backendConfigurationManager->_call('getSwitchableControllerActions', 'SomeExtensionName', 'SomePluginName');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getSwitchableControllerActionsReturnsConfigurationStoredInExtconf()
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
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['SomeExtensionName']['modules']['SomePluginName']['controllers'] = $testSwitchableControllerActions;
        $expectedResult = $testSwitchableControllerActions;
        $actualResult = $this->backendConfigurationManager->_call('getSwitchableControllerActions', 'SomeExtensionName', 'SomePluginName');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getContextSpecificFrameworkConfigurationReturnsUnmodifiedFrameworkConfigurationIfRequestHandlersAreConfigured()
    {
        $frameworkConfiguration = [
            'pluginName' => 'Pi1',
            'extensionName' => 'SomeExtension',
            'foo' => [
                'bar' => [
                    'baz' => 'Foo'
                ]
            ],
            'mvc' => [
                'requestHandlers' => [
                    \TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler::class => 'SomeRequestHandler'
                ]
            ]
        ];
        $expectedResult = $frameworkConfiguration;
        $actualResult = $this->backendConfigurationManager->_call('getContextSpecificFrameworkConfiguration', $frameworkConfiguration);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getContextSpecificFrameworkConfigurationSetsDefaultRequestHandlersIfRequestHandlersAreNotConfigured()
    {
        $frameworkConfiguration = [
            'pluginName' => 'Pi1',
            'extensionName' => 'SomeExtension',
            'foo' => [
                'bar' => [
                    'baz' => 'Foo'
                ]
            ]
        ];
        $expectedResult = [
            'pluginName' => 'Pi1',
            'extensionName' => 'SomeExtension',
            'foo' => [
                'bar' => [
                    'baz' => 'Foo'
                ]
            ],
            'mvc' => [
                'requestHandlers' => [
                    \TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler::class => \TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler::class,
                    \TYPO3\CMS\Extbase\Mvc\Web\BackendRequestHandler::class => \TYPO3\CMS\Extbase\Mvc\Web\BackendRequestHandler::class
                ]
            ]
        ];
        $actualResult = $this->backendConfigurationManager->_call('getContextSpecificFrameworkConfiguration', $frameworkConfiguration);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreExtendedIfRecursiveSearchIsConfigured()
    {
        $storagePid = '1,2,3';
        $recursive = 99;

        /** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|ObjectProphecy $beUserAuthentication */
        $beUserAuthentication = $this->prophesize(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class);
        $beUserAuthentication->getPagePermsClause(1)->willReturn('1=1');
        $GLOBALS['BE_USER'] = $beUserAuthentication->reveal();

        /** @var $abstractConfigurationManager \TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager */
        $abstractConfigurationManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager::class, ['overrideSwitchableControllerActions', 'getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions']);
        $queryGenerator = $this->getMock(\TYPO3\CMS\Core\Database\QueryGenerator::class);
        $queryGenerator->expects($this->any())
            ->method('getTreeList')
            ->will($this->onConsecutiveCalls('4', '', '5,6'));
        $abstractConfigurationManager->_set('queryGenerator', $queryGenerator);

        $expectedResult = '4,5,6';
        $actualResult = $abstractConfigurationManager->_call('getRecursiveStoragePids', $storagePid, $recursive);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreExtendedIfRecursiveSearchIsConfiguredAndWithPidIncludedForNegativePid()
    {
        $storagePid = '1,2,-3';
        $recursive = 99;

        /** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|ObjectProphecy $beUserAuthentication */
        $beUserAuthentication = $this->prophesize(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class);
        $beUserAuthentication->getPagePermsClause(1)->willReturn('1=1');
        $GLOBALS['BE_USER'] = $beUserAuthentication->reveal();

        /** @var $abstractConfigurationManager \TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager */
        $abstractConfigurationManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager::class, ['overrideSwitchableControllerActions', 'getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions']);
        $queryGenerator = $this->getMock(\TYPO3\CMS\Core\Database\QueryGenerator::class);
        $queryGenerator->expects($this->any())
            ->method('getTreeList')
            ->will($this->onConsecutiveCalls('4', '', '3,5,6'));
        $abstractConfigurationManager->_set('queryGenerator', $queryGenerator);

        $expectedResult = '4,3,5,6';
        $actualResult = $abstractConfigurationManager->_call('getRecursiveStoragePids', $storagePid, $recursive);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreNotExtendedIfRecursiveSearchIsNotConfigured()
    {
        $storagePid = '1,2,3';

        $abstractConfigurationManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager::class, ['overrideSwitchableControllerActions', 'getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions']);

        $queryGenerator = $this->getMock(\TYPO3\CMS\Core\Database\QueryGenerator::class);
        $queryGenerator->expects($this->never())->method('getTreeList');
        $abstractConfigurationManager->_set('queryGenerator', $queryGenerator);

        $expectedResult = '1,2,3';
        $actualResult = $abstractConfigurationManager->_call('getRecursiveStoragePids', $storagePid);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storagePidsAreNotExtendedIfRecursiveSearchIsConfiguredForZeroLevels()
    {
        $storagePid = '1,2,3';
        $recursive = 0;

        $abstractConfigurationManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager::class, ['overrideSwitchableControllerActions', 'getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions']);

        $queryGenerator = $this->getMock(\TYPO3\CMS\Core\Database\QueryGenerator::class);
        $queryGenerator->expects($this->never())->method('getTreeList');
        $abstractConfigurationManager->_set('queryGenerator', $queryGenerator);

        $expectedResult = '1,2,3';
        $actualResult = $abstractConfigurationManager->_call('getRecursiveStoragePids', $storagePid, $recursive);
        $this->assertEquals($expectedResult, $actualResult);
    }
}
