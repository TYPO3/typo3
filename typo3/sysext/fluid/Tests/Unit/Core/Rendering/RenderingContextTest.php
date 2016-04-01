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

/**
 * Test case
 */
class RenderingContextTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Parsing state
     *
     * @var \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface
     */
    protected $renderingContext;

    protected function setUp()
    {
        $this->renderingContext = $this->getAccessibleMock(RenderingContextFixture::class, array('dummy'));
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
        $renderingContext = $this->getMock(RenderingContextFixture::class, array('setControllerAction', 'setControllerName'));
        $request = $this->getMock(Request::class, array('getControllerActionName', 'getControllerSubpackageKey', 'getControllerName'));
        $request->expects($this->exactly(2))->method('getControllerSubpackageKey')->willReturn('test1');
        $request->expects($this->once())->method('getControllerName')->willReturn('test2');
        $controllerContext = $this->getMock(ControllerContext::class, array('getRequest'));
        $controllerContext->expects($this->once())->method('getRequest')->willReturn($request);
        $renderingContext->expects($this->once())->method('setControllerName')->with('test1\\test2');
        $renderingContext->setControllerContext($controllerContext);
    }

    /**
     * @test
     */
    public function templateVariableContainerCanBeReadCorrectly()
    {
        $templateVariableContainer = $this->getMock(\TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer::class);
        $this->renderingContext->setVariableProvider($templateVariableContainer);
        $this->assertSame($this->renderingContext->getTemplateVariableContainer(), $templateVariableContainer, 'Template Variable Container could not be read out again.');
    }

    /**
     * @test
     */
    public function controllerContextCanBeReadCorrectly()
    {
        $controllerContext = $this->getMock(
            \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class,
            array('getRequest'), array(), '', false
        );
        $controllerContext->expects($this->atLeastOnce())->method('getRequest')->willReturn($this->getMock(Request::class));
        $this->renderingContext->setControllerContext($controllerContext);
        $this->assertSame($this->renderingContext->getControllerContext(), $controllerContext);
    }

    /**
     * @test
     */
    public function viewHelperVariableContainerCanBeReadCorrectly()
    {
        $viewHelperVariableContainer = $this->getMock(\TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer::class);
        $this->renderingContext->_set('viewHelperVariableContainer', $viewHelperVariableContainer);
        $this->assertSame($viewHelperVariableContainer, $this->renderingContext->getViewHelperVariableContainer());
    }
}
