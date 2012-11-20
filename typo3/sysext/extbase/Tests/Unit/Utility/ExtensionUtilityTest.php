<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

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
 * Testcase for class \TYPO3\CMS\Extbase\Utility\ExtensionUtility
 */
class ExtensionUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * A backup of the global database
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseBackup = NULL;

	public function setUp() {
		$this->databaseBackup = $GLOBALS['TYPO3_DB'];
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('fullQuoteStr', 'exec_SELECTgetRows'));

		$GLOBALS['TSFE'] = new \stdClass();
		$GLOBALS['TSFE']->tmpl = new \stdClass();
		$GLOBALS['TSFE']->tmpl->setup = array();
		$GLOBALS['TSFE']->tmpl->setup['tt_content.']['list.']['20.'] = array(
			'9' => 'CASE',
			'9.' => array(
				'key.' => array(
					'field' => 'layout'
				),
				0 => '< plugin.tt_news'
			),
			'extensionname_someplugin' => 'USER',
			'extensionname_someplugin.' => array(
				'userFunc' => 'TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run',
				'extensionName' => 'ExtensionName',
				'pluginName' => 'SomePlugin'
			),
			'someotherextensionname_secondplugin' => 'USER',
			'someotherextensionname_secondplugin.' => array(
				'userFunc' => 'TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run',
				'extensionName' => 'SomeOtherExtensionName',
				'pluginName' => 'SecondPlugin'
			),
			'extensionname_thirdplugin' => 'USER',
			'extensionname_thirdplugin.' => array(
				'userFunc' => 'TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run',
				'extensionName' => 'ExtensionName',
				'pluginName' => 'ThirdPlugin'
			)
		);
	}

	public function tearDown() {
		$GLOBALS['TYPO3_DB'] = $this->databaseBackup;
	}

	/**
	 * @test
	 * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
	 */
	public function configurePluginWorksForMinimalisticSetup() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('MyExtension', 'Pi1', array('Blog' => 'index'));
		$staticTypoScript = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['43'];
		$this->assertContains('tt_content.list.20.myextension_pi1 = USER', $staticTypoScript);
		$this->assertContains('
	userFunc = TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run
	extensionName = MyExtension
	pluginName = Pi1', $staticTypoScript);
		$this->assertNotContains('USER_INT', $staticTypoScript);
	}

	/**
	 * @test
	 * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
	 */
	public function configurePluginCreatesCorrectDefaultTypoScriptSetup() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('MyExtension', 'Pi1', array('Blog' => 'index'));
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
	 * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
	 */
	public function configurePluginWorksForASingleControllerAction() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('MyExtension', 'Pi1', array(
			'FirstController' => 'index'
		));
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
	 * @expectedException \InvalidArgumentException
	 * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
	 */
	public function configurePluginThrowsExceptionIfExtensionNameIsEmpty() {
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('', 'SomePlugin', array(
			'FirstController' => 'index'
		));
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
	 */
	public function configurePluginThrowsExceptionIfPluginNameIsEmpty() {
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('MyExtension', '', array(
			'FirstController' => 'index'
		));
	}

	/**
	 * @test
	 * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
	 */
	public function configurePluginRespectsDefaultActionAsANonCacheableAction() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('MyExtension', 'Pi1', array(
			'FirstController' => 'index,show,new, create,delete,edit,update'
		), array(
			'FirstController' => 'index,show'
		));
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
	 * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
	 */
	public function configurePluginRespectsNonDefaultActionAsANonCacheableAction() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('MyExtension', 'Pi1', array(
			'FirstController' => 'index,show,new, create,delete,edit,update'
		), array(
			'FirstController' => 'new,show'
		));
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
	 * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
	 */
	public function configurePluginWorksForMultipleControllerActionsWithCacheableActionAsDefault() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('MyExtension', 'Pi1', array(
			'FirstController' => 'index,show,new,create,delete,edit,update',
			'SecondController' => 'index,show,delete',
			'ThirdController' => 'create'
		), array(
			'FirstController' => 'new,create,edit,update',
			'ThirdController' => 'create'
		));
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
	 * @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin
	 */
	public function configurePluginWorksForMultipleControllerActionsWithNonCacheableActionAsDefault() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'] = array();
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('MyExtension', 'Pi1', array(
			'FirstController' => 'index,show,new,create,delete,edit,update',
			'SecondController' => 'index,show,delete',
			'ThirdController' => 'create'
		), array(
			'FirstController' => 'index,new,create,edit,update',
			'SecondController' => 'delete',
			'ThirdController' => 'create'
		));
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
}

?>