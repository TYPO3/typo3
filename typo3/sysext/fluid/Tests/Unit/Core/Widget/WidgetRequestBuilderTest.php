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
class Tx_Fluid_Tests_Unit_Core_Widget_WidgetRequestBuilderTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

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
	 * @var array
	 */
	protected $serverBackup;

	/**
	 * @var array
	 */
	protected $getBackup;

	/**
	 * @var array
	 */
	protected $postBackup;

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUp() {
		$this->serverBackup = $_SERVER;
		$this->getBackup = $_GET;
		$this->postBackup = $_POST;
		$this->widgetRequestBuilder = $this->getAccessibleMock('Tx_Fluid_Core_Widget_WidgetRequestBuilder', array('setArgumentsFromRawRequestData'));

		$this->mockWidgetRequest = $this->getMock('Tx_Fluid_Core_Widget_WidgetRequest');

		$this->mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$this->mockObjectManager->expects($this->once())->method('create')->with('Tx_Fluid_Core_Widget_WidgetRequest')->will($this->returnValue($this->mockWidgetRequest));

		$this->widgetRequestBuilder->_set('objectManager', $this->mockObjectManager);

		$this->mockWidgetContext = $this->getMock('Tx_Fluid_Core_Widget_WidgetContext');

		$this->mockAjaxWidgetContextHolder = $this->getMock('Tx_Fluid_Core_Widget_AjaxWidgetContextHolder');
		$this->widgetRequestBuilder->injectAjaxWidgetContextHolder($this->mockAjaxWidgetContextHolder);
		$this->mockAjaxWidgetContextHolder->expects($this->once())->method('get')->will($this->returnValue($this->mockWidgetContext));
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		$_SERVER = $this->serverBackup;
		$_GET = $this->getBackup;
		$_POST = $this->postBackup;
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsRequestUri() {
		$requestUri = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
		$this->mockWidgetRequest->expects($this->once())->method('setRequestURI')->with($requestUri);

		$this->widgetRequestBuilder->build();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsBaseUri() {
		$baseUri = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$this->mockWidgetRequest->expects($this->once())->method('setBaseURI')->with($baseUri);

		$this->widgetRequestBuilder->build();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsRequestMethod() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->mockWidgetRequest->expects($this->once())->method('setMethod')->with('POST');

		$this->widgetRequestBuilder->build();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsPostArgumentsFromRequest() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_GET = array('get' => 'foo');
		$_POST = array('post' => 'bar');
		$this->mockWidgetRequest->expects($this->once())->method('setArguments')->with($_POST);

		$this->widgetRequestBuilder->build();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsGetArgumentsFromRequest() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET = array('get' => 'foo');
		$_POST = array('post' => 'bar');
		$this->mockWidgetRequest->expects($this->once())->method('setArguments')->with($_GET);

		$this->widgetRequestBuilder->build();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsControllerActionNameFromGetArguments() {
		$_GET = array('action' => 'myAction');
		$this->mockWidgetRequest->expects($this->once())->method('setControllerActionName')->with('myAction');

		$this->widgetRequestBuilder->build();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsWidgetContext() {
		$_GET = array('fluid-widget-id' => '123');
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