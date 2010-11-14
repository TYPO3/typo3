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
 * Testcase for WidgetRequestBuilder
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_Core_Widget_WidgetRequestBuilderTest extends Tx_Extbase_BaseTestCase {

	/**
	 * @var Tx_Fluid_Core_Widget_WidgetRequestBuilder
	 */
	protected $widgetRequestBuilder;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var Tx_Fluid_Core_Widget_WidgetRequest
	 */
	protected $mockWidgetRequest;

	/**
	 * @var Tx_Fluid_Core_Widget_AjaxWidgetContextHolder
	 */
	protected $mockAjaxWidgetContextHolder;

	/**
	 * @var Tx_Fluid_Core_Widget_WidgetContext
	 */
	protected $mockWidgetContext;

	/**
	 * @var Tx_Fluid_Utility_Environment
	 */
	protected $mockEnvironment;

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->widgetRequestBuilder = $this->getAccessibleMock('Tx_Fluid_Core_Widget_WidgetRequestBuilder', array('setArgumentsFromRawRequestData'));

		$this->mockWidgetRequest = $this->getMock('Tx_Fluid_Core_Widget_WidgetRequest');

		$this->mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$this->mockObjectManager->expects($this->once())->method('create')->with('Tx_Fluid_Core_Widget_WidgetRequest')->will($this->returnValue($this->mockWidgetRequest));

		$this->widgetRequestBuilder->_set('objectManager', $this->mockObjectManager);

		$this->mockWidgetContext = $this->getMock('Tx_Fluid_Core_Widget_WidgetContext');

		$this->mockAjaxWidgetContextHolder = $this->getMock('Tx_Fluid_Core_Widget_AjaxWidgetContextHolder');
		$this->widgetRequestBuilder->injectAjaxWidgetContextHolder($this->mockAjaxWidgetContextHolder);
		$this->mockAjaxWidgetContextHolder->expects($this->once())->method('get')->will($this->returnValue($this->mockWidgetContext));

		$this->mockEnvironment = $this->getMock('Tx_Fluid_Utility_Environment');
		$this->widgetRequestBuilder->_set('environment', $this->mockEnvironment);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function buildInjectsEnvironmentInRequest() {
		$this->mockWidgetRequest->expects($this->once())->method('injectEnvironment')->with($this->mockEnvironment);

		$this->widgetRequestBuilder->build();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function buildSetsRequestMethodFromEnvironment() {
		$this->mockEnvironment->expects($this->once())->method('getRequestMethod')->will($this->returnValue('POST'));
		$this->mockWidgetRequest->expects($this->once())->method('setMethod')->with('POST');

		$this->widgetRequestBuilder->build();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function buildCallsSetArgumentsFromRawRequestData() {
		$this->widgetRequestBuilder->expects($this->once())->method('setArgumentsFromRawRequestData')->with($this->mockWidgetRequest);

		$this->widgetRequestBuilder->build();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function buildSetsControllerActionNameFromGetArguments() {
		$this->mockEnvironment->expects($this->once())->method('getRawGetArguments')->will($this->returnValue(array('action' => 'myaction', 'f3-fluid-widget-id' => '')));
		$this->mockWidgetRequest->expects($this->once())->method('setControllerActionName')->with('myaction');

		$this->widgetRequestBuilder->build();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function buildSetsWidgetContext() {
		$this->mockEnvironment->expects($this->once())->method('getRawGetArguments')->will($this->returnValue(array('f3-fluid-widget-id' => '123')));
		$this->mockAjaxWidgetContextHolder->expects($this->once())->method('get')->with('123')->will($this->returnValue($this->mockWidgetContext));
		$this->mockWidgetRequest->expects($this->once())->method('setWidgetContext')->with($this->mockWidgetContext);

		$this->widgetRequestBuilder->build();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function buildReturnsRequest() {
		$expected = $this->mockWidgetRequest;
		$actual = $this->widgetRequestBuilder->build();
		$this->assertSame($expected, $actual);
	}
}
?>