<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\View\AbstractTemplateView;
use TYPO3Fluid\Fluid\View\TemplateView as FluidTemplateView;

/**
 * Test case
 */
class ActionControllerTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
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

    /**
     * @test
     */
    public function resolveActionMethodNameReturnsTheCurrentActionMethodNameFromTheRequest()
    {
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('fooBar'));
        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockController = $this->getAccessibleMock(ActionController::class, ['fooBarAction'], [], '', false);
        $mockController->_set('request', $mockRequest);
        $this->assertEquals('fooBarAction', $mockController->_call('resolveActionMethodName'));
    }

    /**
     * @test
     */
    public function resolveActionMethodNameThrowsAnExceptionIfTheActionDefinedInTheRequestDoesNotExist()
    {
        $this->expectException(NoSuchActionException::class);
        $this->expectExceptionCode(1186669086);
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('fooBar'));
        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockController = $this->getAccessibleMock(ActionController::class, ['otherBarAction'], [], '', false);
        $mockController->_set('request', $mockRequest);
        $mockController->_call('resolveActionMethodName');
    }

    /**
     * @test
     *
     * @todo: make this a functional test
     */
    public function initializeActionMethodArgumentsRegistersArgumentsFoundInTheSignatureOfTheCurrentActionMethod(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $mockArguments = $this->getMockBuilder(Arguments::class)
            ->setMethods(['addNewArgument', 'removeAll'])
            ->getMock();
        $mockArguments->expects($this->at(0))->method('addNewArgument')->with('stringArgument', 'string', true);
        $mockArguments->expects($this->at(1))->method('addNewArgument')->with('integerArgument', 'integer', true);
        $mockArguments->expects($this->at(2))->method('addNewArgument')->with('objectArgument', 'F3_Foo_Bar', true);
        $mockController = $this->getAccessibleMock(ActionController::class, ['fooAction', 'evaluateDontValidateAnnotations'], [], '', false);

        $classSchemaMethod = new ClassSchema\Method(
            'fooAction',
            [
                'params' => [
                    'stringArgument' => [
                        'position' => 0,
                        'byReference' => false,
                        'array' => false,
                        'optional' => false,
                        'allowsNull' => false,
                        'type' => 'string',
                        'hasDefaultValue' => false
                    ],
                    'integerArgument' => [
                        'position' => 1,
                        'byReference' => false,
                        'array' => false,
                        'optional' => false,
                        'allowsNull' => false,
                        'type' => 'integer',
                        'hasDefaultValue' => false
                    ],
                    'objectArgument' => [
                        'position' => 2,
                        'byReference' => false,
                        'array' => false,
                        'optional' => false,
                        'allowsNull' => false,
                        'type' => 'F3_Foo_Bar',
                        'hasDefaultValue' => false
                    ]
                ]
            ],
            get_class($mockController)
        );

        $classSchemaMock = $this->createMock(ClassSchema::class);
        $classSchemaMock
            ->expects($this->any())
            ->method('getMethod')
            ->with('fooAction')
            ->willReturn($classSchemaMethod);

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService
            ->expects($this->any())
            ->method('getClassSchema')
            ->with(get_class($mockController))
            ->willReturn($classSchemaMock);
        $mockController->_set('reflectionService', $mockReflectionService);
        $mockController->_set('request', $mockRequest);
        $mockController->_set('arguments', $mockArguments);
        $mockController->_set('actionMethodName', 'fooAction');
        $mockController->_call('initializeActionMethodArguments');
    }

    /**
     * @test
     */
    public function initializeActionMethodArgumentsRegistersOptionalArgumentsAsSuch(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $mockArguments = $this->createMock(Arguments::class);
        $mockArguments->expects($this->at(0))->method('addNewArgument')->with('arg1', 'string', true);
        $mockArguments->expects($this->at(1))->method('addNewArgument')->with('arg2', 'array', false, [21]);
        $mockArguments->expects($this->at(2))->method('addNewArgument')->with('arg3', 'string', false, 42);
        $mockController = $this->getAccessibleMock(ActionController::class, ['fooAction', 'evaluateDontValidateAnnotations'], [], '', false);

        $classSchemaMethod = new ClassSchema\Method(
            'fooAction',
            [
                'params' => [
                    'arg1' => [
                        'position' => 0,
                        'byReference' => false,
                        'array' => false,
                        'optional' => false,
                        'allowsNull' => false,
                        'type' => 'string',
                        'hasDefaultValue' => false
                    ],
                    'arg2' => [
                        'position' => 1,
                        'byReference' => false,
                        'array' => true,
                        'optional' => true,
                        'defaultValue' => [21],
                        'allowsNull' => false,
                        'hasDefaultValue' => true
                    ],
                    'arg3' => [
                        'position' => 2,
                        'byReference' => false,
                        'array' => false,
                        'optional' => true,
                        'defaultValue' => 42,
                        'allowsNull' => false,
                        'type' => 'string',
                        'hasDefaultValue' => true
                    ]
                ]
            ],
            get_class($mockController)
        );

        $classSchemaMock = $this->createMock(ClassSchema::class);
        $classSchemaMock
            ->expects($this->any())
            ->method('getMethod')
            ->with('fooAction')
            ->willReturn($classSchemaMethod);

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService
            ->expects($this->any())
            ->method('getClassSchema')
            ->with(get_class($mockController))
            ->willReturn($classSchemaMock);
        $mockController->_set('reflectionService', $mockReflectionService);
        $mockController->_set('request', $mockRequest);
        $mockController->_set('arguments', $mockArguments);
        $mockController->_set('actionMethodName', 'fooAction');
        $mockController->_call('initializeActionMethodArguments');
    }

    /**
     * @test
     */
    public function initializeActionMethodArgumentsThrowsExceptionIfDataTypeWasNotSpecified(): void
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $this->expectExceptionCode(1253175643);
        $mockRequest = $this->createMock(Request::class);
        $mockArguments = $this->createMock(Arguments::class);
        $mockController = $this->getAccessibleMock(ActionController::class, ['fooAction'], [], '', false);

        $classSchemaMethod = new ClassSchema\Method(
            'fooAction',
            [
                'params' => [
                    'arg1' => [
                        'position' => 0,
                        'byReference' => false,
                        'array' => false,
                        'optional' => false,
                        'allowsNull' => false
                    ]
                ]
            ],
            get_class($mockController)
        );

        $classSchemaMock = $this->createMock(ClassSchema::class);
        $classSchemaMock
            ->expects($this->any())
            ->method('getMethod')
            ->with('fooAction')
            ->willReturn($classSchemaMethod);

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService
            ->expects($this->any())
            ->method('getClassSchema')
            ->with(get_class($mockController))
            ->willReturn($classSchemaMock);
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
        /** @var ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $mockController */
        $mockController = $this->getAccessibleMock(ActionController::class, ['dummy'], [], '', false);
        /** @var ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($configuration));
        $mockController->injectConfigurationManager($mockConfigurationManager);
        $mockController->_set('request', $this->createMock(Request::class), ['getControllerExtensionKey']);
        $view = $this->getMockBuilder(ViewInterface::class)
            ->setMethods(['setControllerContext', 'assign', 'assignMultiple', 'canRender', 'render', 'initializeView', 'setTemplateRootPaths'])
            ->getMock();
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
        /** @var ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $mockController */
        $mockController = $this->getAccessibleMock(ActionController::class, ['dummy'], [], '', false);
        /** @var ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($configuration));
        $mockController->injectConfigurationManager($mockConfigurationManager);
        $mockController->_set('request', $this->createMock(Request::class), ['getControllerExtensionKey']);
        $view = $this->getMockBuilder(ViewInterface::class)
            ->setMethods(['setControllerContext', 'assign', 'assignMultiple', 'canRender', 'render', 'initializeView', 'setlayoutRootPaths'])
            ->getMock();
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
        /** @var ActionController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $mockController */
        $mockController = $this->getAccessibleMock(ActionController::class, ['dummy'], [], '', false);
        /** @var ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($configuration));
        $mockController->injectConfigurationManager($mockConfigurationManager);
        $mockController->_set('request', $this->createMock(Request::class), ['getControllerExtensionKey']);
        $view = $this->getMockBuilder(ViewInterface::class)
            ->setMethods(['setControllerContext', 'assign', 'assignMultiple', 'canRender', 'render', 'initializeView', 'setpartialRootPaths'])
            ->getMock();
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

    /**
     * @param FluidTemplateView $viewMock
     * @param string|null $expectedHeader
     * @param string|null $expectedFooter
     * @test
     * @dataProvider headerAssetDataProvider
     */
    public function rendersAndAssignsAssetsFromViewIntoPageRenderer($viewMock, $expectedHeader, $expectedFooter)
    {
        $this->mockObjectManager = $this->createMock(ObjectManager::class);
        $pageRendererMock = $this->getMockBuilder(PageRenderer::class)->setMethods(['addHeaderData', 'addFooterData'])->getMock();
        if (!$viewMock instanceof FluidTemplateView) {
            $this->mockObjectManager->expects($this->never())->method('get');
        } else {
            $this->mockObjectManager->expects($this->any())->method('get')->with(PageRenderer::class)->willReturn($pageRendererMock);
        }
        if (!empty(trim($expectedHeader ?? ''))) {
            $pageRendererMock->expects($this->once())->method('addHeaderData')->with($expectedHeader);
        } else {
            $pageRendererMock->expects($this->never())->method('addHeaderData');
        }
        if (!empty(trim($expectedFooter ?? ''))) {
            $pageRendererMock->expects($this->once())->method('addFooterData')->with($expectedFooter);
        } else {
            $pageRendererMock->expects($this->never())->method('addFooterData');
        }
        $requestMock = $this->getMockBuilder(RequestInterface::class)->getMockForAbstractClass();
        $subject = new ActionController('');
        $viewProperty = new \ReflectionProperty($subject, 'view');
        $viewProperty->setAccessible(true);
        $viewProperty->setValue($subject, $viewMock);
        $objectManagerProperty = new \ReflectionProperty($subject, 'objectManager');
        $objectManagerProperty->setAccessible(true);
        $objectManagerProperty->setValue($subject, $this->mockObjectManager);

        $method = new \ReflectionMethod($subject, 'renderAssetsForRequest');
        $method->setAccessible(true);
        $method->invokeArgs($subject, [$requestMock]);
    }

    /**
     * @return array
     */
    public function headerAssetDataProvider()
    {
        $viewWithHeaderData = $this->getMockBuilder(FluidTemplateView::class)->setMethods(['renderSection'])->disableOriginalConstructor()->getMock();
        $viewWithHeaderData->expects($this->at(0))->method('renderSection')->with('HeaderAssets', $this->anything(), true)->willReturn('custom-header-data');
        $viewWithHeaderData->expects($this->at(1))->method('renderSection')->with('FooterAssets', $this->anything(), true)->willReturn(null);
        $viewWithFooterData = $this->getMockBuilder(FluidTemplateView::class)->setMethods(['renderSection'])->disableOriginalConstructor()->getMock();
        $viewWithFooterData->expects($this->at(0))->method('renderSection')->with('HeaderAssets', $this->anything(), true)->willReturn(null);
        $viewWithFooterData->expects($this->at(1))->method('renderSection')->with('FooterAssets', $this->anything(), true)->willReturn('custom-footer-data');
        $viewWithBothData = $this->getMockBuilder(FluidTemplateView::class)->setMethods(['renderSection'])->disableOriginalConstructor()->getMock();
        $viewWithBothData->expects($this->at(0))->method('renderSection')->with('HeaderAssets', $this->anything(), true)->willReturn('custom-header-data');
        $viewWithBothData->expects($this->at(1))->method('renderSection')->with('FooterAssets', $this->anything(), true)->willReturn('custom-footer-data');
        $invalidView = $this->getMockBuilder(AbstractTemplateView::class)->disableOriginalConstructor()->getMockForAbstractClass();
        return [
            [$viewWithHeaderData, 'custom-header-data', null],
            [$viewWithFooterData, null, 'custom-footer-data'],
            [$viewWithBothData, 'custom-header-data', 'custom-footer-data'],
            [$invalidView, null, null]
        ];
    }
}
