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
class Tx_Extbase_Utility_Extension_testcase extends tx_phpunit_testcase {

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
		global $TYPO3_CONF_VARS;
		$this->typo3ConfVars = $TYPO3_CONF_VARS;
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
				'userFunc' => 'tx_extbase_dispatcher->dispatch',
				'pluginName' => 'SomePlugin',
				'extensionName' => 'ExtensionName',
				'controller' => 'ControllerName',
				'action' => 'index',
				'switchableControllerActions.' => array(
					'ControllerName.' => array(
						'actions' => 'index,otherAction',
					),
				),
			),
			'someotherextensionname_secondplugin' => 'USER',
			'someotherextensionname_secondplugin.' => array(
				'userFunc' => 'tx_extbase_dispatcher->dispatch',
				'pluginName' => 'SecondPlugin',
				'extensionName' => 'SomeOtherExtensionName',
				'controller' => 'ControllerName',
				'action' => 'index',
				'switchableControllerActions.' => array(
					'ControllerName.' => array(
						'actions' => 'index,otherAction',
					),
					'SecondControllerName.' => array(
						'actions' => 'someAction,someOtherAction',
						'nonCacheableActions' => 'someOtherAction',
					),
				),
			),
			'extensionname_thirdplugin' => 'USER',
			'extensionname_thirdplugin.' => array(
				'userFunc' => 'tx_extbase_dispatcher->dispatch',
				'pluginName' => 'ThirdPlugin',
				'extensionName' => 'ExtensionName',
				'controller' => 'ControllerName',
				'action' => 'index',
				'switchableControllerActions.' => array(
					'ControllerName.' => array(
						'actions' => 'otherAction,thirdAction',
					),
				),
			),
		);
	}

	public function tearDown() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS = $this->typo3ConfVars;
		$GLOBALS['TSFE'] = $this->tsfeBackup;
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginWorksForMinimalisticSetup() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array('Blog' => 'index')
		);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['43'];

		$this->assertContains('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
		$this->assertContains('
	pluginName = Pi1
	extensionName = MyExtension', $staticTypoScript);

	$this->assertNotContains('USER_INT', $staticTypoScript);
	}


	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginCreatesCorrectDefaultTypoScriptSetup() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array('Blog' => 'index')
		);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['43'];
		$defaultTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup'];
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
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array(
				'FirstController' => 'index'
				)
		);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['43'];

		$this->assertContains('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
		$this->assertContains('
	pluginName = Pi1
	extensionName = MyExtension', $staticTypoScript);
		$this->assertContains('
	controller = FirstController
	action = index', $staticTypoScript);
		$this->assertNotContains('USER_INT', $staticTypoScript);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginWithEmptyPluginNameResultsInAnError() {
		$this->setExpectedException('InvalidArgumentException');
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
	public function configurePluginWithEmptyExtensionNameResultsInAnError() {
		$this->setExpectedException('InvalidArgumentException');
		Tx_Extbase_Utility_Extension::configurePlugin(
			'',
			'Pi1',
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
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array(
				'FirstController' => 'index,show,new,create,delete,edit,update'
				),
			array(
				'FirstController' => 'index,show'
				)
			);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['43'];
		$this->assertContains('
tt_content.list.20.myextension_pi1 = USER
tt_content.list.20.myextension_pi1 {', $staticTypoScript);
		$this->assertContains('FirstController.nonCacheableActions = index,show
', $staticTypoScript);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginRespectsNonDefaultActionAsANonCacheableAction() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Extension::configurePlugin(
			'MyExtension',
			'Pi1',
			array(
				'FirstController' => 'index,show,new,create,delete,edit,update'
				),
			array(
				'FirstController' => 'show,new'
				)
			);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['43'];

		$this->assertContains('
tt_content.list.20.myextension_pi1 = USER
tt_content.list.20.myextension_pi1 {', $staticTypoScript);
		$this->assertContains('FirstController.nonCacheableActions = show,new
', $staticTypoScript);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginWorksForMultipleControllerActionsWithCacheableActionAsDefault() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
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
				'SecondController' => 'delete',
				'ThirdController' => 'create'
				)
			);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['43'];

		$this->assertContains('
tt_content.list.20.myextension_pi1 = USER
tt_content.list.20.myextension_pi1 {', $staticTypoScript);

		$this->assertContains('FirstController.nonCacheableActions = new,create,edit,update
', $staticTypoScript);

		$this->assertContains('SecondController.nonCacheableActions = delete
', $staticTypoScript);

		$this->assertContains('ThirdController.nonCacheableActions = create
', $staticTypoScript);
	}


	/**
	 * @test
	 * @see Tx_Extbase_Utility_Extension::registerPlugin
	 */
	public function configurePluginWorksForMultipleControllerActionsWithNonCacheableActionAsDefault() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
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
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['43'];

		$this->assertContains('
tt_content.list.20.myextension_pi1 = USER
tt_content.list.20.myextension_pi1 {', $staticTypoScript);

		$this->assertContains('FirstController.nonCacheableActions = index,new,create,edit,update
', $staticTypoScript);

		$this->assertContains('SecondController.nonCacheableActions = delete
', $staticTypoScript);

		$this->assertContains('ThirdController.nonCacheableActions = create
', $staticTypoScript);
	}

	/**
	 * DataProvider for getPluginNamespaceByPluginSignatureTests()
	 *
	 * @return array
	 */
	public function getPluginNamespaceByPluginSignatureDataProvider() {
		return array(
			array('someextension_someplugin', 'tx_someextension_someplugin'),
			array('nonexistingextension_someplugin', 'tx_nonexistingextension_someplugin'),
			array('InvalidPluginNamespace', 'tx_InvalidPluginNamespace'),
		);
	}

	/**
	 * @test
	 * @dataProvider getPluginNamespaceByPluginSignatureDataProvider
	 */
	public function getPluginNamespaceByPluginSignatureTests($pluginSignature, $expectedResult) {
		$dispatcher = new Tx_Extbase_Dispatcher();
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManager', array('getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup'));
		$dispatcher->injectConfigurationManager($mockConfigurationManager);
		$actualResult = Tx_Extbase_Utility_Extension::getPluginNamespaceByPluginSignature($pluginSignature);
		$this->assertEquals($expectedResult, $actualResult, 'Failing for $pluginSignature: "' . $pluginSignature . '"');
	}

	/**
	 * @test
	 */
	public function pluginNamespaceCanBeOverridden() {
		$dispatcher = new Tx_Extbase_Dispatcher();
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManager', array('getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getConfiguration'));
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array('view' => array('pluginNamespace' => 'overridden_plugin_namespace'))));
		$dispatcher->injectConfigurationManager($mockConfigurationManager);
		$expectedResult = 'overridden_plugin_namespace';
		$actualResult = Tx_Extbase_Utility_Extension::getPluginNamespaceByPluginSignature('somePluginSignature');
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
			array('eXtEnSiOnNaMe', 'cOnTrOlLeRnAmE', 'thirdAction', 'ThirdPlugin'),
			array('eXtEnSiOnNaMe', 'cOnTrOlLeRnAmE', 'ThIrDaCtIoN', NULL),
			array('SomeOtherExtensionName', 'ControllerName', 'otherAction', 'SecondPlugin'),
		);
	}

	/**
	 * @test
	 * @dataProvider getPluginNameByActionDataProvider
	 */
	public function getPluginNameByActionTests($extensionName, $controllerName, $actionName, $expectedResult) {
		$actualResult = Tx_Extbase_Utility_Extension::getPluginNameByAction($extensionName, $controllerName, $actionName);
		$this->assertEquals($expectedResult, $actualResult, 'Failing for $extensionName: "' . $extensionName . '", $controllerName: "' . $controllerName . '", $actionName: "' . $actionName . '" - ');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Exception
	 */
	public function getPluginNameByActionThrowsExceptionIfMoreThanOnePluginMatches() {
		Tx_Extbase_Utility_Extension::getPluginNameByAction('ExtensionName', 'ControllerName', 'otherAction');
	}

	/**
	 * @test
	 */
	public function getTargetPidByPluginSignatureReturnsNullIfConfigurationManagerIsNotInitialized() {
		$this->assertNull(Tx_Extbase_Utility_Extension::getTargetPidByPluginSignature('plugin_signature'));
	}

	/**
	 * @test
	 */
	public function getTargetPidByPluginSignatureReturnsNullIfDefaultPidIsNotConfigured() {
		$dispatcher = new Tx_Extbase_Dispatcher();
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManager', array('getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getConfiguration'));
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with($GLOBALS['TSFE']->tmpl->setup['tt_content.']['list.']['20.']['extensionname_someplugin.'])->will($this->returnValue(array()));
		$dispatcher->injectConfigurationManager($mockConfigurationManager);
		$this->assertNull(Tx_Extbase_Utility_Extension::getTargetPidByPluginSignature('extensionname_someplugin'));
	}

	/**
	 * @test
	 */
	public function getTargetPidByPluginSignatureReturnsTheConfiguredDefaultPid() {
		$dispatcher = new Tx_Extbase_Dispatcher();
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManager', array('getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getConfiguration'));
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with($GLOBALS['TSFE']->tmpl->setup['tt_content.']['list.']['20.']['extensionname_someplugin.'])->will($this->returnValue(array('view' => array('defaultPid' => '123'))));
		$dispatcher->injectConfigurationManager($mockConfigurationManager);
		$expectedResult = 123;
		$actualResult = Tx_Extbase_Utility_Extension::getTargetPidByPluginSignature('extensionname_someplugin');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getTargetPidByPluginSignatureDeterminesTheTargetPidIfDefaultPidIsAuto() {
		$dispatcher = new Tx_Extbase_Dispatcher();
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManager', array('getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getConfiguration'));
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with($GLOBALS['TSFE']->tmpl->setup['tt_content.']['list.']['20.']['extensionname_someplugin.'])->will($this->returnValue(array('view' => array('defaultPid' => 'auto'))));
		$dispatcher->injectConfigurationManager($mockConfigurationManager);
		$pluginSignature = 'extensionname_someplugin';
		$GLOBALS['TSFE']->sys_page = $this->getMock('t3lib_pageSelect', array('enableFields'));
		$GLOBALS['TSFE']->sys_page->expects($this->once())->method('enableFields')->with('tt_content')->will($this->returnValue(' AND enable_fields'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('fullQuoteStr')->with($pluginSignature, 'tt_content')->will($this->returnValue('"pluginSignature"'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_SELECTgetRows')->with(
			'pid',
			'tt_content',
			'list_type="pluginSignature" AND enable_fields',
			'',
			''
		)->will($this->returnValue(array(array('pid' => '321'))));
		$expectedResult = 321;
		$actualResult = Tx_Extbase_Utility_Extension::getTargetPidByPluginSignature($pluginSignature);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getTargetPidByPluginSignatureReturnsNullIfTargetPidCouldNotBeDetermined() {
		$dispatcher = new Tx_Extbase_Dispatcher();
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManager', array('getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getConfiguration'));
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with($GLOBALS['TSFE']->tmpl->setup['tt_content.']['list.']['20.']['extensionname_someplugin.'])->will($this->returnValue(array('view' => array('defaultPid' => 'auto'))));
		$dispatcher->injectConfigurationManager($mockConfigurationManager);
		$GLOBALS['TSFE']->sys_page = $this->getMock('t3lib_pageSelect', array('enableFields'));
		$GLOBALS['TSFE']->sys_page->expects($this->once())->method('enableFields')->will($this->returnValue(' AND enable_fields'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('fullQuoteStr')->will($this->returnValue('"pluginSignature"'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnValue(array()));
		$this->assertNull(Tx_Extbase_Utility_Extension::getTargetPidByPluginSignature('extensionname_someplugin'));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Exception
	 */
	public function getTargetPidByPluginSignatureThrowsExceptionIfMoreThanOneTargetPidsWereFound() {
		$dispatcher = new Tx_Extbase_Dispatcher();
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManager', array('getContextSpecificFrameworkConfiguration', 'getTypoScriptSetup', 'getConfiguration'));
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with($GLOBALS['TSFE']->tmpl->setup['tt_content.']['list.']['20.']['extensionname_someplugin.'])->will($this->returnValue(array('view' => array('defaultPid' => 'auto'))));
		$dispatcher->injectConfigurationManager($mockConfigurationManager);
		$GLOBALS['TSFE']->sys_page = $this->getMock('t3lib_pageSelect', array('enableFields'));
		$GLOBALS['TSFE']->sys_page->expects($this->once())->method('enableFields')->will($this->returnValue(' AND enable_fields'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('fullQuoteStr')->will($this->returnValue('"pluginSignature"'));
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnValue(array(array('pid' => 123), array('pid' => 124))));
		Tx_Extbase_Utility_Extension::getTargetPidByPluginSignature('extensionname_someplugin');
	}

}

?>