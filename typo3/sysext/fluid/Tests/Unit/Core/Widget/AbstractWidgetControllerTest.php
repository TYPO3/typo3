<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Widget;

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
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController;
use TYPO3\CMS\Fluid\Core\Widget\WidgetRequest;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Test case
 */
class AbstractWidgetControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function canHandleWidgetRequest()
    {
        /** @var WidgetRequest|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class)
            ->setMethods(array('dummy'))
            ->disableOriginalConstructor()
            ->getMock();
        /** @var AbstractWidgetController|\PHPUnit_Framework_MockObject_MockObject $abstractWidgetController */
        $abstractWidgetController = $this->getMockBuilder(\TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController::class)
            ->setMethods(array('dummy'))
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertTrue($abstractWidgetController->canProcessRequest($request));
    }

    /**
     * @test
     */
    public function processRequestSetsWidgetConfiguration()
    {
        $widgetContext = $this->createMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
        $widgetContext->expects($this->once())->method('getWidgetConfiguration')->will($this->returnValue('myConfiguration'));
        /** @var WidgetRequest|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class);
        $request->expects($this->once())->method('getWidgetContext')->will($this->returnValue($widgetContext));
        /** @var ResponseInterface|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->createMock(\TYPO3\CMS\Extbase\Mvc\ResponseInterface::class);
        /** @var AbstractWidgetController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $abstractWidgetController */
        $abstractWidgetController = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController::class, array('resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'initializeAction', 'checkRequestHash', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext', 'resolveView', 'callActionMethod'), array(), '', false);
        $mockUriBuilder = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
        $objectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $objectManager->expects($this->any())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class)->will($this->returnValue($mockUriBuilder));

        $configurationService = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        $abstractWidgetController->_set('mvcPropertyMappingConfigurationService', $configurationService);
        $abstractWidgetController->_set('arguments', new Arguments());

        $abstractWidgetController->_set('objectManager', $objectManager);
        $abstractWidgetController->processRequest($request, $response);
        $widgetConfiguration = $abstractWidgetController->_get('widgetConfiguration');
        $this->assertEquals('myConfiguration', $widgetConfiguration);
    }

    /**
     * @test
     * @dataProvider getSetViewConfigurationTestValues
     * @param array $parent
     * @param array|NULL $widget
     * @param array $expected
     */
    public function setViewConfigurationPerformsExpectedInitialization(array $parent, $widget, array $expected)
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->expects($this->once())->method('getConfiguration')->willReturn(array(
            'view' => array(
                'widget' => array(
                    'foobarClassName' => $widget
                )
            )
        ));
        $parentRequest = $this->getMockBuilder(Request::class)
            ->setMethods(array('getControllerExtensionKey'))
            ->getMock();
        $parentRequest->expects($this->once())->method('getControllerExtensionKey')->willReturn(null);
        $controllerContext = $this->getMockBuilder(ControllerContext::class)
            ->setMethods(array('getRequest'))
            ->getMock();
        $controllerContext->expects($this->once())->method('getRequest')->willReturn($parentRequest);
        $templatePaths = $this->getMockBuilder(TemplatePaths::class)
            ->setMethods(array('fillFromConfigurationArray', 'toArray'))
            ->getMock();
        $templatePaths->expects($this->once())->method('fillFromConfigurationArray')->with($expected);
        $templatePaths->expects($this->any())->method('toArray')->willReturn($parent);
        $widgetContext = $this->getMockBuilder(WidgetContext::class)
            ->setMethods(array('getWidgetViewHelperClassName'))
            ->getMock();
        $widgetContext->expects($this->once())->method('getWidgetViewHelperClassName')->willReturn('foobarClassName');
        $request = $this->getMockBuilder(Request::class)
            ->setMethods(array('getWidgetContext'))
            ->getMock();
        $request->expects($this->once())->method('getWidgetContext')->willReturn($widgetContext);

        $view = $this->getAccessibleMock(TemplateView::class, array('getTemplatePaths', 'toArray'), array(), '', false);
        $view->expects($this->exactly(2))->method('getTemplatePaths')->willReturn($templatePaths);

        $mock = $this->getAccessibleMock(AbstractWidgetController::class, array('dummy'));
        $mock->_set('configurationManager', $configurationManager);
        $mock->_set('controllerContext', $controllerContext);
        $mock->_set('request', $request);
        $method = new \ReflectionMethod(AbstractWidgetController::class, 'setViewConfiguration');
        $method->setAccessible(true);
        $method->invokeArgs($mock, array($view));
    }

    /**
     * @return array
     */
    public function getSetViewConfigurationTestValues()
    {
        return array(
            'Empty path sets cause empty widget paths' => array(
                array(),
                null,
                array(
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => array(),
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => array(),
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => array()
                )
            ),
            'Parent request paths are reused when not overridden' => array(
                array(
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => array('foo'),
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => array('bar'),
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => array('baz')
                ),
                array(),
                array(
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => array('foo'),
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => array('bar'),
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => array('baz')
                )
            ),
            'Widget paths are added to parent paths' => array(
                array(
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => array('foo1'),
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => array('bar1'),
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => array('baz1')
                ),
                array(
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => array('foo2'),
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => array('bar2'),
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => array('baz2')
                ),
                array(
                    TemplatePaths::CONFIG_TEMPLATEROOTPATHS => array('foo1', 'foo2'),
                    TemplatePaths::CONFIG_LAYOUTROOTPATHS => array('bar1', 'bar2'),
                    TemplatePaths::CONFIG_PARTIALROOTPATHS => array('baz1', 'baz2')
                )
            ),
        );
    }
}
