<?php

declare(strict_types=1);

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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

final class AbstractTemplateViewTest extends UnitTestCase
{
    protected AbstractTemplateView&MockObject&AccessibleObjectInterface $view;

    protected RenderingContext&MockObject $renderingContext;

    protected ViewHelperVariableContainer&MockObject $viewHelperVariableContainer;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelperVariableContainer = $this->getMockBuilder(ViewHelperVariableContainer::class)
            ->onlyMethods(['setView'])
            ->getMock();
        $this->renderingContext = $this->getMockBuilder(RenderingContext::class)
            ->onlyMethods(['getViewHelperVariableContainer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->renderingContext->method('getViewHelperVariableContainer')->willReturn($this->viewHelperVariableContainer);
        $this->view = $this->getAccessibleMock(AbstractTemplateView::class, null, [], '', false);
        $this->view->setRenderingContext($this->renderingContext);
    }

    #[Test]
    public function viewIsPlacedInViewHelperVariableContainer(): void
    {
        $this->viewHelperVariableContainer->expects($this->once())->method('setView')->with($this->view);
        $this->view->setRenderingContext($this->renderingContext);
    }
}
