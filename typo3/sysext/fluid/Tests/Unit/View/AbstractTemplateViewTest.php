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

namespace TYPO3\CMS\Fluid\Tests\Unit\View;

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Test case
 */
class AbstractTemplateViewTest extends UnitTestCase
{
    /**
     * @var AbstractTemplateView|AccessibleObjectInterface
     */
    protected $view;

    /**
     * @var RenderingContext|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $renderingContext;

    /**
     * @var ViewHelperVariableContainer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $viewHelperVariableContainer;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelperVariableContainer = $this->getMockBuilder(ViewHelperVariableContainer::class)
            ->setMethods(['setView'])
            ->getMock();
        $this->renderingContext = $this->getMockBuilder(RenderingContext::class)
            ->setMethods(['getViewHelperVariableContainer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->renderingContext->expects(self::any())->method('getViewHelperVariableContainer')->willReturn($this->viewHelperVariableContainer);
        $this->view = $this->getAccessibleMock(AbstractTemplateView::class, ['dummy'], [], '', false);
        $this->view->setRenderingContext($this->renderingContext);
    }

    /**
     * @test
     */
    public function viewIsPlacedInViewHelperVariableContainer()
    {
        $this->viewHelperVariableContainer->expects(self::once())->method('setView')->with($this->view);
        $this->view->setRenderingContext($this->renderingContext);
    }
}
