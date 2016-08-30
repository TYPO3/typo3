<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

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

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Test case
 */
class ActionControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $actionController;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     */
    protected $mockUriBuilder;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService
     */
    protected $mockMvcPropertyMappingConfigurationService;

    protected function setUp()
    {
        $this->actionController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class);
    }

    /**
     * @test
     */
    public function processRequestSticksToSpecifiedSequence()
    {
        $mockRequest = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Web\Request::class, [], [], '', false);
        $mockRequest->expects($this->once())->method('setDispatched')->with(true);
        $mockUriBuilder = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('setRequest')->with($mockRequest);
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class)->will($this->returnValue($mockUriBuilder));
        $mockResponse = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Web\Response::class, [], [], '', false);
        $configurationService = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, [
            'initializeFooAction',
            'initializeAction',
            'resolveActionMethodName',
            'initializeActionMethodArguments',
            'initializeActionMethodValidators',
            'mapRequestArgumentsToControllerArguments',
            'buildControllerContext',
            'resolveView',
            'initializeView',
            'callActionMethod',
            'checkRequestHash'
        ], [], '', false);
        $mockController->_set('objectManager', $mockObjectManager);

        $mockController->expects($this->at(0))->method('resolveActionMethodName')->will($this->returnValue('fooAction'));
        $mockController->expects($this->at(1))->method('initializeActionMethodArguments');
        $mockController->expects($this->at(2))->method('initializeActionMethodValidators');
        $mockController->expects($this->at(3))->method('initializeAction');
        $mockController->expects($this->at(4))->method('initializeFooAction');
        $mockController->expects($this->at(5))->method('mapRequestArgumentsToControllerArguments');
        $mockController->expects($this->at(6))->method('checkRequestHash');
        $mockController->expects($this->at(7))->method('buildControllerContext');
        $mockController->expects($this->at(8))->method('resolveView');

        $mockController->_set('mvcPropertyMappingConfigurationService', $configurationService);
        $mockController->_set('arguments', new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments());

        $mockController->processRequest($mockRequest, $mockResponse);
        $this->assertSame($mockRequest, $mockController->_get('request'));
        $this->assertSame($mockResponse, $mockController->_get('response'));
    }

    /**
     * @test
     */
    public function resolveViewUsesFluidTemplateViewIfTemplateIsAvailable()
    {
        $mockSession = $this->getMock('Tx_Extbase_Session_SessionInterface');
        $mockControllerContext = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class, [], [], '', false);
        $mockFluidTemplateView = $this->getMock(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class);
        $mockFluidTemplateView->expects($this->once())->method('setControllerContext')->with($mockControllerContext);
        $mockFluidTemplateView->expects($this->once())->method('canRender')->with($mockControllerContext)->will($this->returnValue(true));
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class, [], [], '', false);
        $mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\CMS\Fluid\View\TemplateView::class)->will($this->returnValue($mockFluidTemplateView));
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['buildControllerContext', 'resolveViewObjectName', 'setViewConfiguration'], [], '', false);
        $mockController->expects($this->once())->method('resolveViewObjectName')->will($this->returnValue(false));
        $mockController->_set('session', $mockSession);
        $mockController->_set('objectManager', $mockObjectManager);
        $mockController->_set('controllerContext', $mockControllerContext);
        $this->assertSame($mockFluidTemplateView, $mockController->_call('resolveView'));
    }

    /**
     * @test
     */
    public function resolveViewObjectNameUsesViewObjectNamePatternToResolveViewObjectName()
    {
        $mockRequest = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, [], [], '', false);
        $mockRequest->expects($this->once())->method('getControllerVendorName')->will($this->returnValue('MyVendor'));
        $mockRequest->expects($this->once())->method('getControllerExtensionName')->will($this->returnValue('MyPackage'));
        $mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('MyController'));
        $mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('MyAction'));
        $mockRequest->expects($this->atLeastOnce())->method('getFormat')->will($this->returnValue('MyFormat'));
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class, [], [], '', false);
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['dummy'], [], '', false);
        $mockController->_set('request', $mockRequest);
        $mockController->_set('objectManager', $mockObjectManager);
        $mockController->_set('namespacesViewObjectNamePattern', 'RandomViewObject@vendor\@extension\View\@controller\@action@format');
        $mockController->_call('resolveViewObjectName');
    }

    /**
     * @test
     */
    public function resolveViewObjectNameUsesNamespacedViewObjectNamePatternForExtensionsWithVendor()
    {
        eval('namespace MyVendor\MyPackage\View\MyController; class MyActionMyFormat {}');

        $mockRequest = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, [], [], '', false);
        $mockRequest->expects($this->once())->method('getControllerExtensionName')->will($this->returnValue('MyPackage'));
        $mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('MyController'));
        $mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('MyAction'));
        $mockRequest->expects($this->once())->method('getControllerVendorName')->will($this->returnValue('MyVendor'));
        $mockRequest->expects($this->atLeastOnce())->method('getFormat')->will($this->returnValue('MyFormat'));
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class, [], [], '', false);
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['dummy'], [], '', false);
        $mockController->_set('request', $mockRequest);
        $mockController->_set('objectManager', $mockObjectManager);

        $this->assertEquals(
            'MyVendor\MyPackage\View\MyController\MyActionMyFormat',
            $mockController->_call('resolveViewObjectName')
        );
    }

    /**
     * @test
     */
    public function resolveActionMethodNameReturnsTheCurrentActionMethodNameFromTheRequest()
    {
        $mockRequest = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, [], [], '', false);
        $mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('fooBar'));
        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['fooBarAction'], [], '', false);
        $mockController->_set('request', $mockRequest);
        $this->assertEquals('fooBarAction', $mockController->_call('resolveActionMethodName'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException
     */
    public function resolveActionMethodNameThrowsAnExceptionIfTheActionDefinedInTheRequestDoesNotExist()
    {
        $mockRequest = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, [], [], '', false);
        $mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('fooBar'));
        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['otherBarAction'], [], '', false);
        $mockController->_set('request', $mockRequest);
        $mockController->_call('resolveActionMethodName');
    }

    /**
     * @test
     */
    public function initializeActionMethodArgumentsRegistersArgumentsFoundInTheSignatureOfTheCurrentActionMethod()
    {
        $mockRequest = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, [], [], '', false);
        $mockArguments = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['addNewArgument', 'removeAll']);
        $mockArguments->expects($this->at(0))->method('addNewArgument')->with('stringArgument', 'string', true);
        $mockArguments->expects($this->at(1))->method('addNewArgument')->with('integerArgument', 'integer', true);
        $mockArguments->expects($this->at(2))->method('addNewArgument')->with('objectArgument', 'F3_Foo_Bar', true);
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['fooAction', 'evaluateDontValidateAnnotations'], [], '', false);
        $methodParameters = [
            'stringArgument' => [
                'position' => 0,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => false,
                'type' => 'string'
            ],
            'integerArgument' => [
                'position' => 1,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => false,
                'type' => 'integer'
            ],
            'objectArgument' => [
                'position' => 2,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => false,
                'type' => 'F3_Foo_Bar'
            ]
        ];
        $mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, [], [], '', false);
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));
        $mockController->_set('reflectionService', $mockReflectionService);
        $mockController->_set('request', $mockRequest);
        $mockController->_set('arguments', $mockArguments);
        $mockController->_set('actionMethodName', 'fooAction');
        $mockController->_call('initializeActionMethodArguments');
    }

    /**
     * @test
     */
    public function initializeActionMethodArgumentsRegistersOptionalArgumentsAsSuch()
    {
        $mockRequest = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, [], [], '', false);
        $mockArguments = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, [], [], '', false);
        $mockArguments->expects($this->at(0))->method('addNewArgument')->with('arg1', 'string', true);
        $mockArguments->expects($this->at(1))->method('addNewArgument')->with('arg2', 'array', false, [21]);
        $mockArguments->expects($this->at(2))->method('addNewArgument')->with('arg3', 'string', false, 42);
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['fooAction', 'evaluateDontValidateAnnotations'], [], '', false);
        $methodParameters = [
            'arg1' => [
                'position' => 0,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => false,
                'type' => 'string'
            ],
            'arg2' => [
                'position' => 1,
                'byReference' => false,
                'array' => true,
                'optional' => true,
                'defaultValue' => [21],
                'allowsNull' => false
            ],
            'arg3' => [
                'position' => 2,
                'byReference' => false,
                'array' => false,
                'optional' => true,
                'defaultValue' => 42,
                'allowsNull' => false,
                'type' => 'string'
            ]
        ];
        $mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, [], [], '', false);
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));
        $mockController->_set('reflectionService', $mockReflectionService);
        $mockController->_set('request', $mockRequest);
        $mockController->_set('arguments', $mockArguments);
        $mockController->_set('actionMethodName', 'fooAction');
        $mockController->_call('initializeActionMethodArguments');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException
     */
    public function initializeActionMethodArgumentsThrowsExceptionIfDataTypeWasNotSpecified()
    {
        $mockRequest = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, [], [], '', false);
        $mockArguments = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, [], [], '', false);
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['fooAction'], [], '', false);
        $methodParameters = [
            'arg1' => [
                'position' => 0,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => false
            ]
        ];
        $mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, [], [], '', false);
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));
        $mockController->_set('reflectionService', $mockReflectionService);
        $mockController->_set('request', $mockRequest);
        $mockController->_set('arguments', $mockArguments);
        $mockController->_set('actionMethodName', 'fooAction');
        $mockController->_call('initializeActionMethodArguments');
    }

    /**
     * @test
     * @dataProvider templateRootPathDataProvider
     * @param array $configuration
     * @param array $expected
     */
    public function setViewConfigurationResolvesTemplateRootPathsForTemplateRootPath($configuration, $expected)
    {
        /** @var ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $mockController */
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['dummy'], [], '', false);
        /** @var ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($configuration));
        $mockController->injectConfigurationManager($mockConfigurationManager);
        $mockController->_set('request', $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class), ['getControllerExtensionKey']);
        $view = $this->getMock(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class, ['setControllerContext', 'assign', 'assignMultiple', 'canRender', 'render', 'initializeView', 'setTemplateRootPaths']);
        $view->expects($this->once())->method('setTemplateRootPaths')->with($expected);
        $mockController->_call('setViewConfiguration', $view);
    }

    /**
     * @return array
     */
    public function templateRootPathDataProvider()
    {
        return [
            'text keys' => [
                [
                    'view' => [
                        'templateRootPaths' => [
                            'default' => 'some path',
                            'extended' => 'some other path'
                        ]
                    ]
                ],
                [
                    'extended' => 'some other path',
                    'default' => 'some path'
                ]
            ],
            'numerical keys' => [
                [
                    'view' => [
                        'templateRootPaths' => [
                            '10' => 'some path',
                            '20' => 'some other path',
                            '15' => 'intermediate specific path'
                        ]
                    ]
                ],
                [
                    '20' => 'some other path',
                    '15' => 'intermediate specific path',
                    '10' => 'some path'
                ]
            ],
            'mixed keys' => [
                [
                    'view' => [
                        'templateRootPaths' => [
                            '10' => 'some path',
                            'very_specific' => 'some other path',
                            '15' => 'intermediate specific path'
                        ]
                    ]
                ],
                [
                    '15' => 'intermediate specific path',
                    'very_specific' => 'some other path',
                    '10' => 'some path'
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider layoutRootPathDataProvider
     *
     * @param array $configuration
     * @param array $expected
     */
    public function setViewConfigurationResolvesLayoutRootPathsForLayoutRootPath($configuration, $expected)
    {
        /** @var ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $mockController */
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['dummy'], [], '', false);
        /** @var ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($configuration));
        $mockController->injectConfigurationManager($mockConfigurationManager);
        $mockController->_set('request', $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class), ['getControllerExtensionKey']);
        $view = $this->getMock(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class, ['setControllerContext', 'assign', 'assignMultiple', 'canRender', 'render', 'initializeView', 'setlayoutRootPaths']);
        $view->expects($this->once())->method('setlayoutRootPaths')->with($expected);
        $mockController->_call('setViewConfiguration', $view);
    }

    /**
     * @return array
     */
    public function layoutRootPathDataProvider()
    {
        return [
            'text keys' => [
                [
                    'view' => [
                        'layoutRootPaths' => [
                            'default' => 'some path',
                            'extended' => 'some other path'
                        ]
                    ]
                ],
                [
                    'extended' => 'some other path',
                    'default' => 'some path'
                ]
            ],
            'numerical keys' => [
                [
                    'view' => [
                        'layoutRootPaths' => [
                            '10' => 'some path',
                            '20' => 'some other path',
                            '15' => 'intermediate specific path'
                        ]
                    ]
                ],
                [
                    '20' => 'some other path',
                    '15' => 'intermediate specific path',
                    '10' => 'some path'
                ]
            ],
            'mixed keys' => [
                [
                    'view' => [
                        'layoutRootPaths' => [
                            '10' => 'some path',
                            'very_specific' => 'some other path',
                            '15' => 'intermediate specific path'
                        ]
                    ]
                ],
                [
                    '15' => 'intermediate specific path',
                    'very_specific' => 'some other path',
                    '10' => 'some path'
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider partialRootPathDataProvider
     *
     * @param array $configuration
     * @param array $expected
     */
    public function setViewConfigurationResolvesPartialRootPathsForPartialRootPath($configuration, $expected)
    {
        /** @var ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $mockController */
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['dummy'], [], '', false);
        /** @var ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($configuration));
        $mockController->injectConfigurationManager($mockConfigurationManager);
        $mockController->_set('request', $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class), ['getControllerExtensionKey']);
        $view = $this->getMock(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface::class, ['setControllerContext', 'assign', 'assignMultiple', 'canRender', 'render', 'initializeView', 'setpartialRootPaths']);
        $view->expects($this->once())->method('setpartialRootPaths')->with($expected);
        $mockController->_call('setViewConfiguration', $view);
    }

    /**
     * @return array
     */
    public function partialRootPathDataProvider()
    {
        return [
            'text keys' => [
                [
                    'view' => [
                        'partialRootPaths' => [
                            'default' => 'some path',
                            'extended' => 'some other path'
                        ]
                    ]
                ],
                [
                    'extended' => 'some other path',
                    'default' => 'some path'
                ]
            ],
            'numerical keys' => [
                [
                    'view' => [
                        'partialRootPaths' => [
                            '10' => 'some path',
                            '20' => 'some other path',
                            '15' => 'intermediate specific path'
                        ]
                    ]
                ],
                [
                    '20' => 'some other path',
                    '15' => 'intermediate specific path',
                    '10' => 'some path'
                ]
            ],
            'mixed keys' => [
                [
                    'view' => [
                        'partialRootPaths' => [
                            '10' => 'some path',
                            'very_specific' => 'some other path',
                            '15' => 'intermediate specific path'
                        ]
                    ]
                ],
                [
                    '15' => 'intermediate specific path',
                    'very_specific' => 'some other path',
                    '10' => 'some path'
                ]
            ],
        ];
    }
}
