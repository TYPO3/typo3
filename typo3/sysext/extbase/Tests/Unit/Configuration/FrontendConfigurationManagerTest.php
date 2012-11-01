<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of FLOW3.
 *  All credits go to the v5 team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class FrontendConfigurationManagerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $mockContentObject;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $frontendConfigurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\TypoScriptService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $mockTypoScriptService;

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		$GLOBALS['TSFE'] = new \stdClass();
		$GLOBALS['TSFE']->tmpl = new \stdClass();
		$this->mockContentObject = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$this->frontendConfigurationManager = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Configuration\\FrontendConfigurationManager', array('dummy'));
		$this->frontendConfigurationManager->_set('contentObject', $this->mockContentObject);
		$this->mockTypoScriptService = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService');
		$this->frontendConfigurationManager->injectTypoScriptService($this->mockTypoScriptService);
	}

	/**
	 * @test
	 */
	public function getTypoScriptSetupReturnsSetupFromTsfe() {
		$GLOBALS['TSFE']->tmpl->setup = array('foo' => 'bar');
		$this->assertEquals(array('foo' => 'bar'), $this->frontendConfigurationManager->_call('getTypoScriptSetup'));
	}

	/**
	 * @test
	 */
	public function getPluginConfigurationReturnsEmptyArrayIfNoPluginConfigurationWasFound() {
		$GLOBALS['TSFE']->tmpl->setup = array('foo' => 'bar');
		$expectedResult = array();
		$actualResult = $this->frontendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getPluginConfigurationReturnsExtensionConfiguration() {
		$testSettings = array(
			'settings.' => array(
				'foo' => 'bar'
			)
		);
		$testSettingsConverted = array(
			'settings' => array(
				'foo' => 'bar'
			)
		);
		$testSetup = array(
			'plugin.' => array(
				'tx_someextensionname.' => $testSettings
			)
		);
		$this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->will($this->returnValue($testSettingsConverted));
		$GLOBALS['TSFE']->tmpl->setup = $testSetup;
		$expectedResult = array(
			'settings' => array(
				'foo' => 'bar'
			)
		);
		$actualResult = $this->frontendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getPluginConfigurationReturnsPluginConfiguration() {
		$testSettings = array(
			'settings.' => array(
				'foo' => 'bar'
			)
		);
		$testSettingsConverted = array(
			'settings' => array(
				'foo' => 'bar'
			)
		);
		$testSetup = array(
			'plugin.' => array(
				'tx_someextensionname_somepluginname.' => $testSettings
			)
		);
		$this->mockTypoScriptService->expects($this->any())->method('convertTypoScriptArrayToPlainArray')->with($testSettings)->will($this->returnValue($testSettingsConverted));
		$GLOBALS['TSFE']->tmpl->setup = $testSetup;
		$expectedResult = array(
			'settings' => array(
				'foo' => 'bar'
			)
		);
		$actualResult = $this->frontendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getPluginConfigurationRecursivelyMergesExtensionAndPluginConfiguration() {
		$testExtensionSettings = array(
			'settings.' => array(
				'foo' => 'bar',
				'some.' => array(
					'nested' => 'value'
				)
			)
		);
		$testExtensionSettingsConverted = array(
			'settings' => array(
				'foo' => 'bar',
				'some' => array(
					'nested' => 'value'
				)
			)
		);
		$testPluginSettings = array(
			'settings.' => array(
				'some.' => array(
					'nested' => 'valueOverridde',
					'new' => 'value'
				)
			)
		);
		$testPluginSettingsConverted = array(
			'settings' => array(
				'some' => array(
					'nested' => 'valueOverridde',
					'new' => 'value'
				)
			)
		);
		$testSetup = array(
			'plugin.' => array(
				'tx_someextensionname.' => $testExtensionSettings,
				'tx_someextensionname_somepluginname.' => $testPluginSettings
			)
		);
		$this->mockTypoScriptService->expects($this->at(0))->method('convertTypoScriptArrayToPlainArray')->with($testExtensionSettings)->will($this->returnValue($testExtensionSettingsConverted));
		$this->mockTypoScriptService->expects($this->at(1))->method('convertTypoScriptArrayToPlainArray')->with($testPluginSettings)->will($this->returnValue($testPluginSettingsConverted));
		$GLOBALS['TSFE']->tmpl->setup = $testSetup;
		$expectedResult = array(
			'settings' => array(
				'foo' => 'bar',
				'some' => array(
					'nested' => 'valueOverridde',
					'new' => 'value'
				)
			)
		);
		$actualResult = $this->frontendConfigurationManager->_call('getPluginConfiguration', 'SomeExtensionName', 'SomePluginName');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getSwitchableControllerActionsReturnsEmptyArrayByDefault() {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase'] = NULL;
		$expectedResult = array();
		$actualResult = $this->frontendConfigurationManager->_call('getSwitchableControllerActions', 'SomeExtensionName', 'SomePluginName');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getSwitchableControllerActionsReturnsConfigurationStoredInExtconf() {
		$testSwitchableControllerActions = array(
			'Controller1' => array(
				'actions' => array(
					'action1',
					'action2'
				),
				'nonCacheableActions' => array(
					'action1'
				)
			),
			'Controller2' => array(
				'actions' => array(
					'action3',
					'action4'
				)
			)
		);
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['SomeExtensionName']['plugins']['SomePluginName']['controllers'] = $testSwitchableControllerActions;
		$expectedResult = $testSwitchableControllerActions;
		$actualResult = $this->frontendConfigurationManager->_call('getSwitchableControllerActions', 'SomeExtensionName', 'SomePluginName');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function overrideSwitchableControllerActionsFromFlexFormReturnsUnchangedFrameworkConfigurationIfNoFlexFormConfigurationIsFound() {
		$frameworkConfiguration = array(
			'pluginName' => 'Pi1',
			'extensionName' => 'SomeExtension',
			'controllerConfiguration' => array(
				'Controller1' => array(
					'controller' => 'Controller1',
					'actions' => 'action1 , action2'
				),
				'Controller2' => array(
					'controller' => 'Controller2',
					'actions' => 'action2 , action1,action3',
					'nonCacheableActions' => 'action2, action3'
				)
			)
		);
		$flexFormConfiguration = array();
		$actualResult = $this->frontendConfigurationManager->_call('overrideSwitchableControllerActionsFromFlexForm', $frameworkConfiguration, $flexFormConfiguration);
		$this->assertSame($frameworkConfiguration, $actualResult);
	}

	/**
	 * @test
	 */
	public function overrideSwitchableControllerActionsFromFlexFormMergesNonCacheableActions() {
		$frameworkConfiguration = array(
			'pluginName' => 'Pi1',
			'extensionName' => 'SomeExtension',
			'controllerConfiguration' => array(
				'Controller1' => array(
					'actions' => array('action1 , action2')
				),
				'Controller2' => array(
					'actions' => array('action2', 'action1', 'action3'),
					'nonCacheableActions' => array('action2', 'action3')
				)
			)
		);
		$flexFormConfiguration = array(
			'switchableControllerActions' => 'Controller1  -> action2;Controller2->action3;  Controller2->action1'
		);
		$expectedResult = array(
			'pluginName' => 'Pi1',
			'extensionName' => 'SomeExtension',
			'controllerConfiguration' => array(
				'Controller1' => array(
					'actions' => array('action2')
				),
				'Controller2' => array(
					'actions' => array('action3', 'action1'),
					'nonCacheableActions' => array(1 => 'action3')
				)
			)
		);
		$actualResult = $this->frontendConfigurationManager->_call('overrideSwitchableControllerActionsFromFlexForm', $frameworkConfiguration, $flexFormConfiguration);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Configuration\Exception\ParseErrorException
	 */
	public function overrideSwitchableControllerActionsThrowsExceptionIfFlexFormConfigurationIsInvalid() {
		$frameworkConfiguration = array(
			'pluginName' => 'Pi1',
			'extensionName' => 'SomeExtension',
			'controllerConfiguration' => array(
				'Controller1' => array(
					'actions' => array('action1 , action2')
				),
				'Controller2' => array(
					'actions' => array('action2', 'action1', 'action3'),
					'nonCacheableActions' => array('action2', 'action3')
				)
			)
		);
		$flexFormConfiguration = array(
			'switchableControllerActions' => 'Controller1->;Controller2->action3;Controller2->action1'
		);
		$this->frontendConfigurationManager->_call('overrideSwitchableControllerActionsFromFlexForm', $frameworkConfiguration, $flexFormConfiguration);
	}

	/**
	 * @test
	 */
	public function getContextSpecificFrameworkConfigurationCorrectlyCallsOverrideMethods() {
		$frameworkConfiguration = array(
			'some' => array(
				'framework' => 'configuration'
			)
		);
		/** @var \TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$frontendConfigurationManager = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Configuration\\FrontendConfigurationManager', array('overrideStoragePidIfStartingPointIsSet', 'overrideConfigurationFromPlugin', 'overrideConfigurationFromFlexForm'));
		$frontendConfigurationManager->expects($this->at(0))->method('overrideStoragePidIfStartingPointIsSet')->with($frameworkConfiguration)->will($this->returnValue(array('overridden' => 'storagePid')));
		$frontendConfigurationManager->expects($this->at(1))->method('overrideConfigurationFromPlugin')->with(array('overridden' => 'storagePid'))->will($this->returnValue(array('overridden' => 'pluginConfiguration')));
		$frontendConfigurationManager->expects($this->at(2))->method('overrideConfigurationFromFlexForm')->with(array('overridden' => 'pluginConfiguration'))->will($this->returnValue(array('overridden' => 'flexFormConfiguration')));
		$expectedResult = array('overridden' => 'flexFormConfiguration');
		$actualResult = $frontendConfigurationManager->_call('getContextSpecificFrameworkConfiguration', $frameworkConfiguration);
		$this->assertEquals($expectedResult, $actualResult);
	}

}


?>