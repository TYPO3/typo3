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
 * Testcase for class Tx_Extbase_Utility_Plugin
 *
 * @package Extbase
 * @subpackage extbase
 */
class Tx_Extbase_Utility_Plugin_testcase extends tx_phpunit_testcase {
	
	/**
	 * Contains backup of $TYPO3_CONF_VARS
	 * @var array
	 */
	protected $typo3ConfVars = array();

	public function setUp() {
		global $TYPO3_CONF_VARS;
		$this->typo3ConfVars = $TYPO3_CONF_VARS;
	}
	
	public function tearDown() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS = $this->typo3ConfVars;
	}
	
	/**
	 * @test
	 * @see Tx_Extbase_Utility_Plugin::registerPlugin
	 */
	public function addingTsWorksForMinimalisticSetup() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Plugin::registerPlugin(
			'MyExtension',
			'Pi1',
			'My Plugin Title',
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
	 * @see Tx_Extbase_Utility_Plugin::registerPlugin
	 */
	public function addingTsWorksForASingleControllerAction() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Plugin::registerPlugin(
			'MyExtension',
			'Pi1',
			'My Plugin Title',
			array(
				'FirstController' => 'index'
				)
		);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['43'];

		$this->assertContains('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
		$this->assertContains('
	pluginName = Pi1
	extensionName = MyExtension
	controller = FirstController
	action = index', $staticTypoScript);
		$this->assertNotContains('USER_INT', $staticTypoScript);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Plugin::registerPlugin
	 */
	public function addingPluginWithEmptyPluginNameResultsInAnError() {
		$this->setExpectedException('InvalidArgumentException');
		Tx_Extbase_Utility_Plugin::registerPlugin(
			'MyExtension',
			'',
			'My Plugin Title',
			array(
				'FirstController' => 'index'
				)
		);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Plugin::registerPlugin
	 */
	public function addingPluginWithEmptyExtensionNameResultsInAnError() {
		$this->setExpectedException('InvalidArgumentException');
		Tx_Extbase_Utility_Plugin::registerPlugin(
			'',
			'Pi1',
			'My Plugin Title',
			array(
				'FirstController' => 'index'
				)
		);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Plugin::registerPlugin
	 */
	public function addingPluginWithInvalidExtensionNameResultsInAnError() {
		$this->setExpectedException('InvalidArgumentException');
		Tx_Extbase_Utility_Plugin::registerPlugin(
			'my_extension',
			'Pi1',
			'My Plugin Title',
			array(
				'FirstController' => 'index'
				)
		);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Plugin::registerPlugin
	 */
	public function addingPluginRespectsDefaultActionAsANonCachableAction() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Plugin::registerPlugin(
			'MyExtension',
			'Pi1',
			'My Plugin Title',
			array(
				'FirstController' => 'index,show,new,create,delete,edit,update'
				),
			array(
				'FirstController' => 'index,show'
				)
			);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['43'];

		$this->assertContains('
tt_content.list.20.myextension_pi1 = USER_INT
tt_content.list.20.myextension_pi1 {', $staticTypoScript);
		$this->assertContains('
[globalString: GP = tx_myextension_pi1|controller = FirstController] && [globalString: GP = tx_myextension_pi1|action = /new|create|delete|edit|update/]
tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Plugin::registerPlugin
	 */
	public function addingPluginRespectsNonDefaultActionAsANonCachableAction() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Plugin::registerPlugin(
			'MyExtension',
			'Pi1',
			'My Plugin Title',
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
		$this->assertContains('
[globalString: GP = tx_myextension_pi1|controller = FirstController] && [globalString: GP = tx_myextension_pi1|action = /show|new/]
tt_content.list.20.myextension_pi1 = USER_INT', $staticTypoScript);
	}

	/**
	 * @test
	 * @see Tx_Extbase_Utility_Plugin::registerPlugin
	 */
	public function addingPluginWorksForMultipleControllerActionsWithCachableActionAsDefault() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Plugin::registerPlugin(
			'MyExtension',
			'Pi1',
			'My Plugin Title',
			array(
				'FirstController' => 'index,show,new,create,delete,edit,update',
				'SecondController' => 'index,show,delete',
				'ThirdController' => 'create'
				),
			array(
				'FirstController' => 'new,create,edit,update',
				'SecondController' => 'delete',
				'ThirdController' => 'create'
				),
			array('SecondController' => 'show')	
			);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['43'];

		$this->assertContains('
tt_content.list.20.myextension_pi1 = USER
tt_content.list.20.myextension_pi1 {', $staticTypoScript);

		$this->assertContains('
[globalString: GP = tx_myextension_pi1|controller = FirstController] && [globalString: GP = tx_myextension_pi1|action = /new|create|edit|update/]
tt_content.list.20.myextension_pi1 = USER_INT', $staticTypoScript);

		$this->assertContains('
[globalString: GP = tx_myextension_pi1|controller = SecondController] && [globalString: GP = tx_myextension_pi1|action = /delete/]
tt_content.list.20.myextension_pi1 = USER_INT', $staticTypoScript);

		$this->assertContains('
[globalString: GP = tx_myextension_pi1|controller = ThirdController] && [globalString: GP = tx_myextension_pi1|action = /create/]
tt_content.list.20.myextension_pi1 = USER_INT', $staticTypoScript);
	}


	/**
	 * @test
	 * @see Tx_Extbase_Utility_Plugin::registerPlugin
	 */
	public function addingPluginWorksForMultipleControllerActionsWithNonCachableActionAsDefault() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
		Tx_Extbase_Utility_Plugin::registerPlugin(
			'MyExtension',
			'Pi1',
			'My Plugin Title',
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
tt_content.list.20.myextension_pi1 = USER_INT
tt_content.list.20.myextension_pi1 {', $staticTypoScript);

		$this->assertContains('
[globalString: GP = tx_myextension_pi1|controller = FirstController] && [globalString: GP = tx_myextension_pi1|action = /show|delete/]
tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);

		$this->assertContains('
[globalString: GP = tx_myextension_pi1|controller = SecondController] && [globalString: GP = tx_myextension_pi1|action = /index|show/]
tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);

		$this->assertNotContains('[globalString: GP = tx_myextension_pi1|controller = ThirdController]', $staticTypoScript);
	}

		/**
		 * @test
		 * @see Tx_Extbase_Utility_Plugin::registerPlugin
		 */
		public function addingPluginWorksForMultipleControllerActionsWithNonCachableActionAsDefaultAndOnlyNonCachableActions() {
			global $TYPO3_CONF_VARS;
			$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.'] = array();
			Tx_Extbase_Utility_Plugin::registerPlugin(
				'MyExtension',
				'Pi1',
				'My Plugin Title',
				array(
					'FirstController' => 'index,show,new,create,delete,edit,update',
					'SecondController' => 'index,show,delete',
					'ThirdController' => 'create'
					),
				array(
					'FirstController' => 'index,show,new,create,delete,edit,update',
					'SecondController' => 'index,show,delete',
					'ThirdController' => 'create'
					)
				);
			$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['43'];

			$this->assertContains('
tt_content.list.20.myextension_pi1 = USER_INT
tt_content.list.20.myextension_pi1 {', $staticTypoScript);

			$this->assertNotContains('GP', $staticTypoScript);
		}
	
	// TODO switchableControllerActionsIsSupressedIfOnlyOneControllerActionIsGiven()
	// TODO switchableControllerDingsIsGeneratedWithMultipleControllersEachHavingOnlyOneAction

}

?>