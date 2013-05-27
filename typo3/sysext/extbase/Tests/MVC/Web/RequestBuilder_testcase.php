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

class Tx_Extbase_MVC_Web_RequestBuilder_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @var array
	 */
	protected $getBackup = array();

	/**
	 * @var array
	 */
	protected $postBackup = array();

	public function __construct() {
		require_once(t3lib_extMgm::extPath('extbase', 'Classes/MVC/Web/RequestBuilder.php'));
	}

	public function setUp() {
		$this->configuration = array(
			'userFunc' => 'Tx_Extbase_Dispatcher->dispatch',
			'pluginName' => 'pi1',
			'extensionName' => 'MyExtension',
			'controller' => 'TheFirstController',
			'action' => 'show',
			'switchableControllerActions.' => array(
				'1.' => array(
					'controller' => 'TheFirstController',
					'actions' => 'show,index, ,new,create,delete,edit,update,setup,test'
					),
				'2.' => array(
					'controller' => 'TheSecondController',
					'actions' => 'show, index'
					),
				'3.' => array(
					'controller' => 'TheThirdController',
					'actions' => 'delete,create'
					)
				)
			);
		$this->builder = new Tx_Extbase_MVC_Web_RequestBuilder;
		$this->getBackup = $_GET;
		$this->postBackup = $_POST;
	}

	public function tearDown() {
		$_GET = $this->getBackup;
		$_POST = $this->postBackup;
	}

	/**
	 * @test
	 */
	public function buildReturnsAWebRequestObject() {
		$this->builder->initialize($this->configuration);
		$request = $this->builder->build();
		$this->assertEquals('Tx_Extbase_MVC_Web_Request', get_class($request));
		$this->assertEquals('pi1', $request->getPluginName());
		$this->assertEquals('MyExtension', $request->getControllerExtensionName());
		$this->assertEquals('TheFirstController', $request->getControllerName());
		$this->assertEquals('show', $request->getControllerActionName());
	}

	/**
	 * @test
	 */
	public function buildWithoutConfigurationReturnsAWebRequestObjectWithDefaultSettings() {
		$request = $this->builder->build();
		$this->assertEquals('plugin', $request->getPluginName());
		$this->assertEquals('Extbase', $request->getControllerExtensionName());
		$this->assertEquals('Standard', $request->getControllerName());
		$this->assertEquals('index', $request->getControllerActionName());
	}

	/**
	 * @test
	 */
	public function buildWithMissingControllerConfigurationsReturnsAWebRequestObjectWithDefaultControllerSettings() {
		$configuration = $this->configuration;
		unset($configuration['controller']);
		unset($configuration['action']);
		unset($configuration['switchableControllerActions.']);
		$this->builder->initialize($configuration);
		$request = $this->builder->build();
		$this->assertEquals('pi1', $request->getPluginName());
		$this->assertEquals('MyExtension', $request->getControllerExtensionName());
		$this->assertEquals('Standard', $request->getControllerName());
		$this->assertEquals('index', $request->getControllerActionName());
	}

	/**
	 * @test
	 */
	public function buildWithMissingActionsReturnsAWebRequestObjectWithDefaultControllerSettings() {
		$configuration = $this->configuration;
		unset($configuration['action']);
		$this->builder->initialize($configuration);
		$request = $this->builder->build();
		$this->assertEquals('pi1', $request->getPluginName());
		$this->assertEquals('MyExtension', $request->getControllerExtensionName());
		$this->assertEquals('TheFirstController', $request->getControllerName());
		$this->assertEquals('index', $request->getControllerActionName());
	}

	/**
	 * @test
	 */
	public function buildSetsTheRequestURIInTheRequestObject() {
		$this->builder->initialize($this->configuration);
		$request = $this->builder->build();
		$this->assertEquals(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'), $request->getRequestURI());
	}

	/**
	 * @test
	 */
	public function buildSetsParametersFromGetAndPostVariables() {
		$builder = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Web_RequestBuilder'), array('dummy'));
		$builder->_set('extensionName', 'someExtensionName');
		$builder->_set('pluginName', 'somePluginName');

		$_GET = array(
			'tx_someotherextensionname_somepluginname' => array(
				'foo' => 'bar'
			),
			'tx_someextensionname_somepluginname' => array(
				'parameter1' => 'valueGetsOverwritten',
				'parameter2' => array(
					'parameter3' => 'value3'
				)
			)
		);
		$_POST = array(
			'tx_someextensionname_someotherpluginname' => array(
				'foo' => 'bar'
			),
			'tx_someextensionname_somepluginname' => array(
				'parameter1' => 'value1',
				'parameter2' => array(
					'parameter4' => 'value4'
				)
			)
		);

		$request = $builder->build();
		$expectedResult = array(
			'parameter1' => 'value1',
			'parameter2' => array(
				'parameter3' => 'value3',
				'parameter4' => 'value4',
			),
		);
		$actualResult = $request->getArguments();
		$this->assertEquals($expectedResult, $actualResult);
	}

}
?>
