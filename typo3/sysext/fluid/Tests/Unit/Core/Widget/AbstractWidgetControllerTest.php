<?php

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

namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Widget;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController;
use TYPO3\CMS\Fluid\Core\Widget\WidgetRequest;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractWidgetControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function canHandleWidgetRequest()
    {
        /** @var WidgetRequest|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->getMockBuilder(WidgetRequest::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var AbstractWidgetController|\PHPUnit\Framework\MockObject\MockObject $abstractWidgetController */
        $abstractWidgetController = $this->getMockBuilder(AbstractWidgetController::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        self::assertTrue($abstractWidgetController->canProcessRequest($request));
    }

    /**
     * @test
     */
    public function processRequestSetsWidgetConfiguration()
    {
        $widgetContext = $this->createMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
        $widgetContext->expects(self::once())->method('getWidgetConfiguration')->willReturn('myConfiguration');
        /** @var WidgetRequest|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(WidgetRequest::class);
        $request->expects(self::once())->method('getWidgetContext')->willReturn($widgetContext);
        /** @var ResponseInterface|\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        /** @var AbstractWidgetController|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $abstractWidgetController */
        $abstractWidgetController = $this->getAccessibleMock(AbstractWidgetController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'initializeAction', 'checkRequestHash', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext', 'resolveView', 'callActionMethod'], [], '', false);
        $mockUriBuilder = $this->createMock(UriBuilder::class);
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::any())->method('get')->with(UriBuilder::class)->willReturn($mockUriBuilder);

        $configurationService = $this->createMock(MvcPropertyMappingConfigurationService::class);
        $abstractWidgetController->_set('mvcPropertyMappingConfigurationService', $configurationService);
        $abstractWidgetController->_set('arguments', new Arguments());

        $abstractWidgetController->_set('objectManager', $objectManager);
        $abstractWidgetController->processRequest($request, $response);
        $widgetConfiguration = $abstractWidgetController->_get('widgetConfiguration');
        self::assertEquals('myConfiguration', $widgetConfiguration);
    }

    /**
     * @test
     * @dataProvider getSetViewConfigurationTestValues
     * @param array $parent
     * @param array|null $widget
     * @param array $expected
     */
    public function setViewConfigurationPerformsExpectedInitialization(array $parent, $widget, array $expected)
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->expects(self::once())->method('getConfiguration')->willReturn([
            'view' => [
                'widget' => [
                    'foobarClassName' => $widget
                ]
            ]
        ]);
        $parentRequest = $this->getMockBuilder(Request::class)
            ->setMethods(['getControllerExtensionKey'])
            ->getMock();
        $parentRequest->expects(self::once())->method('getControllerExtensionKey')->willReturn(null);
        $controllerContext = $this->getMockBuilder(ControllerContext::class)
            ->setMethods(['getRequest'])
            ->getMock();
        $controllerContext->expects(self::once())->method('getRequest')->willReturn($parentRequest);
        $templatePaths = $this->getMockBuilder(TemplatePaths::class)
            ->setMethods(['fillFromConfigurationArray', 'toArray'])
            ->getMock();
        $templatePaths->expects(self::once())->method('fillFromConfigurationArray')->with($expected);
        $templatePaths->expects(self::any())->method('toArray')->willReturn($parent);
        $widgetContext = $this->getMockBuilder(WidgetContext::class)
            ->setMethods(['getWidgetViewHelperClassName'])
            ->getMock();
        $widgetContext->expects(self::once())->method('getWidgetViewHelperClassName')->willReturn('foobarClassName');
        $request = $this->getMockBuilder(Request::class)
            ->setMethods(['getWidgetContext'])
            ->getMock();
        $request->expects(self::once())->method('getWidgetContext')->willReturn($widgetContext);

        $view = $this->getAccessibleMock(TemplateView::class, ['getTemplatePaths', 'toArray'], [], '', false);
        $view->expects(self::exactly(2))->method('getTemplatePaths')->willReturn($templatePaths);

        $prophecy = $this->prophesize(AbstractWidgetController::class);

        /** @var AbstractWidgetController $controller */
        $controller = $prophecy->reveal();

        $reflectionClass = new \ReflectionClass($controller);

        $reflectionProperty = $reflectionClass->getProperty('configurationManager');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, $configurationManager);

        $reflectionProperty = $reflectionClass->getProperty('controllerContext');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, $controllerContext);

        $reflectionProperty = $reflectionClass->getProperty('request');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, $request);

        $reflectionMethod = $reflectionClass->getMethod('setViewConfiguration');
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invokeArgs($controller, [$view]);
    }

    /**
     * @return array
     */
    public function getSetViewConfigurationTestValues()
    {
        return [
            'Empty path sets cause empty widget paths' => [
                [],
                null,
                [
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => [],
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => [],
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => []
                ]
            ],
            'Parent request paths are reused when not overridden' => [
                [
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => ['foo'],
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => ['bar'],
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => ['baz']
                ],
                [],
                [
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => ['foo'],
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => ['bar'],
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => ['baz']
                ]
            ],
            'Widget paths are added to parent paths' => [
                [
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => ['foo1'],
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => ['bar1'],
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => ['baz1']
                ],
                [
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => ['foo2'],
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => ['bar2'],
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => ['baz2']
                ],
                [
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => ['foo1', 'foo2'],
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => ['bar1', 'bar2'],
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => ['baz1', 'baz2']
                ]
            ],
        ];
    }
}
