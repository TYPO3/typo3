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
 * Testcase for AbstractWidgetController
 */
class AbstractWidgetControllerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function canHandleWidgetRequest() {
		$request = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequest', array('dummy'), array(), '', FALSE);
		$abstractWidgetController = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Widget\\AbstractWidgetController', array('dummy'), array(), '', FALSE);
		$this->assertTrue($abstractWidgetController->canProcessRequest($request));
	}

	/**
	 * @test
	 */
	public function processRequestSetsWidgetConfiguration() {
		$widgetContext = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetContext');
		$widgetContext->expects($this->once())->method('getWidgetConfiguration')->will($this->returnValue('myConfiguration'));
		$request = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequest', array(), array(), '', FALSE);
		$request->expects($this->once())->method('getWidgetContext')->will($this->returnValue($widgetContext));
		$response = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\ResponseInterface');
		$abstractWidgetController = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Widget\\AbstractWidgetController', array('resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'initializeAction', 'checkRequestHash', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext', 'resolveView', 'callActionMethod'), array(), '', FALSE);
		$mockUriBuilder = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
		$objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$objectManager->expects($this->any())->method('get')->with('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder')->will($this->returnValue($mockUriBuilder));

		$configurationService = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\MvcPropertyMappingConfigurationService');
		$abstractWidgetController->injectMvcPropertyMappingConfigurationService($configurationService);
		$abstractWidgetController->_set('arguments', new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments());

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
					'TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\PaginateViewHelper' => array(
						'templateRootPath' => 'EXT:fluid/Resources/Private/DummyTestTemplates'
					)
				)
			)
		);
		$widgetContext = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetContext');
		$widgetContext->expects($this->any())->method('getWidgetViewHelperClassName')->will($this->returnValue('TYPO3\\CMS\\Fluid\\ViewHelpers\\Widget\\PaginateViewHelper'));
		$request = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequest', array(), array(), '', FALSE);
		$request->expects($this->any())->method('getWidgetContext')->will($this->returnValue($widgetContext));
		$configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
		$configurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($frameworkConfiguration));
		$view = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\View\\TemplateView', array('dummy'));
		$abstractWidgetController = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Widget\\AbstractWidgetController', array('dummy'));
		$abstractWidgetController->injectConfigurationManager($configurationManager);
		$abstractWidgetController->_set('request', $request);
		$abstractWidgetController->_call('setViewConfiguration', $view);
		$this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:fluid/Resources/Private/DummyTestTemplates'), $view->_call('getTemplateRootPath'));
	}
}

?>