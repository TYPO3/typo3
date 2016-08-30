<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Widget;

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

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
        $widgetContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class, ['getControllerObjectName']);
        $widgetContext->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('Tx_Fluid_ControllerObjectName'));
        $widgetRequest = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class, ['setControllerObjectName']);
        $widgetRequest->expects($this->once())->method('setControllerObjectName')->with('Tx_Fluid_ControllerObjectName');
        $widgetRequest->setWidgetContext($widgetContext);
    }

    /**
     * @test
     */
    public function widgetContextCanBeReadAgain()
    {
        $widgetContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
        $widgetRequest = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class, ['setControllerObjectName']);
        $widgetRequest->setWidgetContext($widgetContext);
        $this->assertSame($widgetContext, $widgetRequest->getWidgetContext());
    }
}
