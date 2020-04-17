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

use TYPO3\CMS\Fluid\Core\Widget\WidgetContext;
use TYPO3\CMS\Fluid\Core\Widget\WidgetRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class WidgetRequestTest extends UnitTestCase
{
    /**
     * @test
     */
    public function setWidgetContextAlsoSetsControllerObjectName()
    {
        $widgetContext = $this->getMockBuilder(WidgetContext::class)
            ->setMethods(['getControllerObjectName'])
            ->getMock();
        $widgetContext->expects(self::once())->method('getControllerObjectName')->willReturn('Tx_Fluid_ControllerObjectName');
        $widgetRequest = $this->getMockBuilder(WidgetRequest::class)
            ->setMethods(['setControllerObjectName'])
            ->getMock();
        $widgetRequest->expects(self::once())->method('setControllerObjectName')->with('Tx_Fluid_ControllerObjectName');
        $widgetRequest->setWidgetContext($widgetContext);
    }

    /**
     * @test
     */
    public function getArgumentPrefixReadsVariablesFromWidgetContext()
    {
        $widgetContext = $this->getMockBuilder(WidgetContext::class)
            ->setMethods(['getParentPluginNamespace', 'getWidgetIdentifier'])
            ->getMock();
        $widgetContext->expects(self::once())->method('getParentPluginNamespace')->willReturn('foo');
        $widgetContext->expects(self::once())->method('getWidgetIdentifier')->willReturn('bar');
        $widgetRequest = $this->getAccessibleMock(WidgetRequest::class, ['dummy']);
        $widgetRequest->_set('widgetContext', $widgetContext);
        self::assertEquals('foo[bar]', $widgetRequest->getArgumentPrefix());
    }

    /**
     * @test
     */
    public function widgetContextCanBeReadAgain()
    {
        $widgetContext = $this->createMock(WidgetContext::class);
        $widgetRequest = $this->getMockBuilder(WidgetRequest::class)
            ->setMethods(['setControllerObjectName'])
            ->getMock();
        $widgetRequest->setWidgetContext($widgetContext);
        self::assertSame($widgetContext, $widgetRequest->getWidgetContext());
    }
}
