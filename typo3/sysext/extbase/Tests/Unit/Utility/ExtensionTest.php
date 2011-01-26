<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Oliver Hader <oliver@typo3.org>
*  All rights reserved
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

/**
 * Testcase for class Tx_Extbase_Utility_Extension
 *
 * @package Extbase
 * @subpackage extbase
 */
class Tx_Extbase_Tests_Unit_Utility_ExtensionTest extends tx_phpunit_testcase {

	/**
	 * Contains backup of $TYPO3_CONF_VARS
	 * @var array
	 */
	protected $typo3ConfVars = array();

	/**
	 * @var t3lib_DB
	 */
	protected $typo3DbBackup;

	/**
	 * @var	t3lib_fe contains a backup of the current $GLOBALS['TSFE']
	 */
	protected $tsfeBackup;

	public function setUp() {
		$this->typo3ConfVars = $GLOBALS['TYPO3_CONF_VARS'];
		$this->typo3DbBackup = $GLOBALS['TYPO3_DB'];
		$GLOBALS['TYPO3_DB'] = $this->getMock('t3lib_DB', array('fullQuoteStr', 'exec_SELECTgetRows'));
		$this->tsfeBackup = $GLOBALS['TSFE'];
		if (!isset($GLOBALS['TSFE']->tmpl)) {
			$GLOBALS['TSFE']->tmpl = new stdClass();
		}
		if (!isset($GLOBALS['TSFE']->tmpl->setup)) {
			$GLOBALS['TSFE']->tmpl->setup = array();
		}
		$GLOBALS['TSFE']->tmpl->setup['tt_content.']['list.']['20.'] = array(
			'9' => 'CASE',
			'9.' => array(
				'key.' => array(
					'field' => 'layout'),
					0 => '< plugin.tt_news'
				),
			'extensionname_someplugin' => 'USER',
			'extensionname_someplugin.' => array(
				'userFunc' => 'tx_extbase_core_bootstrap->run',
				'extensionName' => 'ExtensionName',
				'pluginName' => 'SomePlugin',
			),
			'someotherextensionname_secondplugin' => 'USER',
			'someotherextensionname_secondplugin.' => array(
				'userFunc' => 'tx_extbase_core_bootstrap->run',
				'extensionName' => 'SomeOtherExtensionName',
				'pluginName' => 'SecondPlugin',
			),
			'extensionname_thirdplugin' => 'USER',
			'extensionname_thirdplugin.' => array(
				'userFunc' => 'tx_extbase_core_bootstrap->run',
				'extensionName' => 'ExtensionName',
				'pluginName' => 'ThirdPlugin',
			),
		);
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'] = array(
			'ExtensionName' => array(
				'plugins' => array(
					'SomePlugin' => array(
						'controllers' => array(
							'ControllerName' => array(
								'actions' => array('index', 'otherAction')
							),
						),
					),
					'ThirdPlugin' => array(
						'controllers' => array(
							'ControllerName' => array(
								'actions' => array('otherAction', 'thirdAction')
							),
						),
					),
				),
			),
			'SomeOtherExtensionName' => array(
				'plugins' => array(
					'SecondPlugin' => array(
						'controllers' => array(
							'ControllerName' => array(
								'actions' => array('index', 'otherAction')
							),
							'SecondControllerName' => array(
								'actions' => array('someAction', 'someOtherAction'),
								'nonCacheableActions' => array('someOtherAction')
							)
						),
					),
				),
			),
		);
	}

	public function tearDown() {
		$GLOBALS['TYPO3_CONF_VARS'] = $this->typo3ConfVars;
		$GLOBALS['TSFE'] = $this->tsfeBackup;
		t3lib_div::purgeInstances();
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginWorksForMinimalisticSetup() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array('Blog' => 'index')
		);
		$staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['43'];

		$this->assertContains('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
		$this->assertContains('
	userFunc = tx_extbase_core_bootstrap->run
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);

	$this->assertNotContains('USER_INT', $staticTypoScript);
	}


	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginCreatesCorrectDefaultTypoScriptSetup() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array('Blog' => 'index')
		);
		$staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['43'];
		$defaultTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'];
		$this->assertContains('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
		$this->assertContains('
plugin.tx_myextension {
	settings {
	}
	persistence {
		storagePid =
		classes {
		}
	}
	view {
		templateRootPath =
		layoutRootPath =
		partialRootPath =
		 # with defaultPid you can specify the default page uid of this plugin. If you set this to the string "auto" the target page will be determined automatically. Defaults to an empty string that expects the target page to be the current page.
		defaultPid =
	}
}', $defaultTypoScript);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginWorksForASingleControllerAction() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array(
				'FirstController' => 'index'
				)
		);
		$staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['43'];

		$this->assertContains('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
		$this->assertContains('
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);

		$expectedResult = array(
			'controllers' => array(
				'FirstController' => array(
					'actions' => array('index')
				)
			),
			'pluginType' => 'list_type'
		);
		$this->assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginThrowsExceptionIfExtensionNameIsEmpty() {
		Tx_Extbase_Utility_Extension::configurePlugin(
			'',
			'SomePlugin',
			array(
				'FirstController' => 'index'
				)
		);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginThrowsExceptionIfPluginNameIsEmpty() {
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'',
			array(
				'FirstController' => 'index'
				)
		);
	}


	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginRespectsDefaultActionAsANonCacheableAction() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array(
				'FirstController' => 'index,show,new, create,delete,edit,update'
				),
			array(
				'FirstController' => 'index,show'
				)
			);
		$staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['43'];
		$this->assertContains('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
		$this->assertContains('
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);

		$expectedResult = array(
			'controllers' => array(
				'FirstController' => array(
					'actions' => array('index', 'show', 'new', 'create', 'delete', 'edit', 'update'),
					'nonCacheableActions' => array('index', 'show')
				)
			),
			'pluginType' => 'list_type'
		);
		$this->assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginRespectsNonDefaultActionAsANonCacheableAction() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array(
				'FirstController' => 'index,show,new, create,delete,edit,update'
				),
			array(
				'FirstController' => 'new,show'
				)
			);
		$staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['43'];
		$this->assertContains('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
		$this->assertContains('
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);

		$expectedResult = array(
			'controllers' => array(
				'FirstController' => array(
					'actions' => array('index', 'show', 'new', 'create', 'delete', 'edit', 'update'),
					'nonCacheableActions' => array('new', 'show')
				)
			),
			'pluginType' => 'list_type'
		);
		$this->assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginWorksForMultipleControllerActionsWithCacheableActionAsDefault() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array(
				'FirstController' => 'index,show,new,create,delete,edit,update',
				'SecondController' => 'index,show,delete',
				'ThirdController' => 'create'
				),
			array(
				'FirstController' => 'new,create,edit,update',
				'ThirdController' => 'create'
				)
			);

		$expectedResult = array(
			'controllers' => array(
				'FirstController' => array(
					'actions' => array('index', 'show', 'new', 'create', 'delete', 'edit', 'update'),
					'nonCacheableActions' => array('new', 'create', 'edit', 'update')
				),
				'SecondController' => array(
					'actions' => array('index', 'show', 'delete')
				),
				'ThirdController' => array(
					'actions' => array('create'),
					'nonCacheableActions' => array('create')
				)
			),
			'pluginType' => 'list_type'
		);
		$this->assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
	}


	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginWorksForMultipleControllerActionsWithNonCacheableActionAsDefault() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array(
				'FirstController' => 'index,show,new,create,delete,edit,update',
				'SecondController' => 'index,show,delete',
				'ThirdController' => 'create'
				),
			array(
				'FirstController' => 'index,new,create,edit,update',
				'SecondController' => 'delete',
				'ThirdController' => 'create'
				)
			);

		$expectedResult = array(
			'controllers' => array(
				'FirstController' => array(
					'actions' => array('index', 'show', 'new', 'create', 'delete', 'edit', 'update'),
					'nonCacheableActions' => array('index', 'new', 'create', 'edit', 'update')
				),
				'SecondController' => array(
					'actions' => array('index', 'show', 'delete'),
					'nonCacheableActions' => array('delete')
				),
				'ThirdController' => array(
					'actions' => array('create'),
					'nonCacheableActions' => array('create')
				)
			),
			'pluginType' => 'list_type'
		);
		$this->assertEquals($expectedResult, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['MyExtension']['plugins']['Pi1']);
	}

	/**
	 * DataProvider for getPluginNamespaceByPluginSignatureTests()
	 *
	 * @return array
	 */
	public function getPluginNamespaceDataProvider() {
		return array(
			array('SomeExtension', 'SomePlugin', 'tx_someextension_someplugin'),
			array('NonExistingExtension', 'SomePlugin', 'tx_nonexistingextension_someplugin'),
			array('Invalid', '', 'tx_invalid_'),
		);
	}

	/**
	 * @test
	 * @dataProvider getPluginNamespaceDataProvider
	 */
	public function getPluginNamespaceTests($extensionName, $pluginName, $expectedResult) {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array()));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$actualResult = Tx_Extbase_Utility_Extension::getPluginNamespace($extensionName, $pluginName);
		$this->assertEquals($expectedResult, $actualResult, 'Failing for extension: "' . $extensionName . '", plugin: "' . $pluginName . '"');
	}

	/**
	 * @test
	 */
	public function pluginNamespaceCanBeOverridden() {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'SomeExtension', 'SomePlugin')->will($this->returnValue(array('view' => array('pluginNamespace' => 'overridden_plugin_namespace'))));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$expectedResult = 'overridden_plugin_namespace';
		$actualResult = Tx_Extbase_Utility_Extension::getPluginNamespace('SomeExtension', 'SomePlugin');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * DataProvider for getPluginNameByActionTests()
	 *
	 * @return array
	 */
	public function getPluginNameByActionDataProvider() {
		return array(
			array('ExtensionName', 'ControllerName', 'someNonExistingAction', NULL),
			array('ExtensionName', 'ControllerName', 'index', 'SomePlugin'),
			array('ExtensionName', 'ControllerName', 'thirdAction', 'ThirdPlugin'),
			array('eXtEnSiOnNaMe', 'cOnTrOlLeRnAmE', 'thirdAction', NULL),
			array('eXtEnSiOnNaMe', 'cOnTrOlLeRnAmE', 'ThIrDaCtIoN', NULL),
			array('SomeOtherExtensionName', 'ControllerName', 'otherAction', 'SecondPlugin'),
		);
	}

	/**
	 * @test
	 * @dataProvider getPluginNameByActionDataProvider
	 */
	public function getPluginNameByActionTests($extensionName, $controllerName, $actionName, $expectedResult) {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)->will($this->returnValue(array('view' => array('pluginNamespace' => 'overridden_plugin_namespace'))));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$actualResult = Tx_Extbase_Utility_Extension::getPluginNameByAction($extensionName, $controllerName, $actionName);
		$this->assertEquals($expectedResult, $actualResult, 'Failing for $extensionName: "' . $extensionName . '", $controllerName: "' . $controllerName . '", $actionName: "' . $actionName . '" - ');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Exception
	 */
	public function getPluginNameByActionThrowsExceptionIfMoreThanOnePluginMatches() {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)->will($this->returnValue(array('view' => array('pluginNamespace' => 'overridden_plugin_namespace'))));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		Tx_Extbase_Utility_Extension::getPluginNameByAction('ExtensionName', 'ControllerName', 'otherAction');
	}

	/**
	 * @test
	 */
	public function getPluginNameByActionReturnsCurrentIfItCanHandleTheActionEvenIfMoreThanOnePluginMatches() {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)->will($this->returnValue(array('extensionName' => 'CurrentExtension', 'pluginName' => 'CurrentPlugin', 'controllerConfiguration' => array('ControllerName' => array('actions' => array('otherAction'))))));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$actualResult = Tx_Extbase_Utility_Extension::getPluginNameByAction('CurrentExtension', 'ControllerName', 'otherAction');
		$expectedResult = 'CurrentPlugin';
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function isActionCacheableReturnsTrueByDefault() {
		$mockConfiguration = array();
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($mockConfiguration));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->any())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$actualResult = Tx_Extbase_Utility_Extension::isActionCacheable('SomeExtension', 'SomePlugin', 'SomeController', 'someAction');
		$this->assertTrue($actualResult);
	}

	/**
	 * @test
	 */
	public function isActionCacheableReturnsFalseIfActionIsNotCacheable() {
		$mockConfiguration = array(
			'controllerConfiguration' => array(
				'SomeController' => array(
					'nonCacheableActions' => array('someAction')
				)
			)
		);
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($mockConfiguration));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->any())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$actualResult = Tx_Extbase_Utility_Extension::isActionCacheable('SomeExtension', 'SomePlugin', 'SomeController', 'someAction');
		$this->assertFalse($actualResult);
	}

	/**
	 * @test
	 */
	public function getTargetPidByPluginSignatureReturnsNullIfConfigurationManagerIsNotInitialized() {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(NULL));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$this->assertNull(Tx_Extbase_Utility_Extension::getTargetPidByPlugin('ExtensionName', 'PluginName'));
	}

	/**
	 * @test
	 */
	public function getTargetPidByPluginSignatureReturnsNullIfDefaultPidIsZero() {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array('view' => array('defaultPid' => 0))));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$this->assertNull(Tx_Extbase_Utility_Extension::getTargetPidByPlugin('ExtensionName', 'PluginName'));
	}

	/**
	 * @test
	 */
	public function getTargetPidByPluginSignatureReturnsTheConfiguredDefaultPid() {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array('view' => array('defaultPid' => 123))));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$expectedResult = 123;
		$actualResult = Tx_Extbase_Utility_Extension::getTargetPidByPlugin('ExtensionName', 'SomePlugin');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getTargetPidByPluginSignatureDeterminesTheTargetPidIfDefaultPidIsAuto() {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array('view' => array('defaultPid' => 'auto'))));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$pluginSignature = 'extensionname_someplugin';
		$GLOBALS['TSFE']->sys_page = $this->getMock('t3lib_pageSelect', array('enableFields'));
		$GLOBALS['TSFE']->sys_page->expects($this->once())->method('enableFields')->with('tt_content')->will($this->returnValue(' AND enable_fields'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('fullQuoteStr')->with($pluginSignature, 'tt_content')->will($this->returnValue('"pluginSignature"'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_SELECTgetRows')->with(
			'pid',
			'tt_content',
			'list_type="pluginSignature" AND CType="list" AND enable_fields',
			'',
			''
		)->will($this->returnValue(array(array('pid' => '321'))));
		$expectedResult = 321;
		$actualResult = Tx_Extbase_Utility_Extension::getTargetPidByPlugin('ExtensionName', 'SomePlugin');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getTargetPidByPluginSignatureReturnsNullIfTargetPidCouldNotBeDetermined() {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array('view' => array('defaultPid' => 'auto'))));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$GLOBALS['TSFE']->sys_page = $this->getMock('t3lib_pageSelect', array('enableFields'));
		$GLOBALS['TSFE']->sys_page->expects($this->once())->method('enableFields')->will($this->returnValue(' AND enable_fields'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('fullQuoteStr')->will($this->returnValue('"pluginSignature"'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnValue(array()));
		$this->assertNull(Tx_Extbase_Utility_Extension::getTargetPidByPlugin('ExtensionName', 'SomePlugin'));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Exception
	 */
	public function getTargetPidByPluginSignatureThrowsExceptionIfMoreThanOneTargetPidsWereFound() {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array('view' => array('defaultPid' => 'auto'))));
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$mockObjectManager->expects($this->once())->method('get')->with('Tx_Extbase_Configuration_ConfigurationManagerInterface')->will($this->returnValue($mockConfigurationManager));
		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $mockObjectManager);

		$GLOBALS['TSFE']->sys_page = $this->getMock('t3lib_pageSelect', array('enableFields'));
		$GLOBALS['TSFE']->sys_page->expects($this->once())->method('enableFields')->will($this->returnValue(' AND enable_fields'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('fullQuoteStr')->will($this->returnValue('"pluginSignature"'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnValue(array(array('pid' => 123), array('pid' => 124))));
		Tx_Extbase_Utility_Extension::getTargetPidByPlugin('ExtensionName', 'SomePlugin');
	}

}

?>