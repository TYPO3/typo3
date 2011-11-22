<?php

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
 * Testcase for AbstractWidgetController
 *
 */
class Tx_Fluid_Tests_Unit_Core_Widget_AbstractWidgetControllerTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function canHandleWidgetRequest() {
		$request = $this->getMock('Tx_Fluid_Core_Widget_WidgetRequest', array('dummy'), array(), '', FALSE);
		$abstractWidgetController = $this->getMock('Tx_Fluid_Core_Widget_AbstractWidgetController', array('dummy'), array(), '', FALSE);
		$this->assertTrue($abstractWidgetController->canProcessRequest($request));
	}

	/**
	 * @test
	 */
	public function processRequestSetsWidgetConfiguration() {
		$widgetContext = $this->getMock('Tx_Fluid_Core_Widget_WidgetContext');
		$widgetContext->expects($this->once())->method('getWidgetConfiguration')->will($this->returnValue('myConfiguration'));

		$request = $this->getMock('Tx_Fluid_Core_Widget_WidgetRequest', array(), array(), '', FALSE);
		$request->expects($this->once())->method('getWidgetContext')->will($this->returnValue($widgetContext));

		$response = $this->getMock('Tx_Extbase_MVC_ResponseInterface');

		$abstractWidgetController = $this->getAccessibleMock('Tx_Fluid_Core_Widget_AbstractWidgetController', array('resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'initializeAction', 'checkRequestHash', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext', 'resolveView', 'callActionMethod'), array(), '', FALSE);

		$mockUriBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_UriBuilder');

		$objectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$objectManager->expects($this->any())->method('create')->with('Tx_Extbase_MVC_Web_Routing_UriBuilder')->will($this->returnValue($mockUriBuilder));
		$abstractWidgetController->_set('objectManager', $objectManager);

		$abstractWidgetController->processRequest($request, $response);

		$widgetConfiguration = $abstractWidgetController->_get('widgetConfiguration');
		$this->assertEquals('myConfiguration', $widgetConfiguration);
	}

	/**
	 * @test
	 */
	public function viewConfigurationCanBeOverriddenThroughFrameworkConfiguration() {
		$frameworkConfiguration = array(
			'view' => array(
				'widget' => array(
					'Tx_Fluid_ViewHelpers_Widget_PaginateViewHelper' => array(
						'templateRootPath' => 'EXT:fluid/Resources/Private/DummyTestTemplates'
					)
				)
			)
		);

		$widgetContext = $this->getMock('Tx_Fluid_Core_Widget_WidgetContext');
		$widgetContext->expects($this->any())->method('getWidgetViewHelperClassName')->will($this->returnValue('Tx_Fluid_ViewHelpers_Widget_PaginateViewHelper'));

		$request = $this->getMock('Tx_Fluid_Core_Widget_WidgetRequest', array(), array(), '', FALSE);
		$request->expects($this->any())->method('getWidgetContext')->will($this->returnValue($widgetContext));

		$configurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManager');
		$configurationManager->expects($this->any())
			->method('getConfiguration')
			->will($this->returnValue($frameworkConfiguration));

		$view = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('dummy'));

		$abstractWidgetController = $this->getAccessibleMock('Tx_Fluid_Core_Widget_AbstractWidgetController', array('dummy'));
		$abstractWidgetController->injectConfigurationManager($configurationManager);
		$abstractWidgetController->_set('request', $request);
		$abstractWidgetController->_call('setViewConfiguration', $view);
		$this->assertEquals(t3lib_div::getFileAbsFileName('EXT:fluid/Resources/Private/DummyTestTemplates'), $view->_call('getTemplateRootPath'));
	}
}
?>