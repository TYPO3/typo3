!Stack Underflow Error!

<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
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

class Tx_ExtBase_MVC_Web_RequestBuilder_testcase extends Tx_ExtBase_Base_testcase {
	
	public function __construct() {
		require_once(t3lib_extMgm::extPath('extbase', 'Classes/MVC/Web/RequestBuilder.php'));
	}
	
	public function setUp() {
		$this->configuration = array(
			'userFunc' => 'tx_extbase_dispatcher->dispatch',
			'pluginKey' => 'myplugin',
			'extensionName' => 'MyExtension',
			'controllers.' => array(
				'10.' => array(
					'controllerName' => 'TheFirstController',
					'actions' => ',show,index, ,new,create,delete,edit,update,setup,test'
					),
				'20.' => array(
					'controllerName' => 'TheSecondController',
					'actions' => 'show, index'
					),
				'30.' => array(
					'controllerName' => 'TheThirdController',
					'actions' => 'delete,create'
					)
				)
			);
		$this->builder = new Tx_ExtBase_MVC_Web_RequestBuilder;
	}
	
	public function test_BuildReturnsAWebRequestObject() {
		$this->builder->initialize($this->configuration);
		$request = $this->builder->build();
		$this->assertEquals('Tx_ExtBase_MVC_Web_Request', get_class($request));
		$this->assertEquals('myplugin', $request->getPluginKey());
		$this->assertEquals('MyExtension', $request->getExtensionName());
		$this->assertEquals('TheFirstController', $request->getControllerName());
		$this->assertEquals('show', $request->getControllerActionName());
	}
	
	public function test_BuildWithoutConfigurationReturnsAWebRequestObjectWithDefaultSettings() {
		$request = $this->builder->build();
		$this->assertEquals('plugin', $request->getPluginKey());
		$this->assertEquals('ExtBase', $request->getExtensionName());
		$this->assertEquals('Default', $request->getControllerName());
		$this->assertEquals('index', $request->getControllerActionName());
	}

	public function test_BuildWithMissingControllerConfigurationsReturnsAWebRequestObjectWithDefaultControllerSettings() {
		$configuration = $this->configuration;
		unset($configuration['controllers.']);
		$this->builder->initialize($configuration);
		$request = $this->builder->build();
		$this->assertEquals('myplugin', $request->getPluginKey());
		$this->assertEquals('MyExtension', $request->getExtensionName());
		$this->assertEquals('Default', $request->getControllerName());
		$this->assertEquals('index', $request->getControllerActionName());
	}
	
	public function test_BuildWithMissingActionsReturnsAWebRequestObjectWithDefaultControllerSettings() {
		$configuration = $this->configuration;
		$configuration['controllers.'] = array(
			'10.' => array(
				'actions' => ',show,index, ,new,create,delete,edit,update,setup,test'
				)
			);
		$this->builder->initialize($configuration);
		$request = $this->builder->build();
		$this->assertEquals('myplugin', $request->getPluginKey());
		$this->assertEquals('MyExtension', $request->getExtensionName());
		$this->assertEquals('Default', $request->getControllerName());
		$this->assertEquals('index', $request->getControllerActionName());
	}

	public function test_BuildSetsTheRequestURIInTheRequestObject() {
		$this->builder->initialize($this->configuration);
		$request = $this->builder->build();
		$this->assertEquals(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'), $request->getRequestURI());
	}

}
?>
