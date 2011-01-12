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

class Tx_Extbase_Tests_Unit_MVC_Web_RequestBuilderTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_MVC_Web_RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $mockConfigurationManager;

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var Tx_Extbase_MVC_Web_Request
	 */
	protected $mockRequest;

	/**
	 * @var array
	 */
	protected $getBackup = array();

	/**
	 * @var array
	 */
	protected $postBackup = array();

	/**
	 * @var array
	 */
	protected $serverBackup = array();

	public function setUp() {
		$this->requestBuilder = $this->getAccessibleMock('Tx_Extbase_MVC_Web_RequestBuilder', array('dummy'));
		$this->configuration = array(
			'userFunc' => 'Tx_Extbase_Dispatcher->dispatch',
			'pluginName' => 'Pi1',
			'extensionName' => 'MyExtension',
			'controller' => 'TheFirstController',
			'action' => 'show',
			'controllerConfiguration' => array(
				'TheFirstController' => array(
					'actions' => array('show', 'index', 'new', 'create', 'delete', 'edit', 'update', 'setup', 'test')
				),
				'TheSecondController' => array(
					'actions' => array('show', 'index')
				),
				'TheThirdController' => array(
					'actions' => array('delete', 'create')
				)
			)
		);
		$this->mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$this->mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request');
		$this->mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');

		$this->getBackup = $_GET;
		$this->postBackup = $_POST;
		$this->serverBackup = $_SERVER;
	}

	/**
	 * @return void
	 */
	protected function injectDependencies() {
		$this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($this->mockConfigurationManager);

		$this->mockObjectManager->expects($this->any())->method('create')->with('Tx_Extbase_MVC_Web_Request')->will($this->returnValue($this->mockRequest));
		$this->requestBuilder->injectObjectManager($this->mockObjectManager);
	}

	public function tearDown() {
		$_GET = $this->getBackup;
		$_POST = $this->postBackup;
		$_SERVER = $this->serverBackup;
	}

	/**
	 * @test
	 */
	public function buildReturnsAWebRequestObject() {
		$this->injectDependencies();
		$request = $this->requestBuilder->build();
		$this->assertSame($this->mockRequest, $request);
	}

	/**
	 * @test
	 */
	public function buildSetsRequestPluginName() {
		$this->injectDependencies();
		$this->mockRequest->expects($this->once())->method('setPluginName')->with('Pi1');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsRequestControllerExtensionName() {
		$this->injectDependencies();
		$this->mockRequest->expects($this->once())->method('setControllerExtensionName')->with('MyExtension');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsRequestControllerName() {
		$this->injectDependencies();
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('TheFirstController');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsRequestControllerActionName() {
		$this->injectDependencies();
		$this->mockRequest->expects($this->once())->method('setControllerActionName')->with('show');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsRequestRequestURI() {
		$this->injectDependencies();
		$expectedRequestUri = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
		$this->mockRequest->expects($this->once())->method('setRequestURI')->with($expectedRequestUri);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsRequestBaseURI() {
		$this->injectDependencies();
		$expectedBaseUri = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$this->mockRequest->expects($this->once())->method('setBaseURI')->with($expectedBaseUri);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsRequestMethod() {
		$this->injectDependencies();
		$_SERVER['REQUEST_METHOD'] = 'SomeRequestMethod';
		$expectedMethod = 'SomeRequestMethod';
		$this->mockRequest->expects($this->once())->method('setMethod')->with($expectedMethod);
		$this->requestBuilder->build();
	}


	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception
	 */
	public function buildThrowsExceptionIfExtensionNameIsNotConfigured() {
		unset($this->configuration['extensionName']);
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($mockConfigurationManager);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception
	 */
	public function buildThrowsExceptionIfPluginNameIsNotConfigured() {
		unset($this->configuration['pluginName']);
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($mockConfigurationManager);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsParametersFromGetAndPostVariables() {
		$this->configuration['extensionName'] = 'SomeExtensionName';
		$this->configuration['pluginName'] = 'SomePluginName';
		$this->injectDependencies();

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
		$this->mockRequest->expects($this->at(7))->method('setArgument')->with('parameter1', 'value1');
		$this->mockRequest->expects($this->at(8))->method('setArgument')->with('parameter2', array('parameter3' => 'value3', 'parameter4' => 'value4'));

		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsFormatFromGetAndPostVariables() {
		$this->configuration['extensionName'] = 'SomeExtensionName';
		$this->configuration['pluginName'] = 'SomePluginName';
		$this->injectDependencies();

		$_GET = array(
			'tx_someextensionname_somepluginname' => array(
				'format' => 'GET',
			)
		);
		$_POST = array(
			'tx_someextensionname_somepluginname' => array(
				'format' => 'POST',
			)
		);
		$this->mockRequest->expects($this->at(7))->method('setFormat')->with('POST');

		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildCorrectlySetsAllowedControllerActions() {
		$this->injectDependencies();
		$expectedResult = array(
			'TheFirstController' => array(
				'show', 'index', 'new', 'create', 'delete', 'edit', 'update', 'setup', 'test'
			),
			'TheSecondController' => array(
				'show', 'index'
			),
			'TheThirdController' => array(
				'delete', 'create'
			)
		);
		$this->requestBuilder->build();
		$actualResult = $this->requestBuilder->_get('allowedControllerActions');
		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>