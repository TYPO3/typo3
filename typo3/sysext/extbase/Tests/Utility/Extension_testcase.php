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

}

?>