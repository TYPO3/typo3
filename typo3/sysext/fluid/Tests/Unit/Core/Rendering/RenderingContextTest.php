<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering;

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
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Test case
 */
class RenderingContextTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Parsing state
     *
     * @var \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface
     */
    protected $renderingContext;

    protected function setUp()
    {
        $this->renderingContext = $this->getAccessibleMock(RenderingContextFixture::class, ['dummy']);
    }

    /**
     * @test
     */
    public function getObjectManagerReturnsObjectManagerPropertyValue()
    {
        $this->renderingContext->_set('objectManager', 'test');
        $this->assertEquals('test', $this->renderingContext->getObjectManager());
    }

    /**
     * @test
     */
    public function setControllerContextWithSubpackageKeySetsExpectedControllerContext()
    {
        $renderingContext = $this->getMockBuilder(RenderingContextFixture::class)
            ->setMethods(['setControllerAction', 'setControllerName'])
            ->getMock();
        $request = $this->getMockBuilder(Request::class)
            ->setMethods(['getControllerActionName', 'getControllerSubpackageKey', 'getControllerName'])
            ->getMock();
        $request->expects($this->exactly(2))->method('getControllerSubpackageKey')->willReturn('test1');
        $request->expects($this->once())->method('getControllerName')->willReturn('test2');
        $controllerContext = $this->getMockBuilder(ControllerContext::class)
            ->setMethods(['getRequest'])
            ->getMock();
        $controllerContext->expects($this->once())->method('getRequest')->willReturn($request);
        $renderingContext->expects($this->once())->method('setControllerName')->with('test1\\test2');
        $renderingContext->setControllerContext($controllerContext);
    }

    /**
     * @test
     */
    public function templateVariableContainerCanBeReadCorrectly()
    {
        $templateVariableContainer = $this->createMock(StandardVariableProvider::class);
        $this->renderingContext->setVariableProvider($templateVariableContainer);
        $this->assertSame($this->renderingContext->getVariableProvider(), $templateVariableContainer, 'Template Variable Container could not be read out again.');
    }

    /**
     * @test
     */
    public function controllerContextCanBeReadCorrectly()
    {
        $controllerContext = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $controllerContext->expects($this->atLeastOnce())->method('getRequest')->willReturn($this->createMock(Request::class));
        $this->renderingContext->setControllerContext($controllerContext);
        $this->assertSame($this->renderingContext->getControllerContext(), $controllerContext);
    }

    /**
     * @test
     */
    public function viewHelperVariableContainerCanBeReadCorrectly()
    {
        $viewHelperVariableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $this->renderingContext->_set('viewHelperVariableContainer', $viewHelperVariableContainer);
        $this->assertSame($viewHelperVariableContainer, $this->renderingContext->getViewHelperVariableContainer());
    }

    /**
     * @test
     * @dataProvider getControllerActionTestValues
     * @param string $input
     * @param string $expected
     */
    public function setControllerActionProcessesInputCorrectly($input, $expected)
    {
        $subject = new RenderingContextFixture();
        $request = $this->getMockBuilder(Request::class)->setMethods(['setControllerActionName'])->getMock();
        $request->expects($this->at(0))->method('setControllerActionName')->with('index');
        $request->expects($this->at(1))->method('setControllerActionName')->with(lcfirst($expected));
        $controllerContext = $this->getMockBuilder(ControllerContext::class)->setMethods(['getRequest'])->getMock();
        $controllerContext->expects($this->atLeastOnce())->method('getRequest')->willReturn($request);
        $subject->setControllerContext($controllerContext);
        $subject->setControllerAction($input);
        $this->assertAttributeSame($expected, 'controllerAction', $subject);
    }

    /**
     * @return array
     */
    public function getControllerActionTestValues()
    {
        return [
            ['default', 'default'],
            ['default.html', 'default'],
            ['default.sub.html', 'default'],
            ['Sub/Default', 'Sub/Default'],
            ['Sub/Default.html', 'Sub/Default'],
            ['Sub/Default.sub.html', 'Sub/Default']
        ];
    }
}
