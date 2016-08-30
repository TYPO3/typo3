<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Web;

/*
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
 * Test case
 */
class RequestBuilderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
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

    protected function setUp()
    {
        $this->requestBuilder = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder::class, ['dummy']);
        $this->configuration = [
            'userFunc' => 'Tx_Extbase_Dispatcher->dispatch',
            'pluginName' => 'Pi1',
            'extensionName' => 'MyExtension',
            'controller' => 'TheFirstController',
            'action' => 'show',
            'controllerConfiguration' => [
                'TheFirstController' => [
                    'actions' => ['show', 'index', 'new', 'create', 'delete', 'edit', 'update', 'setup', 'test']
                ],
                'TheSecondController' => [
                    'actions' => ['show', 'index']
                ],
                'TheThirdController' => [
                    'actions' => ['delete', 'create', 'onlyInThirdController']
                ]
            ]
        ];
        $this->mockConfigurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $this->mockRequest = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Web\Request::class);
        $this->mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->mockExtensionService = $this->getMock(\TYPO3\CMS\Extbase\Service\ExtensionService::class);
        $this->mockEnvironmentService = $this->getMock(\TYPO3\CMS\Extbase\Service\EnvironmentService::class, ['getServerRequestMethod']);
    }

    /**
     * @return void
     */
    protected function injectDependencies()
    {
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $this->mockConfigurationManager);
        $this->mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Web\Request::class)->will($this->returnValue($this->mockRequest));
        $this->requestBuilder->_set('objectManager', $this->mockObjectManager);
        $pluginNamespace = 'tx_' . strtolower(($this->configuration['extensionName'] . '_' . $this->configuration['pluginName']));
        $this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue($pluginNamespace));
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $this->mockEnvironmentService->expects($this->any())->method('getServerRequestMethod')->will($this->returnValue('GET'));
        $this->requestBuilder->_set('environmentService', $this->mockEnvironmentService);
    }

    /**
     * @test
     */
    public function buildReturnsAWebRequestObject()
    {
        $this->injectDependencies();
        $request = $this->requestBuilder->build();
        $this->assertSame($this->mockRequest, $request);
    }

    /**
     * @test
     */
    public function buildSetsRequestPluginName()
    {
        $this->injectDependencies();
        $this->mockRequest->expects($this->once())->method('setPluginName')->with('Pi1');
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsRequestControllerExtensionName()
    {
        $this->injectDependencies();
        $this->mockRequest->expects($this->once())->method('setControllerExtensionName')->with('MyExtension');
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsRequestControllerName()
    {
        $this->injectDependencies();
        $this->mockRequest->expects($this->once())->method('setControllerName')->with('TheFirstController');
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsRequestControllerActionName()
    {
        $this->injectDependencies();
        $this->mockRequest->expects($this->once())->method('setControllerActionName')->with('show');
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsRequestRequestUri()
    {
        $this->injectDependencies();
        $expectedRequestUri = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        $this->mockRequest->expects($this->once())->method('setRequestUri')->with($expectedRequestUri);
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsRequestBaseUri()
    {
        $this->injectDependencies();
        $expectedBaseUri = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $this->mockRequest->expects($this->once())->method('setBaseUri')->with($expectedBaseUri);
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsRequestMethod()
    {
        $this->injectDependencies();
        $expectedMethod = 'SomeRequestMethod';
        $mockEnvironmentService = $this->getMock(\TYPO3\CMS\Extbase\Service\EnvironmentService::class, ['getServerRequestMethod']);
        $mockEnvironmentService->expects($this->once())->method('getServerRequestMethod')->will($this->returnValue($expectedMethod));
        $this->requestBuilder->_set('environmentService', $mockEnvironmentService);
        $this->mockRequest->expects($this->once())->method('setMethod')->with($expectedMethod);
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsVendorNameIfConfigured()
    {
        $this->injectDependencies();
        $expectedVendor = 'Vendor';
        $this->configuration['vendorName'] = $expectedVendor;
        $mockConfigurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $mockConfigurationManager);
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $this->mockRequest->expects($this->once())->method('setControllerVendorName')->with($expectedVendor);
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildDoesNotSetVendorNameIfNotConfiguredInSecondRequest()
    {
        $this->injectDependencies();
        $expectedVendor = 'Vendor';
        $this->configuration['vendorName'] = $expectedVendor;

        $mockConfigurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $mockConfigurationManager);
        $this->mockRequest->expects($this->once())->method('setControllerVendorName')->with($expectedVendor);

        $this->requestBuilder->build();

        unset($this->configuration['vendorName']);
        $mockConfigurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $mockConfigurationManager);

        $this->mockRequest->expects($this->never())->method('setControllerVendorName');
        $this->requestBuilder->build();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
     */
    public function buildThrowsExceptionIfExtensionNameIsNotConfigured()
    {
        unset($this->configuration['extensionName']);
        $mockConfigurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $mockConfigurationManager);
        $this->requestBuilder->build();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
     */
    public function buildThrowsExceptionIfPluginNameIsNotConfigured()
    {
        unset($this->configuration['pluginName']);
        $mockConfigurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $mockConfigurationManager);
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $this->requestBuilder->build();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
     */
    public function buildThrowsExceptionIfControllerConfigurationIsEmptyOrNotSet()
    {
        $this->configuration['controllerConfiguration'] = [];
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $this->mockConfigurationManager);
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $this->requestBuilder->build();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
     */
    public function buildThrowsExceptionIfControllerConfigurationHasNoDefaultActionDefined()
    {
        $this->configuration['controllerConfiguration']['TheFirstController'] = [];
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $this->mockConfigurationManager);
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $this->requestBuilder->build();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
     */
    public function buildThrowsExceptionIfNoDefaultControllerCanBeResolved()
    {
        $this->configuration['controllerConfiguration'] = [
            '' => [
                'actions' => ['foo']
            ]
        ];
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $this->mockConfigurationManager);
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsParametersFromGetAndPostVariables()
    {
        $this->configuration['extensionName'] = 'SomeExtensionName';
        $this->configuration['pluginName'] = 'SomePluginName';
        $this->injectDependencies();
        $_GET = [
            'tx_someotherextensionname_somepluginname' => [
                'foo' => 'bar'
            ],
            'tx_someextensionname_somepluginname' => [
                'parameter1' => 'valueGetsOverwritten',
                'parameter2' => [
                    'parameter3' => 'value3'
                ]
            ]
        ];
        $_POST = [
            'tx_someextensionname_someotherpluginname' => [
                'foo' => 'bar'
            ],
            'tx_someextensionname_somepluginname' => [
                'parameter1' => 'value1',
                'parameter2' => [
                    'parameter4' => 'value4'
                ]
            ]
        ];
        $this->mockRequest->expects($this->at(8))->method('setArgument')->with('parameter1', 'value1');
        $this->mockRequest->expects($this->at(9))->method('setArgument')->with('parameter2', ['parameter3' => 'value3', 'parameter4' => 'value4']);
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsFormatFromGetAndPostVariables()
    {
        $this->configuration['extensionName'] = 'SomeExtensionName';
        $this->configuration['pluginName'] = 'SomePluginName';
        $this->injectDependencies();
        $_GET = [
            'tx_someextensionname_somepluginname' => [
                'format' => 'GET'
            ]
        ];
        $_POST = [
            'tx_someextensionname_somepluginname' => [
                'format' => 'POST'
            ]
        ];
        $this->mockRequest->expects($this->at(7))->method('setFormat')->with('POST');
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildCorrectlySetsAllowedControllerActions()
    {
        $this->injectDependencies();
        $expectedResult = [
            'TheFirstController' => [
                'show',
                'index',
                'new',
                'create',
                'delete',
                'edit',
                'update',
                'setup',
                'test'
            ],
            'TheSecondController' => [
                'show',
                'index'
            ],
            'TheThirdController' => [
                'delete',
                'create',
                'onlyInThirdController'
            ]
        ];
        $this->requestBuilder->build();
        $actualResult = $this->requestBuilder->_get('allowedControllerActions');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
     */
    public function buildThrowsExceptionIfDefaultControllerCantBeDetermined()
    {
        $this->configuration['controllerConfiguration'] = [];
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $this->mockConfigurationManager);
        $this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsDefaultControllerIfNoControllerIsSpecified()
    {
        $this->injectDependencies();
        $_GET = [
            'tx_myextension_pi1' => [
                'foo' => 'bar'
            ]
        ];
        $this->mockRequest->expects($this->once())->method('setControllerName')->with('TheFirstController');
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildCorrectlySetsSpecifiedControllerNameIfItsAllowedForTheCurrentPlugin()
    {
        $this->injectDependencies();
        $_GET = [
            'tx_myextension_pi1' => [
                'controller' => 'TheSecondController'
            ]
        ];
        $this->mockRequest->expects($this->once())->method('setControllerName')->with('TheSecondController');
        $this->requestBuilder->build();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
     */
    public function buildThrowsInvalidControllerNameExceptionIfSpecifiedControllerIsNotAllowed()
    {
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $this->mockConfigurationManager);
        $this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $_GET = [
            'tx_myextension_pi1' => [
                'controller' => 'SomeInvalidController'
            ]
        ];
        $this->requestBuilder->build();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     */
    public function buildThrowsPageNotFoundExceptionIfEnabledAndSpecifiedControllerIsNotAllowed()
    {
        $this->configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved'] = 1;
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $this->mockConfigurationManager);
        $this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $_GET = [
            'tx_myextension_pi1' => [
                'controller' => 'SomeInvalidController'
            ]
        ];
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsDefaultControllerNameIfSpecifiedControllerIsNotAllowedAndCallDefaultActionIfActionCantBeResolvedIsSet()
    {
        $this->configuration['mvc']['callDefaultActionIfActionCantBeResolved'] = 1;
        $this->injectDependencies();
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $_GET = [
            'tx_myextension_pi1' => [
                'controller' => 'SomeInvalidController'
            ]
        ];
        $this->mockRequest->expects($this->once())->method('setControllerName')->with('TheFirstController');
        $this->requestBuilder->build();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception
     */
    public function buildThrowsExceptionIfDefaultActionCantBeDetermined()
    {
        $this->configuration['controllerConfiguration'] = [];
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $this->mockConfigurationManager);
        $this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsDefaultActionOfTheCurrentControllerIfNoActionIsSpecified()
    {
        $this->injectDependencies();
        $_GET = [
            'tx_myextension_pi1' => [
                'controller' => 'TheThirdController'
            ]
        ];
        $this->mockRequest->expects($this->once())->method('setControllerActionName')->with('delete');
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildCorrectlySetsSpecifiedActionNameForTheDefaultControllerIfItsAllowedForTheCurrentPlugin()
    {
        $this->injectDependencies();
        $_GET = [
            'tx_myextension_pi1' => [
                'action' => 'create'
            ]
        ];
        $this->mockRequest->expects($this->once())->method('setControllerActionName')->with('create');
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildCorrectlySetsSpecifiedActionNameForTheSpecifiedControllerIfItsAllowedForTheCurrentPlugin()
    {
        $this->injectDependencies();
        $_GET = [
            'tx_myextension_pi1' => [
                'controller' => 'TheThirdController',
                'action' => 'onlyInThirdController'
            ]
        ];
        $this->mockRequest->expects($this->once())->method('setControllerActionName')->with('onlyInThirdController');
        $this->requestBuilder->build();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
     */
    public function buildThrowsInvalidActionNameExceptionIfSpecifiedActionIsNotAllowed()
    {
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $this->mockConfigurationManager);
        $this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $_GET = [
            'tx_myextension_pi1' => [
                'action' => 'someInvalidAction'
            ]
        ];
        $this->requestBuilder->build();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     */
    public function buildThrowsPageNotFoundExceptionIfEnabledAndSpecifiedActionIsNotAllowed()
    {
        $this->configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved'] = 1;
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($this->configuration));
        $this->requestBuilder->_set('configurationManager', $this->mockConfigurationManager);
        $this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_myextension_pi1'));
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $_GET = [
            'tx_myextension_pi1' => [
                'action' => 'someInvalidAction'
            ]
        ];
        $this->requestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsDefaultActionNameIfSpecifiedActionIsNotAllowedAndCallDefaultActionIfActionCantBeResolvedIsSet()
    {
        $this->configuration['mvc']['callDefaultActionIfActionCantBeResolved'] = 1;
        $this->injectDependencies();
        $this->requestBuilder->_set('extensionService', $this->mockExtensionService);
        $_GET = [
            'tx_myextension_pi1' => [
                'controller' => 'TheThirdController',
                'action' => 'someInvalidAction'
            ]
        ];
        $this->mockRequest->expects($this->once())->method('setControllerName')->with('TheThirdController');
        $this->mockRequest->expects($this->once())->method('setControllerActionName')->with('delete');
        $this->requestBuilder->build();
    }

    /**
     * @test
     * @see TYPO3\Flow\Tests\Unit\Utility\EnvironmentTest
     */
    public function untangleFilesArrayTransformsTheFilesSuperglobalIntoAManageableForm()
    {
        $convolutedFiles = [
            'a0' => [
                'name' => [
                    'a1' => 'a.txt'
                ],
                'type' => [
                    'a1' => 'text/plain'
                ],
                'tmp_name' => [
                    'a1' => '/private/var/tmp/phpbqXsYt'
                ],
                'error' => [
                    'a1' => 0
                ],
                'size' => [
                    'a1' => 100
                ]
            ],
            'b0' => [
                'name' => [
                    'b1' => 'b.txt'
                ],
                'type' => [
                    'b1' => 'text/plain'
                ],
                'tmp_name' => [
                    'b1' => '/private/var/tmp/phpvZ6oUD'
                ],
                'error' => [
                    'b1' => 0
                ],
                'size' => [
                    'b1' => 200
                ]
            ],
            'c' => [
                'name' => 'c.txt',
                'type' => 'text/plain',
                'tmp_name' => '/private/var/tmp/phpS9KMNw',
                'error' => 0,
                'size' => 300
            ],
            'd0' => [
                'name' => [
                    'd1' => [
                        'd2' => [
                            'd3' => 'd.txt'
                        ]
                    ]
                ],
                'type' => [
                    'd1' => [
                        'd2' => [
                            'd3' => 'text/plain'
                        ]
                    ]
                ],
                'tmp_name' => [
                    'd1' => [
                        'd2' => [
                            'd3' => '/private/var/tmp/phprR3fax'
                        ]
                    ]
                ],
                'error' => [
                    'd1' => [
                        'd2' => [
                            'd3' => 0
                        ]
                    ]
                ],
                'size' => [
                    'd1' => [
                        'd2' => [
                            'd3' => 400
                        ]
                    ]
                ]
            ],
            'e0' => [
                'name' => [
                    'e1' => [
                        'e2' => [
                            0 => 'e_one.txt',
                            1 => 'e_two.txt'
                        ]
                    ]
                ],
                'type' => [
                    'e1' => [
                        'e2' => [
                            0 => 'text/plain',
                            1 => 'text/plain'
                        ]
                    ]
                ],
                'tmp_name' => [
                    'e1' => [
                        'e2' => [
                            0 => '/private/var/tmp/php01fitB',
                            1 => '/private/var/tmp/phpUUB2cv'
                        ]
                    ]
                ],
                'error' => [
                    'e1' => [
                        'e2' => [
                            0 => 0,
                            1 => 0
                        ]
                    ]
                ],
                'size' => [
                    'e1' => [
                        'e2' => [
                            0 => 510,
                            1 => 520
                        ]
                    ]
                ]
            ]
        ];
        $untangledFiles = [
            'a0' => [
                'a1' => [
                    'name' => 'a.txt',
                    'type' => 'text/plain',
                    'tmp_name' => '/private/var/tmp/phpbqXsYt',
                    'error' => 0,
                    'size' => 100
                ]
            ],
            'b0' => [
                'b1' => [
                    'name' => 'b.txt',
                    'type' => 'text/plain',
                    'tmp_name' => '/private/var/tmp/phpvZ6oUD',
                    'error' => 0,
                    'size' => 200
                ]
            ],
            'c' => [
                'name' => 'c.txt',
                'type' => 'text/plain',
                'tmp_name' => '/private/var/tmp/phpS9KMNw',
                'error' => 0,
                'size' => 300
            ],
            'd0' => [
                'd1' => [
                    'd2' => [
                        'd3' => [
                            'name' => 'd.txt',
                            'type' => 'text/plain',
                            'tmp_name' => '/private/var/tmp/phprR3fax',
                            'error' => 0,
                            'size' => 400
                        ]
                    ]
                ]
            ],
            'e0' => [
                'e1' => [
                    'e2' => [
                        0 => [
                            'name' => 'e_one.txt',
                            'type' => 'text/plain',
                            'tmp_name' => '/private/var/tmp/php01fitB',
                            'error' => 0,
                            'size' => 510
                        ],
                        1 => [
                            'name' => 'e_two.txt',
                            'type' => 'text/plain',
                            'tmp_name' => '/private/var/tmp/phpUUB2cv',
                            'error' => 0,
                            'size' => 520
                        ]
                    ]
                ]
            ]
        ];
        $requestBuilder = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder::class, ['dummy'], [], '', false);
        $result = $requestBuilder->_call('untangleFilesArray', $convolutedFiles);
        $this->assertSame($untangledFiles, $result);
    }
}
