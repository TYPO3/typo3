<?php
/***************************************************************
*  Copyright notice
*
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

class Tx_Extbase_Tests_Unit_Configuration_AbstractConfigurationManagerTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_Configuration_AbstractConfigurationManager
	 */
	protected $abstractConfigurationManager;

	/**
	 * @var array
	 */
	protected $testTypoScriptSetup = array(
		'foo.' => array(
			'bar' => 'baz'
		),
		'config.' => array(
			'tx_extbase.' => array(
				'settings.' => array(
					'setting1' => 'value1',
					'setting2' => 'value2',
				),
				'view.' => array(
					'viewSub.' => array(
						'key1' => 'value1',
						'key2' => 'value2',
					)
				)
			),
		),
	);

	/**
	 * @var array
	 */
	protected $testPluginConfiguration = array(
		'settings' => array(
			'setting1' => 'overriddenValue1',
			'setting3' => 'additionalValue',
		),
		'view' => array(
			'viewSub' => array(
				'key1' => 'overridden',
				'key3' => 'new key',
			)
		),
		'persistence' => array(
			'storagePid' => '123',
		)
	);

	/**
	 * @var array
	 */
	protected $testSwitchableControllerActions = array(
		'Controller1' => array(
			'actions' => array('action1', 'action2', 'action3')
		),
		'Controller2' => array(
			'actions' => array('action4', 'action5', 'action6'),
			'nonCacheableActions' => array('action4', 'action6'),
		),
	);

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		$this->abstractConfigurationManager = $this->getAccessibleMock('Tx_Extbase_Configuration_AbstractConfigurationManager', array('getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions'));
	}

	/**
	 * @test
	 */
	public function setConfigurationResetsConfigurationCache() {
		$this->abstractConfigurationManager->_set('configurationCache', array('foo' => 'bar'));
		$this->abstractConfigurationManager->setConfiguration(array());
		$this->assertEquals(array(), $this->abstractConfigurationManager->_get('configurationCache'));
	}

	/**
	 * @test
	 */
	public function setConfigurationSetsExtensionAndPluginName() {
		$configuration = array(
			'extensionName' => 'SomeExtensionName',
			'pluginName' => 'SomePluginName',
		);
		$this->abstractConfigurationManager->setConfiguration($configuration);
		$this->assertEquals('SomeExtensionName', $this->abstractConfigurationManager->_get('extensionName'));
		$this->assertEquals('SomePluginName', $this->abstractConfigurationManager->_get('pluginName'));
	}

	/**
	 * @test
	 */
	public function setConfigurationConvertsTypoScriptArrayToPlainArray() {
		$configuration = array(
			'foo' => 'bar',
			'settings.' => array('foo' => 'bar'),
			'view.' => array('subkey.' => array('subsubkey' => 'subsubvalue'))
		);
		$expectedResult = array(
			'foo' => 'bar',
			'settings' => array('foo' => 'bar'),
			'view' => array('subkey' => array('subsubkey' => 'subsubvalue'))
		);
		$this->abstractConfigurationManager->setConfiguration($configuration);
		$this->assertEquals($expectedResult, $this->abstractConfigurationManager->_get('configuration'));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Configuration_Exception
	 */
	public function getConfigurationThrowsExceptionIfOnlyExtensionNameIsSpecified() {
		$this->abstractConfigurationManager->getConfiguration('SomeExtensionName');
	}

	/**
	 * @test
	 */
	public function getConfigurationReturnsCachedResultOfCurrentPlugin() {
		$this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
		$this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
		$this->abstractConfigurationManager->_set('configurationCache', array('currentextensionname_currentpluginname' => array('foo' => 'bar'), 'someotherextension_somepluginname' => array('baz' => 'shouldnotbereturned')));
		$expectedResult = array('foo' => 'bar');
		$actualResult = $this->abstractConfigurationManager->getConfiguration();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getConfigurationReturnsCachedResultForGivenExtension() {
		$this->abstractConfigurationManager->_set('configurationCache', array('someextensionname_somepluginname' => array('foo' => 'bar'), 'someotherextension_somepluginname' => array('baz' => 'shouldnotbereturned')));
		$expectedResult = array('foo' => 'bar');
		$actualResult = $this->abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getConfigurationRecursivelyMergesCurrentPluginConfigurationWithFrameworkConfiguration() {
		$this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
		$this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
		$this->abstractConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue($this->testTypoScriptSetup));
		$this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
		$expectedResult = array(
			'settings' => array(
				'setting1' => 'overriddenValue1',
				'setting2' => 'value2',
				'setting3' => 'additionalValue',
			),
			'view' => array(
				'viewSub' => array(
					'key1' => 'overridden',
					'key2' => 'value2',
					'key3' => 'new key',
				)
			),
			'persistence' => array(
				'storagePid' => '123',
			),
			'controllerConfiguration' => NULL
		);
		$this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->with($expectedResult)->will($this->returnValue($expectedResult));
		$actualResult = $this->abstractConfigurationManager->getConfiguration();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getConfigurationRecursivelyMergesPluginConfigurationOfSpecifiedPluginWithFrameworkConfiguration() {
		$this->abstractConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue($this->testTypoScriptSetup));
		$this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('SomeExtensionName', 'SomePluginName')->will($this->returnValue($this->testPluginConfiguration));
		$expectedResult = array(
			'settings' => array(
				'setting1' => 'overriddenValue1',
				'setting2' => 'value2',
				'setting3' => 'additionalValue',
			),
			'view' => array(
				'viewSub' => array(
					'key1' => 'overridden',
					'key2' => 'value2',
					'key3' => 'new key',
				)
			),
			'persistence' => array(
				'storagePid' => '123',
			),
			'controllerConfiguration' => NULL
		);
		$this->abstractConfigurationManager->expects($this->never())->method('getContextSpecificFrameworkConfiguration');
		$actualResult = $this->abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getConfigurationDoesNotOverrideConfigurationWithContextSpecificFrameworkConfigurationIfDifferentPluginIsSpecified() {
		$this->abstractConfigurationManager->expects($this->never())->method('getContextSpecificFrameworkConfiguration');
		$this->abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
	}

	/**
	 * @test
	 */
	public function getConfigurationOverridesConfigurationWithContextSpecificFrameworkConfigurationIfNoPluginWasSpecified() {
		$this->abstractConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue($this->testTypoScriptSetup));
		$this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with()->will($this->returnValue($this->testPluginConfiguration));
		$contextSpecifixFrameworkConfiguration = array(
			'context' => array(
				'specific' => 'framwork',
				'conf' => 'iguration',
			),
		);
		$this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnValue($contextSpecifixFrameworkConfiguration));
		$actualResult = $this->abstractConfigurationManager->getConfiguration();
		$this->assertEquals($contextSpecifixFrameworkConfiguration, $actualResult);
	}

	/**
	 * @test
	 */
	public function getConfigurationOverridesConfigurationWithContextSpecificFrameworkConfigurationIfSpecifiedPluginIsTheCurrentPlugin() {
		$this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
		$this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
		$this->abstractConfigurationManager->expects($this->once())->method('getTypoScriptSetup')->will($this->returnValue($this->testTypoScriptSetup));
		$this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
		$contextSpecifixFrameworkConfiguration = array(
			'context' => array(
				'specific' => 'framwork',
				'conf' => 'iguration',
			),
		);
		$this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnValue($contextSpecifixFrameworkConfiguration));
		$actualResult = $this->abstractConfigurationManager->getConfiguration('CurrentExtensionName', 'CurrentPluginName');
		$this->assertEquals($contextSpecifixFrameworkConfiguration, $actualResult);
	}

	/**
	 * @test
	 */
	public function getConfigurationStoresResultInConfigurationCache() {
		$this->abstractConfigurationManager->_set('extensionName', 'CurrentExtensionName');
		$this->abstractConfigurationManager->_set('pluginName', 'CurrentPluginName');
		$this->abstractConfigurationManager->expects($this->any())->method('getPluginConfiguration')->will($this->returnValue(array('foo' => 'bar')));
		$this->abstractConfigurationManager->getConfiguration();
		$this->abstractConfigurationManager->getConfiguration('SomeOtherExtensionName', 'SomeOtherCurrentPluginName');
		$expectedResult = array('currentextensionname_currentpluginname', 'someotherextensionname_someothercurrentpluginname');
		$actualResult = array_keys($this->abstractConfigurationManager->_get('configurationCache'));
		$this->assertEquals($expectedResult, $actualResult);
	}

	/** switchableControllerActions **/

	/**
	 * @test
	 */
	public function switchableControllerActionsAreNotOverriddenIfPluginNameIsSpecified() {
		$abstractConfigurationManager = $this->getAccessibleMock('Tx_Extbase_Configuration_AbstractConfigurationManager', array('overrideSwitchableControllerActions', 'getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions'));
		$abstractConfigurationManager->setConfiguration(array('switchableControllerActions' => array('overriddenSwitchableControllerActions')));
		$abstractConfigurationManager->expects($this->any())->method('getPluginConfiguration')->will($this->returnValue(array()));
		$abstractConfigurationManager->expects($this->never())->method('overrideSwitchableControllerActions');
		$abstractConfigurationManager->getConfiguration('SomeExtensionName', 'SomePluginName');
	}

	/**
	 * @test
	 */
	public function switchableControllerActionsAreOverriddenIfSpecifiedPluginIsTheCurrentPlugin() {
		$abstractConfigurationManager = $this->getAccessibleMock('Tx_Extbase_Configuration_AbstractConfigurationManager', array('overrideSwitchableControllerActions', 'getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions'));
		$abstractConfigurationManager->setConfiguration(array('extensionName' => 'CurrentExtensionName', 'pluginName' => 'CurrentPluginName', 'switchableControllerActions' => array('overriddenSwitchableControllerActions')));
		$abstractConfigurationManager->expects($this->any())->method('getPluginConfiguration')->will($this->returnValue(array()));
		$abstractConfigurationManager->expects($this->once())->method('overrideSwitchableControllerActions');
		$abstractConfigurationManager->getConfiguration('CurrentExtensionName', 'CurrentPluginName');
	}

	/**
	 * @test
	 */
	public function switchableControllerActionsAreOverriddenIfPluginNameIsNotSpecified() {
		$abstractConfigurationManager = $this->getAccessibleMock('Tx_Extbase_Configuration_AbstractConfigurationManager', array('overrideSwitchableControllerActions', 'getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getPluginConfiguration', 'getSwitchableControllerActions'));
		$abstractConfigurationManager->setConfiguration(array('switchableControllerActions' => array('overriddenSwitchableControllerActions')));
		$abstractConfigurationManager->expects($this->any())->method('getPluginConfiguration')->will($this->returnValue(array()));
		$abstractConfigurationManager->expects($this->once())->method('overrideSwitchableControllerActions');
		$abstractConfigurationManager->getConfiguration();
	}

	/**
	 * @test
	 */
	public function orderOfActionsCanBeOverriddenForCurrentPlugin() {
		$configuration = array(
			'extensionName' => 'CurrentExtensionName',
			'pluginName' => 'CurrentPluginName',
			'switchableControllerActions' => array(
				'Controller1' => array('action2', 'action1', 'action3'),
			)
		);
		$this->abstractConfigurationManager->setConfiguration($configuration);
		$this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
		$this->abstractConfigurationManager->expects($this->once())->method('getSwitchableControllerActions')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testSwitchableControllerActions));
		$this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnCallBack(create_function('$a', 'return $a;')));
		$mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
		$expectedResult = array(
			'Controller1' => array(
				'actions' => array('action2', 'action1', 'action3'),
			),
		);
		$actualResult = $mergedConfiguration['controllerConfiguration'];
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function newActionsCanBeAddedForCurrentPlugin() {
		$configuration = array(
			'extensionName' => 'CurrentExtensionName',
			'pluginName' => 'CurrentPluginName',
			'switchableControllerActions' => array(
				'Controller1' => array('action2', 'action1', 'action3', 'newAction'),
			)
		);
		$this->abstractConfigurationManager->setConfiguration($configuration);
		$this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
		$this->abstractConfigurationManager->expects($this->once())->method('getSwitchableControllerActions')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testSwitchableControllerActions));
		$this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnCallBack(create_function('$a', 'return $a;')));
		$mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
		$expectedResult = array(
			'Controller1' => array(
				'actions' => array('action2', 'action1', 'action3', 'newAction'),
			),
		);
		$actualResult = $mergedConfiguration['controllerConfiguration'];
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function controllersCanNotBeOverridden() {
		$configuration = array(
			'extensionName' => 'CurrentExtensionName',
			'pluginName' => 'CurrentPluginName',
			'switchableControllerActions' => array(
				'NewController' => array('action1', 'action2'),
			)
		);
		$this->abstractConfigurationManager->setConfiguration($configuration);
		$this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
		$this->abstractConfigurationManager->expects($this->once())->method('getSwitchableControllerActions')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testSwitchableControllerActions));
		$this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnCallBack(create_function('$a', 'return $a;')));
		$mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
		$expectedResult = array();
		$actualResult = $mergedConfiguration['controllerConfiguration'];
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function cachingOfActionsCanNotBeChanged() {
		$configuration = array(
			'extensionName' => 'CurrentExtensionName',
			'pluginName' => 'CurrentPluginName',
			'switchableControllerActions' => array(
				'Controller1' => array('newAction', 'action1'),
				'Controller2' => array('newAction2', 'action4', 'action5'),
			)
		);
		$this->abstractConfigurationManager->setConfiguration($configuration);
		$this->abstractConfigurationManager->expects($this->once())->method('getPluginConfiguration')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testPluginConfiguration));
		$this->abstractConfigurationManager->expects($this->once())->method('getSwitchableControllerActions')->with('CurrentExtensionName', 'CurrentPluginName')->will($this->returnValue($this->testSwitchableControllerActions));
		$this->abstractConfigurationManager->expects($this->once())->method('getContextSpecificFrameworkConfiguration')->will($this->returnCallBack(create_function('$a', 'return $a;')));
		$mergedConfiguration = $this->abstractConfigurationManager->getConfiguration();
		$expectedResult = array(
			'Controller1' => array(
				'actions' => array('newAction', 'action1')
			),
			'Controller2' => array(
				'actions' => array('newAction2', 'action4', 'action5'),
				'nonCacheableActions' => array('action4'),
			)
		);
		$actualResult = $mergedConfiguration['controllerConfiguration'];
		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>