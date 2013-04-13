<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Web;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class RequestBuilderTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $requestBuilder;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockConfigurationManager;

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockExtensionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\EnvironmentService|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockEnvironmentService;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Request|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockRequest;

	public function setUp() {
		$this->requestBuilder = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Web\\RequestBuilder', array('dummy'));
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
					'actions' => array('delete', 'create', 'onlyInThirdController')
				)
			)
		);
		$this->mockConfigurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$this->mockRequest = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Request');
		$this->mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$this->mockExtensionService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\ExtensionService');
		$this->mockEnvironmentService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\EnvironmentService', array('getServerRequestMethod'));
	}

	/**
	 * @return void
	 */
	protected function injectDependencies() {
		$this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($this->mockConfigurationManager);
		$this->mockObjectManager->expects($this->any())->method('get')->with('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Request')->will($this->returnValue($this->mockRequest));
		$this->requestBuilder->injectObjectManager($this->mockObjectManager);
		$pluginNamespace = 'tx_' . strtolower(($this->configuration['extensionName'] . '_' . $this->configuration['pluginName']));
		$this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue($pluginNamespace));
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$this->mockEnvironmentService->expects($this->any())->method('getServerRequestMethod')->will($this->returnValue('GET'));
		$this->requestBuilder->injectEnvironmentService($this->mockEnvironmentService);
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
	public function buildSetsRequestRequestUri() {
		$this->injectDependencies();
		$expectedRequestUri = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
		$this->mockRequest->expects($this->once())->method('setRequestUri')->with($expectedRequestUri);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsRequestBaseUri() {
		$this->injectDependencies();
		$expectedBaseUri = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
		$this->mockRequest->expects($this->once())->method('setBaseUri')->with($expectedBaseUri);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsRequestMethod() {
		$this->injectDependencies();
		$expectedMethod = 'SomeRequestMethod';
		$mockEnvironmentService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\EnvironmentService', array('getServerRequestMethod'));
		$mockEnvironmentService->expects($this->once())->method('getServerRequestMethod')->will($this->returnValue($expectedMethod));
		$this->requestBuilder->injectEnvironmentService($mockEnvironmentService);
		$this->mockRequest->expects($this->once())->method('setMethod')->with($expectedMethod);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsVendorNameIfConfigured() {
		$this->injectDependencies();
		$expectedVendor = 'Vendor';
		$this->configuration['vendorName'] = $expectedVendor;
		$mockConfigurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($mockConfigurationManager);
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$this->mockRequest->expects($this->once())->method('setControllerVendorName')->with($expectedVendor);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildDoesNotSetVendorNameIfNotConfiguredInSecondRequest() {
		$this->injectDependencies();
		$expectedVendor = 'Vendor';
		$this->configuration['vendorName'] = $expectedVendor;

		$mockConfigurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($mockConfigurationManager);
		$this->mockRequest->expects($this->once())->method('setControllerVendorName')->with($expectedVendor);

		$this->requestBuilder->build();

		unset($this->configuration['vendorName']);
		$mockConfigurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($mockConfigurationManager);

		$this->mockRequest->expects($this->never())->method('setControllerVendorName');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
	 */
	public function buildThrowsExceptionIfExtensionNameIsNotConfigured() {
		unset($this->configuration['extensionName']);
		$mockConfigurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($mockConfigurationManager);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
	 */
	public function buildThrowsExceptionIfPluginNameIsNotConfigured() {
		unset($this->configuration['pluginName']);
		$mockConfigurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($mockConfigurationManager);
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
	 */
	public function buildThrowsExceptionIfControllerConfigurationIsEmptyOrNotSet() {
		$this->configuration['controllerConfiguration'] = array();
		$this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($this->mockConfigurationManager);
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
	 */
	public function buildThrowsExceptionIfControllerConfigurationHasNoDefaultActionDefined() {
		$this->configuration['controllerConfiguration']['TheFirstController'] = array();
		$this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($this->mockConfigurationManager);
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
	 */
	public function buildThrowsExceptionIfNoDefaultControllerCanBeResolved() {
		$this->configuration['controllerConfiguration'] = array(
			'' => array(
				'actions' => array('foo')
			)
		);
		$this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($this->mockConfigurationManager);
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
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
		$this->mockRequest->expects($this->at(8))->method('setArgument')->with('parameter1', 'value1');
		$this->mockRequest->expects($this->at(9))->method('setArgument')->with('parameter2', array('parameter3' => 'value3', 'parameter4' => 'value4'));
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
				'format' => 'GET'
			)
		);
		$_POST = array(
			'tx_someextensionname_somepluginname' => array(
				'format' => 'POST'
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
				'show',
				'index',
				'new',
				'create',
				'delete',
				'edit',
				'update',
				'setup',
				'test'
			),
			'TheSecondController' => array(
				'show',
				'index'
			),
			'TheThirdController' => array(
				'delete',
				'create',
				'onlyInThirdController'
			)
		);
		$this->requestBuilder->build();
		$actualResult = $this->requestBuilder->_get('allowedControllerActions');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
	 */
	public function buildThrowsExceptionIfDefaultControllerCantBeDetermined() {
		$this->configuration['controllerConfiguration'] = array();
		$this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($this->mockConfigurationManager);
		$this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsDefaultControllerIfNoControllerIsSpecified() {
		$this->injectDependencies();
		$_GET = array(
			'tx_myextension_pi1' => array(
				'foo' => 'bar'
			)
		);
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('TheFirstController');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildCorrectlySetsSpecifiedControllerNameIfItsAllowedForTheCurrentPlugin() {
		$this->injectDependencies();
		$_GET = array(
			'tx_myextension_pi1' => array(
				'controller' => 'TheSecondController'
			)
		);
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('TheSecondController');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
	 */
	public function buildThrowsInvalidControllerNameExceptionIfSpecifiedControllerIsNotAllowed() {
		$this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($this->mockConfigurationManager);
		$this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$_GET = array(
			'tx_myextension_pi1' => array(
				'controller' => 'SomeInvalidController'
			)
		);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Error\Http\PageNotFoundException
	 */
	public function buildThrowsPageNotFoundExceptionIfEnabledAndSpecifiedControllerIsNotAllowed() {
		$this->configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved'] = 1;
		$this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($this->mockConfigurationManager);
		$this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$_GET = array(
			'tx_myextension_pi1' => array(
				'controller' => 'SomeInvalidController'
			)
		);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsDefaultControllerNameIfSpecifiedControllerIsNotAllowedAndCallDefaultActionIfActionCantBeResolvedIsSet() {
		$this->configuration['mvc']['callDefaultActionIfActionCantBeResolved'] = 1;
		$this->injectDependencies();
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$_GET = array(
			'tx_myextension_pi1' => array(
				'controller' => 'SomeInvalidController'
			)
		);
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('TheFirstController');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
	 */
	public function buildThrowsExceptionIfDefaultActionCantBeDetermined() {
		$this->configuration['controllerConfiguration'] = array();
		$this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($this->mockConfigurationManager);
		$this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsDefaultActionOfTheCurrentControllerIfNoActionIsSpecified() {
		$this->injectDependencies();
		$_GET = array(
			'tx_myextension_pi1' => array(
				'controller' => 'TheThirdController'
			)
		);
		$this->mockRequest->expects($this->once())->method('setControllerActionName')->with('delete');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildCorrectlySetsSpecifiedActionNameForTheDefaultControllerIfItsAllowedForTheCurrentPlugin() {
		$this->injectDependencies();
		$_GET = array(
			'tx_myextension_pi1' => array(
				'action' => 'create'
			)
		);
		$this->mockRequest->expects($this->once())->method('setControllerActionName')->with('create');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildCorrectlySetsSpecifiedActionNameForTheSpecifiedControllerIfItsAllowedForTheCurrentPlugin() {
		$this->injectDependencies();
		$_GET = array(
			'tx_myextension_pi1' => array(
				'controller' => 'TheThirdController',
				'action' => 'onlyInThirdController'
			)
		);
		$this->mockRequest->expects($this->once())->method('setControllerActionName')->with('onlyInThirdController');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
	 */
	public function buildThrowsInvalidActionNameExceptionIfSpecifiedActionIsNotAllowed() {
		$this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($this->mockConfigurationManager);
		$this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$_GET = array(
			'tx_myextension_pi1' => array(
				'action' => 'someInvalidAction'
			)
		);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Error\Http\PageNotFoundException
	 */
	public function buildThrowsPageNotFoundExceptionIfEnabledAndSpecifiedActionIsNotAllowed() {
		$this->configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved'] = 1;
		$this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
		$this->requestBuilder->injectConfigurationManager($this->mockConfigurationManager);
		$this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$_GET = array(
			'tx_myextension_pi1' => array(
				'action' => 'someInvalidAction'
			)
		);
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 */
	public function buildSetsDefaultActionNameIfSpecifiedActionIsNotAllowedAndCallDefaultActionIfActionCantBeResolvedIsSet() {
		$this->configuration['mvc']['callDefaultActionIfActionCantBeResolved'] = 1;
		$this->injectDependencies();
		$this->requestBuilder->injectExtensionService($this->mockExtensionService);
		$_GET = array(
			'tx_myextension_pi1' => array(
				'controller' => 'TheThirdController',
				'action' => 'someInvalidAction'
			)
		);
		$this->mockRequest->expects($this->once())->method('setControllerName')->with('TheThirdController');
		$this->mockRequest->expects($this->once())->method('setControllerActionName')->with('delete');
		$this->requestBuilder->build();
	}

	/**
	 * @test
	 * @see TYPO3\FLOW3\Tests\Unit\Utility\EnvironmentTest
	 */
	public function untangleFilesArrayTransformsTheFilesSuperglobalIntoAManageableForm() {
		$convolutedFiles = array(
			'a0' => array(
				'name' => array(
					'a1' => 'a.txt'
				),
				'type' => array(
					'a1' => 'text/plain'
				),
				'tmp_name' => array(
					'a1' => '/private/var/tmp/phpbqXsYt'
				),
				'error' => array(
					'a1' => 0
				),
				'size' => array(
					'a1' => 100
				)
			),
			'b0' => array(
				'name' => array(
					'b1' => 'b.txt'
				),
				'type' => array(
					'b1' => 'text/plain'
				),
				'tmp_name' => array(
					'b1' => '/private/var/tmp/phpvZ6oUD'
				),
				'error' => array(
					'b1' => 0
				),
				'size' => array(
					'b1' => 200
				)
			),
			'c' => array(
				'name' => 'c.txt',
				'type' => 'text/plain',
				'tmp_name' => '/private/var/tmp/phpS9KMNw',
				'error' => 0,
				'size' => 300
			),
			'd0' => array(
				'name' => array(
					'd1' => array(
						'd2' => array(
							'd3' => 'd.txt'
						)
					)
				),
				'type' => array(
					'd1' => array(
						'd2' => array(
							'd3' => 'text/plain'
						)
					)
				),
				'tmp_name' => array(
					'd1' => array(
						'd2' => array(
							'd3' => '/private/var/tmp/phprR3fax'
						)
					)
				),
				'error' => array(
					'd1' => array(
						'd2' => array(
							'd3' => 0
						)
					)
				),
				'size' => array(
					'd1' => array(
						'd2' => array(
							'd3' => 400
						)
					)
				)
			),
			'e0' => array(
				'name' => array(
					'e1' => array(
						'e2' => array(
							0 => 'e_one.txt',
							1 => 'e_two.txt'
						)
					)
				),
				'type' => array(
					'e1' => array(
						'e2' => array(
							0 => 'text/plain',
							1 => 'text/plain'
						)
					)
				),
				'tmp_name' => array(
					'e1' => array(
						'e2' => array(
							0 => '/private/var/tmp/php01fitB',
							1 => '/private/var/tmp/phpUUB2cv'
						)
					)
				),
				'error' => array(
					'e1' => array(
						'e2' => array(
							0 => 0,
							1 => 0
						)
					)
				),
				'size' => array(
					'e1' => array(
						'e2' => array(
							0 => 510,
							1 => 520
						)
					)
				)
			)
		);
		$untangledFiles = array(
			'a0' => array(
				'a1' => array(
					'name' => 'a.txt',
					'type' => 'text/plain',
					'tmp_name' => '/private/var/tmp/phpbqXsYt',
					'error' => 0,
					'size' => 100
				)
			),
			'b0' => array(
				'b1' => array(
					'name' => 'b.txt',
					'type' => 'text/plain',
					'tmp_name' => '/private/var/tmp/phpvZ6oUD',
					'error' => 0,
					'size' => 200
				)
			),
			'c' => array(
				'name' => 'c.txt',
				'type' => 'text/plain',
				'tmp_name' => '/private/var/tmp/phpS9KMNw',
				'error' => 0,
				'size' => 300
			),
			'd0' => array(
				'd1' => array(
					'd2' => array(
						'd3' => array(
							'name' => 'd.txt',
							'type' => 'text/plain',
							'tmp_name' => '/private/var/tmp/phprR3fax',
							'error' => 0,
							'size' => 400
						)
					)
				)
			),
			'e0' => array(
				'e1' => array(
					'e2' => array(
						0 => array(
							'name' => 'e_one.txt',
							'type' => 'text/plain',
							'tmp_name' => '/private/var/tmp/php01fitB',
							'error' => 0,
							'size' => 510
						),
						1 => array(
							'name' => 'e_two.txt',
							'type' => 'text/plain',
							'tmp_name' => '/private/var/tmp/phpUUB2cv',
							'error' => 0,
							'size' => 520
						)
					)
				)
			)
		);
		$requestBuilder = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Web\\RequestBuilder', array('dummy'), array(), '', FALSE);
		$result = $requestBuilder->_call('untangleFilesArray', $convolutedFiles);
		$this->assertSame($untangledFiles, $result);
	}
}

?>