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

/**
 * Test case
 */
class WidgetRequestTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function setWidgetContextAlsoSetsControllerObjectName()
    {
        $widgetContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class, array('getControllerObjectName'));
        $widgetContext->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('Tx_Fluid_ControllerObjectName'));
        $widgetRequest = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class, array('setControllerObjectName'));
        $widgetRequest->expects($this->once())->method('setControllerObjectName')->with('Tx_Fluid_ControllerObjectName');
        $widgetRequest->setWidgetContext($widgetContext);
    }

    /**
     * @test
     */
    public function getArgumentPrefixReadsVariablesFromWidgetContext()
    {
        $widgetContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class, array('getParentPluginNamespace', 'getWidgetIdentifier'));
        $widgetContext->expects($this->once())->method('getParentPluginNamespace')->will($this->returnValue('foo'));
        $widgetContext->expects($this->once())->method('getWidgetIdentifier')->will($this->returnValue('bar'));
        $widgetRequest = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class, array('dummy'));
        $widgetRequest->_set('widgetContext', $widgetContext);
        $this->assertEquals('foo[bar]', $widgetRequest->getArgumentPrefix());
    }

    /**
     * @test
     */
    public function widgetContextCanBeReadAgain()
    {
        $widgetContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
        $widgetRequest = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class, array('setControllerObjectName'));
        $widgetRequest->setWidgetContext($widgetContext);
        $this->assertSame($widgetContext, $widgetRequest->getWidgetContext());
    }
}
