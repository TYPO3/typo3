<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

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
 * Testcase for RenderChildren ViewHelper
 */
class RenderChildrenViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\RenderChildrenViewHelper
     */
    protected $viewHelper;

    /**

     */
    protected function setUp()
    {
        $this->controllerContext = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class, [], [], '', false);
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\RenderChildrenViewHelper::class, ['renderChildren']);
        $this->viewHelper->_set('controllerContext', $this->controllerContext);
    }

    /**
     * @test
     */
    public function renderCallsEvaluateOnTheRootNodeAndRegistersTheArguments()
    {
        $this->request = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class);
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->viewHelper->initializeArguments();
        $templateVariableContainer = $this->getMock(\TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer::class);
        $templateVariableContainer->expects($this->at(0))->method('add')->with('k1', 'v1');
        $templateVariableContainer->expects($this->at(1))->method('add')->with('k2', 'v2');
        $templateVariableContainer->expects($this->at(2))->method('remove')->with('k1');
        $templateVariableContainer->expects($this->at(3))->method('remove')->with('k2');
        $renderingContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface::class);
        $renderingContext->expects($this->any())->method('getTemplateVariableContainer')->will($this->returnValue($templateVariableContainer));
        $rootNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode::class);
        $widgetContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
        $this->request->expects($this->any())->method('getWidgetContext')->will($this->returnValue($widgetContext));
        $widgetContext->expects($this->any())->method('getViewHelperChildNodeRenderingContext')->will($this->returnValue($renderingContext));
        $widgetContext->expects($this->any())->method('getViewHelperChildNodes')->will($this->returnValue($rootNode));
        $rootNode->expects($this->any())->method('evaluate')->with($renderingContext)->will($this->returnValue('Rendered Results'));
        $output = $this->viewHelper->render(['k1' => 'v1', 'k2' => 'v2']);
        $this->assertEquals('Rendered Results', $output);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\Widget\Exception\WidgetRequestNotFoundException
     */
    public function renderThrowsExceptionIfTheRequestIsNotAWidgetRequest()
    {
        $this->request = $this->getMock('Tx_Fluid_MVC_Request');
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->viewHelper->initializeArguments();
        $this->viewHelper->render();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\Widget\Exception\RenderingContextNotFoundException
     */
    public function renderThrowsExceptionIfTheChildNodeRenderingContextIsNotThere()
    {
        $this->request = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class);
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->viewHelper->initializeArguments();
        $widgetContext = $this->getMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
        $this->request->expects($this->any())->method('getWidgetContext')->will($this->returnValue($widgetContext));
        $widgetContext->expects($this->any())->method('getViewHelperChildNodeRenderingContext')->will($this->returnValue(null));
        $widgetContext->expects($this->any())->method('getViewHelperChildNodes')->will($this->returnValue(null));
        $this->viewHelper->render();
    }
}
