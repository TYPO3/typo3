<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
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
 * Testcase for WidgetRequest
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_Core_Widget_WidgetRequestTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setWidgetContextAlsoSetsControllerObjectName() {
		$widgetContext = $this->getMock('Tx_Fluid_Core_Widget_WidgetContext', array('getControllerObjectName'));
		$widgetContext->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('Tx_Fluid_ControllerObjectName'));

		$widgetRequest = $this->getMock('Tx_Fluid_Core_Widget_WidgetRequest', array('setControllerObjectName'));
		$widgetRequest->expects($this->once())->method('setControllerObjectName')->with('Tx_Fluid_ControllerObjectName');

		$widgetRequest->setWidgetContext($widgetContext);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	 public function widgetContextCanBeReadAgain() {
		$widgetContext = $this->getMock('Tx_Fluid_Core_Widget_WidgetContext');

		$widgetRequest = $this->getMock('Tx_Fluid_Core_Widget_WidgetRequest', array('setControllerObjectName'));
		$widgetRequest->setWidgetContext($widgetContext);

		$this->assertSame($widgetContext, $widgetRequest->getWidgetContext());
	 }
}
?>