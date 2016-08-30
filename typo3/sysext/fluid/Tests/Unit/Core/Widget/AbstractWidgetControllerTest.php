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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController;
use TYPO3\CMS\Fluid\Core\Widget\WidgetRequest;

/**
 * Test case
 */
class AbstractWidgetControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function canHandleWidgetRequest()
    {
        /** @var WidgetRequest|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class, ['dummy'], [], '', false);
        /** @var AbstractWidgetController|\PHPUnit_Framework_MockObject_MockObject $abstractWidgetController */
        $abstractWidgetController = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController::class, ['dummy'], [], '', false);
        $this->assertTrue($abstractWidgetController->canProcessRequest($request));
    }

    /**
     * @test
     */
    public function processRequestSetsWidgetConfiguration()
    {
        $widgetContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
        $widgetContext->expects($this->once())->method('getWidgetConfiguration')->will($this->returnValue('myConfiguration'));
        /** @var WidgetRequest|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class, [], [], '', false);
        $request->expects($this->once())->method('getWidgetContext')->will($this->returnValue($widgetContext));
        /** @var ResponseInterface|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->getMock(\TYPO3\CMS\Extbase\Mvc\ResponseInterface::class);
        /** @var AbstractWidgetController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $abstractWidgetController */
        $abstractWidgetController = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'initializeAction', 'checkRequestHash', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext', 'resolveView', 'callActionMethod'], [], '', false);
        $mockUriBuilder = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $objectManager->expects($this->any())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class)->will($this->returnValue($mockUriBuilder));

        $configurationService = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService::class);
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
    public function viewConfigurationCanBeOverriddenThroughFrameworkConfiguration()
    {
        $frameworkConfiguration = [
            'view' => [
                'widget' => [
                    \TYPO3\CMS\Fluid\ViewHelpers\Widget\PaginateViewHelper::class => [
                        'templateRootPath' => 'EXT:fluid/Resources/Private/DummyTestTemplates'
                    ]
                ]
            ]
        ];
        $widgetContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
        $widgetContext->expects($this->any())->method('getWidgetViewHelperClassName')->will($this->returnValue(\TYPO3\CMS\Fluid\ViewHelpers\Widget\PaginateViewHelper::class));
        $request = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class, [], [], '', false);
        $request->expects($this->any())->method('getWidgetContext')->will($this->returnValue($widgetContext));
        $configurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class);
        $configurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($frameworkConfiguration));
        $view = $this->getAccessibleMock(\TYPO3\CMS\Fluid\View\TemplateView::class, ['dummy'], [], '', false);
        $abstractWidgetController = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController::class, ['dummy']);
        $abstractWidgetController->_set('configurationManager', $configurationManager);
        $abstractWidgetController->_set('request', $request);
        $abstractWidgetController->_call('setViewConfiguration', $view);
        $this->assertSame([GeneralUtility::getFileAbsFileName('EXT:fluid/Resources/Private/DummyTestTemplates')], $view->_call('getTemplateRootPaths'));
    }
}
