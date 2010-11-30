<?php
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

class Tx_Extbase_Configuration_FrontendConfigurationManagerTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var tslib_fe
	 */
	protected $tsfeBackup;

	/**
	 * @var tslib_cObj
	 */
	protected $mockContentObject;

	/**
	 * @var Tx_Extbase_Configuration_FrontendConfigurationManager
	 */
	protected $frontendConfigurationManager;

	/**
	 * @var array
	 */
	protected $extConfBackup;

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		$this->tsfeBackup = $GLOBALS['TSFE'];
		$this->mockContentObject = $this->getMock('tslib_cObj');
		$this->extConfBackup = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase'];
		$this->frontendConfigurationManager = $this->getAccessibleMock('Tx_Extbase_Configuration_FrontendConfigurationManager', array('dummy'));
		$this->frontendConfigurationManager->_set('contentObject', $this->mockContentObject);
	}

	/**
	 * Tears down this testcase
	 */
	public function tearDown() {
		$GLOBALS['TSFE']->tmpl->setup;
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase'] = $this->extConfBackup;
	}

	/**
	 * @test
	 */
	public function getTypoScriptSetupReturnsSetupFromTSFE() {
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
		$testSetup = array(
			'plugin.' => array(
				'tx_someextensionname.' => array(
					'settings.' => array(
						'foo' => 'bar'
					)
				),
			),
		);
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
	public function getPluginConfigurationReturnsPluginConfiguration() {
		$testSetup = array(
			'plugin.' => array(
				'tx_someextensionname_somepluginname.' => array(
					'settings.' => array(
						'foo' => 'bar'
					)
				),
			),
		);
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
		$testSetup = array(
			'plugin.' => array(
				'tx_someextensionname.' => array(
					'settings.' => array(
						'foo' => 'bar',
						'some.' => array(
							'nested' => 'value'
						),
					),
				),
				'tx_someextensionname_somepluginname.' => array(
					'settings.' => array(
						'some.' => array(
							'nested' => 'valueOverridde',
							'new' => 'value',
						),
					),
				),
			),
		);
		$GLOBALS['TSFE']->tmpl->setup = $testSetup;
		$expectedResult = array(
			'settings' => array(
				'foo' => 'bar',
				'some' => array(
					'nested' => 'valueOverridde',
					'new' => 'value'
				),
			),
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
					'action1', 'action2'
				),
				'nonCacheableActions' => array(
					'action1'
				),
			),
			'Controller2' => array(
				'actions' => array(
					'action3', 'action4'
				),
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
	public function overrideSwitchableControllerActionsFromFlexformReturnsUnchangedFrameworkConfigurationIfNoFlexformConfigurationIsFound() {
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
		$flexformConfiguration = array();
		$actualResult = $this->frontendConfigurationManager->_call('overrideSwitchableControllerActionsFromFlexform', $frameworkConfiguration, $flexformConfiguration);
		$this->assertSame($frameworkConfiguration, $actualResult);
	}

	/**
	 * @test
	 */
	public function overrideSwitchableControllerActionsFromFlexformMergesNonCacheableActions() {
		$frameworkConfiguration = array(
			'pluginName' => 'Pi1',
			'extensionName' => 'SomeExtension',
			'controllerConfiguration' => array(
				'Controller1' => array(
					'actions' => array('action1 , action2')
				),
				'Controller2' => array(
					'actions' => array('action2', 'action1','action3'),
					'nonCacheableActions' => array('action2', 'action3')
				)
			)
		);
		$flexformConfiguration = array(
			'switchableControllerActions' => 'Controller1  -> action2;Controller2->action3;  Controller2->action1'
		);
		$expectedResult = array(
			'pluginName' => 'Pi1',
			'extensionName' => 'SomeExtension',
			'controllerConfiguration' => array(
				'Controller1' => array(
					'actions' => array('action2'),
				),
				'Controller2' => array(
					'actions' => array('action3', 'action1'),
					'nonCacheableActions' => array(1 => 'action3'),
				)
			)
		);
		$actualResult = $this->frontendConfigurationManager->_call('overrideSwitchableControllerActionsFromFlexform', $frameworkConfiguration, $flexformConfiguration);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Configuration_Exception_ParseError
	 */
	public function overrideSwitchableControllerActionsThrowsExceptionIfFlexformConfigurationIsInvalid() {
		$frameworkConfiguration = array(
			'pluginName' => 'Pi1',
			'extensionName' => 'SomeExtension',
			'controllerConfiguration' => array(
				'Controller1' => array(
					'actions' => array('action1 , action2')
				),
				'Controller2' => array(
					'actions' => array('action2', 'action1','action3'),
					'nonCacheableActions' => array('action2', 'action3')
				)
			)
		);
		$flexformConfiguration = array(
			'switchableControllerActions' => 'Controller1->;Controller2->action3;Controller2->action1'
		);
		$this->frontendConfigurationManager->_call('overrideSwitchableControllerActionsFromFlexform', $frameworkConfiguration, $flexformConfiguration);
	}

	/**
	 * @test
	 */
	public function getContextSpecificFrameworkConfigurationCorrectlyCallsOverrideMethods() {
		$frameworkConfiguration = array(
			'some' => array(
				'framework' => 'configuration'
			),
		);
		$frontendConfigurationManager = $this->getAccessibleMock('Tx_Extbase_Configuration_FrontendConfigurationManager', array('overrideStoragePidIfStartingPointIsSet', 'overrideConfigurationFromPlugin', 'overrideConfigurationFromFlexform'));
		$frontendConfigurationManager->expects($this->at(0))->method('overrideStoragePidIfStartingPointIsSet')->with($frameworkConfiguration)->will($this->returnValue(array('overridden' => 'storagePid')));
		$frontendConfigurationManager->expects($this->at(1))->method('overrideConfigurationFromPlugin')->with(array('overridden' => 'storagePid'))->will($this->returnValue(array('overridden' => 'pluginConfiguration')));
		$frontendConfigurationManager->expects($this->at(2))->method('overrideConfigurationFromFlexform')->with(array('overridden' => 'pluginConfiguration'))->will($this->returnValue(array('overridden' => 'flexformConfiguration')));
		$expectedResult = array('overridden' => 'flexformConfiguration');
		$actualResult = $frontendConfigurationManager->_call('getContextSpecificFrameworkConfiguration', $frameworkConfiguration);
		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>