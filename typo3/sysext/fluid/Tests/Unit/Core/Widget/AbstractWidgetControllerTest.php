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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController;
use TYPO3\CMS\Fluid\Core\Widget\WidgetContext;
use TYPO3\CMS\Fluid\Core\Widget\WidgetRequest;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Fluid\ViewHelpers\Widget\PaginateViewHelper;

/**
 * Test case
 */
class AbstractWidgetControllerTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function canHandleWidgetRequest() {
		/** @var WidgetRequest|\PHPUnit_Framework_MockObject_MockObject $request */
		$request = $this->getMock(WidgetRequest::class, array('dummy'), array(), '', FALSE);
		/** @var AbstractWidgetController|\PHPUnit_Framework_MockObject_MockObject $abstractWidgetController */
		$abstractWidgetController = $this->getMock(AbstractWidgetController::class, array('dummy'), array(), '', FALSE);
		$this->assertTrue($abstractWidgetController->canProcessRequest($request));
	}

	/**
	 * @test
	 */
	public function processRequestSetsWidgetConfiguration() {
		$widgetContext = $this->getMock(WidgetContext::class);
		$widgetContext->expects($this->once())->method('getWidgetConfiguration')->will($this->returnValue('myConfiguration'));
		/** @var WidgetRequest|\PHPUnit_Framework_MockObject_MockObject $request */
		$request = $this->getMock(WidgetRequest::class, array(), array(), '', FALSE);
		$request->expects($this->once())->method('getWidgetContext')->will($this->returnValue($widgetContext));
		/** @var ResponseInterface|\PHPUnit_Framework_MockObject_MockObject $response */
		$response = $this->getMock(ResponseInterface::class);
		/** @var AbstractWidgetController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $abstractWidgetController */
		$abstractWidgetController = $this->getAccessibleMock(AbstractWidgetController::class, array('resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'initializeAction', 'checkRequestHash', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext', 'resolveView', 'callActionMethod'), array(), '', FALSE);
		$mockUriBuilder = $this->getMock(UriBuilder::class);
		$objectManager = $this->getMock(ObjectManagerInterface::class);
		$objectManager->expects($this->any())->method('get')->with(UriBuilder::class)->will($this->returnValue($mockUriBuilder));

		$configurationService = $this->getMock(MvcPropertyMappingConfigurationService::class);
		$abstractWidgetController->_set('mvcPropertyMappingConfigurationService', $configurationService);
		$abstractWidgetController->_set('arguments', new Arguments());

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
					PaginateViewHelper::class => array(
						'templateRootPath' => 'EXT:fluid/Resources/Private',
						'templateRootPaths' => ['EXT:fluid/Resources/Private']
					)
				)
			)
		);
		$overriddenConfiguration['view'] = array_merge_recursive($frameworkConfiguration['view'], $frameworkConfiguration['view']['widget'][PaginateViewHelper::class]);

		$widgetContext = $this->getMock(WidgetContext::class);
		$widgetContext->expects($this->any())->method('getWidgetViewHelperClassName')->will($this->returnValue(PaginateViewHelper::class));

		$request = $this->getMock(WidgetRequest::class, array(), array(), '', FALSE);
		$request->expects($this->any())->method('getWidgetContext')->will($this->returnValue($widgetContext));
		$request->expects($this->any())->method('getControllerExtensionKey')->will($this->returnValue('fluid'));

		$configurationManager = $this->getMock(ConfigurationManager::class);
		$configurationManager->expects($this->at(1))->method('setConfiguration')->with($overriddenConfiguration);
		$configurationManager->expects($this->any())->method('getConfiguration')->willReturnOnConsecutiveCalls($this->returnValue($frameworkConfiguration), $this->returnValue($overriddenConfiguration));

		$controllerContext = $this->getMock(ControllerContext::class);
		$controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($request));

		/** @var TemplateView|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $view */
		$view = $this->getAccessibleMock(TemplateView::class, array('dummy'), array(), '', FALSE);
		$view->_set('controllerContext', $controllerContext);

		$abstractWidgetController = $this->getAccessibleMock(AbstractWidgetController::class, array('dummy'));
		$abstractWidgetController->_set('configurationManager', $configurationManager);
		$abstractWidgetController->_set('request', $request);
		$abstractWidgetController->_set('controllerContext', $controllerContext);
		$abstractWidgetController->_call('setViewConfiguration', $view);
		$this->assertSame(array('EXT:fluid/Resources/Private'), $view->_call('getTemplateRootPaths'));
	}

}
