<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * Testcase for class t3lib_extmgm
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_extMgm_testcase extends tx_phpunit_testcase {
	
	public function setUp() {
		global $TYPO3_CONF_VARS;
		$this->TYPO3_CONF_VARS_backup = $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'] = '';
	}
	
	public function tearDown() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS = $this->TYPO3_CONF_VARS_before;
	}
	
	/**
	 * @test
	 */
	public function addingTsWorksForMinimalisticSetup() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'] = '';
		t3lib_extMgm::addExtbasePlugin(
			'pluginkey',
			'my_extension'
		);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'];		

		$this->assertContains('includeLibs.tx_extbase_dispatcher = EXT:extbase/class.tx_extbase_dispatcher.php', $staticTypoScript, 'Did not include "tx_extbase_dispatcher".');
		$this->assertContains('tt_content.list.20.my_extension_pluginkey = USER', $staticTypoScript);
		$this->assertContains('
	pluginKey = pluginkey
	extensionName = MyExtension', $staticTypoScript);
	$this->assertNotContains('controller =', $staticTypoScript);
	$this->assertNotContains('action =', $staticTypoScript);
		$this->assertNotContains('USER_INT', $staticTypoScript);
	}
	
	/**
	 * @test
	 */
	public function addingTsWorksForASingleControllerAction() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'] = '';
		t3lib_extMgm::addExtbasePlugin(
			'pluginkey',
			'my_extension',
			array(
				'FirstController' => 'index'
				)
		);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'];		

		$this->assertContains('includeLibs.tx_extbase_dispatcher = EXT:extbase/class.tx_extbase_dispatcher.php', $staticTypoScript, 'Did not include "tx_extbase_dispatcher".');
		$this->assertContains('tt_content.list.20.my_extension_pluginkey = USER', $staticTypoScript);
		$this->assertContains('
	pluginKey = pluginkey
	extensionName = MyExtension
	controller = FirstController
	action = index', $staticTypoScript);
		$this->assertNotContains('USER_INT', $staticTypoScript);
	}
	
	/**
	 * @test
	 */
	public function addingPluginWithEmptyPluginKeyResultsInAnError() {
		$this->setExpectedException('InvalidArgumentException');
		t3lib_extMgm::addExtbasePlugin(
			'',
			'my_extension',
			array(
				'FirstController' => 'index'
				)
		);
	}
	
	/**
	 * @test
	 */
	public function addingPluginWithEmptyExtensionKeyResultsInAnError() {
		$this->setExpectedException('InvalidArgumentException');
		t3lib_extMgm::addExtbasePlugin(
			'pluginKey',
			'',
			array(
				'FirstController' => 'index'
				)
		);
	}

	/**
	 * @test
	 */
	public function addingPluginWithInvalidExtensionKeyResultsInAnError() {
		$this->setExpectedException('InvalidArgumentException');
		t3lib_extMgm::addExtbasePlugin(
			'pluginKey',
			'MyExtension',
			array(
				'FirstController' => 'index'
				)
		);
	}

	/**
	 * @test
	 */
	public function theDefaultControllerActionCanBeSetManually() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'] = '';
		t3lib_extMgm::addExtbasePlugin(
			'pluginkey',
			'my_extension',
			array(
				'FirstController' => 'index,show,new,create,delete,edit,update',
				'SecondController' => 'index,show,delete',
				'ThirdController' => 'create'
				),
			array(),
			array('SecondController' => 'show')	
			);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'];		

		$this->assertContains('
	pluginKey = pluginkey
	extensionName = MyExtension
	controller = SecondController
	action = show', $staticTypoScript);
	}

	/**
	 * @test
	 */
	public function addingPluginRespectsDefaultActionAsANonCachableAction() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'] = '';
		t3lib_extMgm::addExtbasePlugin(
			'pluginkey',
			'my_extension',
			array(
				'FirstController' => 'index,show,new,create,delete,edit,update'
				),
			array(
				'FirstController' => 'index,show'
				)
			);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'];		

		$this->assertContains('
tt_content.list.20.my_extension_pluginkey = USER_INT
tt_content.list.20.my_extension_pluginkey {', $staticTypoScript);
		$this->assertContains('
[globalString: GP = tx_myextension_pluginkey|controller = FirstController] && [globalString: GP = tx_myextension_pluginkey|action = /new|create|delete|edit|update/]
tt_content.list.20.my_extension_pluginkey = USER', $staticTypoScript);
	}

	/**
	 * @test
	 */
	public function addingPluginRespectsNonDefaultActionAsANonCachableAction() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'] = '';
		t3lib_extMgm::addExtbasePlugin(
			'pluginkey',
			'my_extension',
			array(
				'FirstController' => 'index,show,new,create,delete,edit,update'
				),
			array(
				'FirstController' => 'show,new'
				)
			);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'];		

		$this->assertContains('
tt_content.list.20.my_extension_pluginkey = USER
tt_content.list.20.my_extension_pluginkey {', $staticTypoScript);
		$this->assertContains('
[globalString: GP = tx_myextension_pluginkey|controller = FirstController] && [globalString: GP = tx_myextension_pluginkey|action = /show|new/]
tt_content.list.20.my_extension_pluginkey = USER_INT', $staticTypoScript);
	}

	/**
	 * @test
	 */
	public function addingPluginWorksForMultipleControllerActionsWithCachableActionAsDefault() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'] = '';
		t3lib_extMgm::addExtbasePlugin(
			'pluginkey',
			'my_extension',
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
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'];		
	
		$this->assertContains('
tt_content.list.20.my_extension_pluginkey = USER
tt_content.list.20.my_extension_pluginkey {', $staticTypoScript);

		$this->assertContains('
[globalString: GP = tx_myextension_pluginkey|controller = FirstController] && [globalString: GP = tx_myextension_pluginkey|action = /new|create|edit|update/]
tt_content.list.20.my_extension_pluginkey = USER_INT', $staticTypoScript);

		$this->assertContains('
[globalString: GP = tx_myextension_pluginkey|controller = SecondController] && [globalString: GP = tx_myextension_pluginkey|action = /delete/]
tt_content.list.20.my_extension_pluginkey = USER_INT', $staticTypoScript);

		$this->assertContains('
[globalString: GP = tx_myextension_pluginkey|controller = ThirdController] && [globalString: GP = tx_myextension_pluginkey|action = /create/]
tt_content.list.20.my_extension_pluginkey = USER_INT', $staticTypoScript);
	}


	/**
	 * @test
	 */
	public function addingPluginWorksForMultipleControllerActionsWithNonCachableActionAsDefault() {
		global $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'] = '';
		t3lib_extMgm::addExtbasePlugin(
			'pluginkey',
			'my_extension',
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
			array('SecondController' => 'delete')	
			);
		$staticTypoScript = $TYPO3_CONF_VARS['FE']['defaultTypoScript_setup.']['cssstyledcontent/static/current/'];		

		$this->assertContains('
tt_content.list.20.my_extension_pluginkey = USER_INT
tt_content.list.20.my_extension_pluginkey {', $staticTypoScript);

		$this->assertContains('
[globalString: GP = tx_myextension_pluginkey|controller = FirstController] && [globalString: GP = tx_myextension_pluginkey|action = /index|show|delete/]
tt_content.list.20.my_extension_pluginkey = USER', $staticTypoScript);

		$this->assertContains('
[globalString: GP = tx_myextension_pluginkey|controller = SecondController] && [globalString: GP = tx_myextension_pluginkey|action = /index|show/]
tt_content.list.20.my_extension_pluginkey = USER', $staticTypoScript);

		$this->assertNotContains('[globalString: GP = tx_myextension_pluginkey|controller = ThirdController]', $staticTypoScript);
	}


}

?>