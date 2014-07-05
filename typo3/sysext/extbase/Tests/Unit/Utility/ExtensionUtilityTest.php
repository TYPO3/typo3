<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

/**
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
/**
 * Testcase for class \TYPO3\CMS\Extbase\Utility\ExtensionUtility
 */
class ExtensionUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	public function setUp() {
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

	/**
	 * Tests method combination of registerPlugin() and its dependency addPlugin() to
	 * verify plugin icon path resolving works.
	 *
	 * @test
	 */
	public function registerPluginTriggersAddPluginWhichSetsPluginIconPathIfUsingUnderscoredExtensionNameAndIconPathNotGiven() {
		$GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = array();
		$GLOBALS['TYPO3_LOADED_EXT'] = array();
		$GLOBALS['TYPO3_LOADED_EXT']['indexed_search']['ext_icon'] = 'foo.gif';
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
			'indexed_search',
			'Pi2',
			'Testing'
		);
		$this->assertEquals(
			'sysext/indexed_search/foo.gif',
			$GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0][2]
		);
	}

	/**
	 * Tests method combination of registerPlugin() and its dependency addPlugin() to
	 * verify plugin icon path resolving works.
	 *
	 * @test
	 */
	public function registerPluginTriggersAddPluginWhichSetsPluginIconPathIfUsingUpperCameCasedExtensionNameAndIconPathNotGiven() {
		$GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = array();
		$GLOBALS['TYPO3_LOADED_EXT'] = array();
		$GLOBALS['TYPO3_LOADED_EXT']['indexed_search']['ext_icon'] = 'foo.gif';
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
			'IndexedSearch',
			'Pi2',
			'Testing'
		);
		$this->assertEquals(
			'sysext/indexed_search/foo.gif',
			$GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0][2]
		);
	}

	/**
	 * Tests method combination of registerPlugin() and its dependency addPlugin() to
	 * verify plugin icon path resolving works.
	 *
	 * @test
	 */
	public function registerPluginTriggersAddPluginWhichSetsPluginIconPathIfIconPathIsGiven() {
		$GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = array();
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
			'IndexedSearch',
			'Pi2',
			'Testing',
			'sysext/indexed_search/foo.gif'
		);
		$this->assertEquals(
			'sysext/indexed_search/foo.gif',
			$GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'][0][2]
		);
	}
}
