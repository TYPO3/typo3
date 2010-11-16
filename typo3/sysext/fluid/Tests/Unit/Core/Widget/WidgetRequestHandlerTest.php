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
 * Testcase for WidgetRequestHandler
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_Core_Widget_WidgetRequestHandlerTest extends Tx_Extbase_BaseTestCase {

	/**
	 * @var Tx_Fluid_Core_Widget_WidgetRequestHandler
	 */
	protected $widgetRequestHandler;

	/**
	 * @var Tx_Fluid_Utility_Environment
	 */
	protected $mockEnvironment;
	
	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->mockEnvironment = $this->getMock('Tx_Fluid_Utility_Environment');

		$this->widgetRequestHandler = $this->getAccessibleMock('Tx_Fluid_Core_Widget_WidgetRequestHandler', array('dummy'), array(), '', FALSE);
		$this->widgetRequestHandler->_set('environment', $this->mockEnvironment);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function canHandleRequestReturnsTrueIfCorrectGetParameterIsSet() {
		$this->mockEnvironment->expects($this->once())->method('getRawGetArguments')->will($this->returnValue(array('f3-fluid-widget-id' => '123')));
		$this->assertTrue($this->widgetRequestHandler->canHandleRequest());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function canHandleRequestReturnsFalsefGetParameterIsNotSet() {
		$this->mockEnvironment->expects($this->once())->method('getRawGetArguments')->will($this->returnValue(array('some-other-id' => '123')));
		$this->assertFalse($this->widgetRequestHandler->canHandleRequest());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function priorityIsHigherThanDefaultRequestHandler() {
		$defaultWebRequestHandler = $this->getMock('Tx_Extbase_MVC_Web_RequestHandler', array('dummy'), array(), '', FALSE);
		$this->assertTrue($this->widgetRequestHandler->getPriority() > $defaultWebRequestHandler->getPriority());
	}
}
?>