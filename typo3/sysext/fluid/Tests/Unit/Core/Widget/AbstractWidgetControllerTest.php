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
 * Testcase for AbstractWidgetController
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_Core_Widget_AbstractWidgetControllerTest extends Tx_Extbase_BaseTestCase {
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function canHandleWidgetRequest() {
		$request = $this->getMock('Tx_Fluid_Core_Widget_WidgetRequest', array('dummy'), array(), '', FALSE);
		$abstractWidgetController = $this->getMock('Tx_Fluid_Core_Widget_AbstractWidgetController', array('dummy'), array(), '', FALSE);
		$this->assertTrue($abstractWidgetController->canProcessRequest($request));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function processRequestSetsWidgetConfiguration() {
		$widgetContext = $this->getMock('Tx_Fluid_Core_Widget_WidgetContext', array('getWidgetConfiguration'));
		$widgetContext->expects($this->once())->method('getWidgetConfiguration')->will($this->returnValue('myConfiguration'));

		$request = $this->getMock('Tx_Fluid_Core_Widget_WidgetRequest', array('getWidgetContext', 'getControllerObjectName', 'getControllerActionName'), array(), '', FALSE);
		$request->expects($this->once())->method('getWidgetContext')->will($this->returnValue($widgetContext));

		$response = $this->getMock('Tx_Fluid_MVC_ResponseInterface');

		$abstractWidgetController = $this->getAccessibleMock('Tx_Fluid_Core_Widget_AbstractWidgetController', array('initializeUriBuilder', 'resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'mapRequestArgumentsToControllerArguments', 'resolveView', 'callActionMethod'), array(), '', FALSE);

		$objectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$abstractWidgetController->_set('objectManager', $objectManager);

		$abstractWidgetController->processRequest($request, $response);

		$widgetConfiguration = $abstractWidgetController->_get('widgetConfiguration');
		$this->assertEquals('myConfiguration', $widgetConfiguration);
	}
}
?>